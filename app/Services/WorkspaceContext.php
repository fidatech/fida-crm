<?php
namespace App\Services;
use App\Models\Tenant;
use App\Models\Record;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class WorkspaceContext {
 public static function id():int{$id=(int)session('tenant_id');abort_unless($id>0,403,'Select a workspace.');return $id;}
 public static function tenant():Tenant{return Tenant::findOrFail(self::id());}
 public static function role():string{return (string)DB::table('memberships')->where('tenant_id',self::id())->where('user_id',Auth::id())->value('role');}
 public static function allows(string $permission):bool{return RoleService::allows(self::id(),(int)Auth::id(),$permission);}
 public static function authorize(string $permission):void{abort_unless(self::allows($permission),403,'Missing permission: '.$permission);}
 public static function canWrite():bool{return self::allows('records.contacts.create');}
 public static function tenantRecord(string $entity,int $id):?Record{return (new Record())->useEntity($entity)->newQuery()->tenant(self::id())->find($id);}
 public static function checkLinkedForTenant(array $data,array $fields,int $tenant):void{
  foreach($fields as [$name,$type]){
   if(!isset($data[$name])||$data[$name]==='')continue;
   if(str_starts_with($type,'fk:')){
    $e=substr($type,3);abort_unless((new Record())->useEntity($e)->newQuery()->tenant($tenant)->whereKey($data[$name])->exists(),422,'Invalid reference: '.$name);
   }
   if($type==='user')abort_unless(DB::table('memberships')->where('tenant_id',$tenant)->where('user_id',(int)$data[$name])->exists(),422,'Invalid team member: '.$name);
  }
 }
}
