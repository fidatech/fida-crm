<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
class SalesService {
 public function save(int $tenant,array $input,?int $id=null,int $actor=0):int {
  $d=Validator::make($input,['kind'=>'required|in:quote,invoice','title'=>'required|string|max:255','company_id'=>'nullable|integer','contact_id'=>'nullable|integer','deal_id'=>'nullable|integer','currency'=>['required',\Illuminate\Validation\Rule::in(CurrencyService::options($tenant))],'issued_on'=>'required|date_format:Y-m-d','due_on'=>'nullable|date_format:Y-m-d','notes'=>'nullable|string|max:20000','lines'=>'required|array|min:1|max:100','lines.*.product_id'=>'nullable|integer','lines.*.description'=>'required|string|max:255','lines.*.quantity'=>'required|numeric|gt:0|lte:10000000','lines.*.unit_price'=>'required|numeric|min:0|max:999999999','lines.*.discount_percent'=>'nullable|numeric|between:0,100','lines.*.tax_percent'=>'nullable|numeric|between:0,100'])->validate();
  foreach(['company_id'=>'companies','contact_id'=>'contacts','deal_id'=>'deals'] as $f=>$e)if(!empty($d[$f]))abort_unless(app(CrmService::class)->query($e,$tenant)->whereKey($d[$f])->exists(),422,'Invalid linked record');
  foreach($d['lines'] as $l)if(!empty($l['product_id']))abort_unless(app(CrmService::class)->query('products',$tenant)->whereKey($l['product_id'])->where('currency',$d['currency'])->exists(),422,'Product must belong to the workspace and match document currency.');
  return DB::transaction(function()use($tenant,$id,$actor,$d){
   $existing=$id?DB::table('sales_documents')->where('tenant_id',$tenant)->where('id',$id)->lockForUpdate()->first():null;
   if($id)abort_unless($existing,404);
   if($existing){abort_unless($existing->status==='draft',422,'Only draft documents can be edited.');abort_unless($existing->kind===$d['kind'],422);}
   $subtotal=0;$taxTotal=0;$discountTotal=0;$lineRows=[];
   foreach($d['lines'] as $l){
    $qty=(int)round($l['quantity']*1000);$unit=(int)round($l['unit_price']*100);
    $gross=(int)round($qty*$unit/1000);$discount=(int)round($gross*($l['discount_percent']??0)/100);
    $tax=(int)round(($gross-$discount)*($l['tax_percent']??0)/100);
    $subtotal+=$gross;$discountTotal+=$discount;$taxTotal+=$tax;
    $lineRows[]=['tenant_id'=>$tenant,'product_id'=>$l['product_id']??null,'description'=>$l['description'],'quantity'=>$l['quantity'],'unit_price'=>$l['unit_price'],'discount_percent'=>$l['discount_percent']??0,'tax_percent'=>$l['tax_percent']??0,'line_total'=>number_format(($gross-$discount+$tax)/100,2,'.',''),'created_at'=>now(),'updated_at'=>now()];
   }
   $payload=['title'=>$d['title'],'company_id'=>$d['company_id']??null,'contact_id'=>$d['contact_id']??null,'deal_id'=>$d['deal_id']??null,'currency'=>$d['currency'],'issued_on'=>$d['issued_on'],'due_on'=>$d['due_on']??null,'notes'=>$d['notes']??null,'subtotal'=>number_format($subtotal/100,2,'.',''),'discount_total'=>number_format($discountTotal/100,2,'.',''),'tax_total'=>number_format($taxTotal/100,2,'.',''),'total'=>number_format(($subtotal-$discountTotal+$taxTotal)/100,2,'.',''),'updated_at'=>now()];
   $conversion=CurrencyService::converted($tenant,$d['currency'],($subtotal-$discountTotal+$taxTotal)/100);$payload['rate_to_base']=$conversion['rate_to_base'];$payload['total_base']=$conversion['amount_base'];
   if(!$existing){DB::table('document_sequences')->insertOrIgnore(['tenant_id'=>$tenant,'kind'=>$d['kind'],'next_number'=>1]);$seq=DB::table('document_sequences')->where('tenant_id',$tenant)->where('kind',$d['kind'])->lockForUpdate()->first();DB::table('document_sequences')->where('id',$seq->id)->increment('next_number');$payload+=['tenant_id'=>$tenant,'kind'=>$d['kind'],'number'=>strtoupper(substr($d['kind'],0,3)).'-'.date('Y').'-'.str_pad($seq->next_number,6,'0',STR_PAD_LEFT),'status'=>'draft','created_by'=>$actor,'created_at'=>now()];$id=DB::table('sales_documents')->insertGetId($payload);}else DB::table('sales_documents')->where('tenant_id',$tenant)->where('id',$id)->update($payload);
   DB::table('sales_lines')->where('tenant_id',$tenant)->where('sales_document_id',$id)->delete();foreach($lineRows as $l)DB::table('sales_lines')->insert($l+['sales_document_id'=>$id]);
   app(CrmService::class)->activity($tenant,$actor,'sales_documents',$id,$existing?'updated':'created');return $id;
  });
 }
 public function status(int $tenant,int $id,string $new,int $actor):void{
  DB::transaction(function()use($tenant,$id,$new,$actor){$doc=DB::table('sales_documents')->where('tenant_id',$tenant)->where('id',$id)->lockForUpdate()->first();abort_unless($doc,404);
   $allowed=$doc->kind==='quote'?['draft'=>['sent','void'],'sent'=>['accepted','rejected','void'],'accepted'=>[],'rejected'=>[],'void'=>[]]:['draft'=>['sent','void'],'sent'=>['void'],'partial'=>['void'],'paid'=>[],'void'=>[]];
   abort_unless(in_array($new,$allowed[$doc->status]??[],true),422,'Unsupported document transition.');if($new==='void')abort_unless((float)$doc->amount_paid<=0,422,'Paid invoices require a formal refund or credit process.');
   DB::table('sales_documents')->where('id',$id)->where('tenant_id',$tenant)->update(['status'=>$new,'updated_at'=>now()]);app(CrmService::class)->activity($tenant,$actor,'sales_documents',$id,'status',$new);
  });
 }
 public function convert(int $tenant,int $id,int $actor):int{
  return DB::transaction(function()use($tenant,$id,$actor){$quote=DB::table('sales_documents')->where('tenant_id',$tenant)->where('id',$id)->lockForUpdate()->first();abort_unless($quote && $quote->kind==='quote' && $quote->status==='accepted',422,'Only accepted quotes can be invoiced.');$other=DB::table('sales_documents')->where('tenant_id',$tenant)->where('source_quote_id',$id)->first();abort_unless(!$other,422,'Invoice already exists for this quote.');DB::table('document_sequences')->insertOrIgnore(['tenant_id'=>$tenant,'kind'=>'invoice','next_number'=>1]);$seq=DB::table('document_sequences')->where('tenant_id',$tenant)->where('kind','invoice')->lockForUpdate()->first();DB::table('document_sequences')->where('id',$seq->id)->increment('next_number');$copy=(array)$quote;unset($copy['id'],$copy['created_at'],$copy['updated_at']);$copy['kind']='invoice';$copy['number']='INV-'.date('Y').'-'.str_pad($seq->next_number,6,'0',STR_PAD_LEFT);$copy['status']='draft';$copy['source_quote_id']=$id;$copy['created_by']=$actor;$copy['issued_on']=now()->toDateString();$copy['created_at']=now();$copy['updated_at']=now();$invoice=DB::table('sales_documents')->insertGetId($copy);
   foreach(DB::table('sales_lines')->where('tenant_id',$tenant)->where('sales_document_id',$id)->get() as $line){$l=(array)$line;unset($l['id']);$l['sales_document_id']=$invoice;$l['created_at']=now();$l['updated_at']=now();DB::table('sales_lines')->insert($l);}
   app(CrmService::class)->activity($tenant,$actor,'sales_documents',$invoice,'converted','From quote '.$quote->number);return $invoice;
  });
 }
 public function payment(int $tenant,int $id,array $input,int $actor):void{
  $d=Validator::make($input,['amount'=>'required|numeric|gt:0','method'=>'required|in:cash,bank,card,wire,other','reference'=>'nullable|string|max:255','paid_on'=>'required|date_format:Y-m-d'])->validate();
  DB::transaction(function()use($tenant,$id,$d,$actor){$doc=DB::table('sales_documents')->where('tenant_id',$tenant)->where('id',$id)->lockForUpdate()->first();abort_unless($doc && $doc->kind==='invoice' && in_array($doc->status,['sent','partial'],true),422,'Invoice must be sent before payments.');$totalCents=(int)round($doc->total*100);$paidCents=(int)round($doc->amount_paid*100);$amountCents=(int)round($d['amount']*100);abort_unless($amountCents>0 && $amountCents<=$totalCents-$paidCents,422,'Amount exceeds invoice balance.');DB::table('payments')->insert(['tenant_id'=>$tenant,'sales_document_id'=>$id,'amount'=>number_format($amountCents/100,2,'.',''),'currency'=>$doc->currency,'method'=>$d['method'],'reference'=>$d['reference']??null,'paid_on'=>$d['paid_on'],'created_by'=>$actor,'created_at'=>now(),'updated_at'=>now()]);$new=$paidCents+$amountCents;DB::table('sales_documents')->where('id',$id)->where('tenant_id',$tenant)->update(['amount_paid'=>number_format($new/100,2,'.',''),'status'=>$new===$totalCents?'paid':'partial','updated_at'=>now()]);app(CrmService::class)->activity($tenant,$actor,'sales_documents',$id,'payment','Payment: '.$doc->currency.' '.number_format($amountCents/100,2));});
 }
}
