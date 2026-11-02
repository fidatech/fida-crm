<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CurrencyService {
 public const CATALOG = [
  'USD'=>['US Dollar','$',2],'EUR'=>['Euro','€',2],'GBP'=>['Pound Sterling','£',2],
  'CAD'=>['Canadian Dollar','C$',2],'AUD'=>['Australian Dollar','A$',2],'AFN'=>['Afghan Afghani','؋',2],
  'AED'=>['UAE Dirham','د.إ',2],'INR'=>['Indian Rupee','₹',2],'CNY'=>['Chinese Yuan','¥',2],'SAR'=>['Saudi Riyal','﷼',2],'QAR'=>['Qatari Riyal','ر.ق',2],
  'PKR'=>['Pakistani Rupee','₨',2],'TRY'=>['Turkish Lira','₺',2],'CHF'=>['Swiss Franc','CHF',2],
  'NZD'=>['New Zealand Dollar','NZ$',2],'SGD'=>['Singapore Dollar','S$',2],
 ];
 public static function seedForTenant(int $tenant,?string $base=null):void {
  // Database defaults do not populate a freshly created Eloquent model instance.
  // Normalize missing and unsupported codes before initializing the currency register.
  $base=strtoupper(trim($base??''));
  if(!isset(self::CATALOG[$base]))$base='USD';
  foreach([$base] as $code){$row=self::CATALOG[$code];DB::table('tenant_currencies')->updateOrInsert(['tenant_id'=>$tenant,'code'=>$code],['name'=>$row[0],'symbol'=>$row[1],'precision'=>$row[2],'rate_to_base'=>1,'is_base'=>$code===$base,'active'=>true,'updated_at'=>now()]);}
  DB::table('currency_rate_history')->insert(['tenant_id'=>$tenant,'code'=>$base,'base_code'=>$base,'rate_to_base'=>1,'source'=>'initial','effective_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
 }
 public static function options(int $tenant):array{return DB::table('tenant_currencies')->where('tenant_id',$tenant)->where('active',true)->orderByDesc('is_base')->orderBy('code')->pluck('code')->all();}
 public static function rate(int $tenant,string $code):string {
  $rate=DB::table('tenant_currencies')->where('tenant_id',$tenant)->where('code',$code)->where('active',1)->value('rate_to_base');
  if($rate===null)throw ValidationException::withMessages(['currency'=>'Currency is not enabled for this workspace.']);
  if((float)$rate<=0)throw ValidationException::withMessages(['currency'=>'Please set a positive exchange rate.']);
  return (string)$rate;
 }
 public static function base(int $tenant):string{return (string)DB::table('tenants')->where('id',$tenant)->value('currency');}
 public static function converted(int $tenant,string $code,$amount):array{
  $rate=self::rate($tenant,$code);$base=round((float)$amount * (float)$rate,2);
  return ['rate_to_base'=>$rate,'amount_base'=>number_format($base,2,'.','')];
 }
}
