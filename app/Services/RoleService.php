<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class RoleService {
 public static function seedForTenant(int $tenant):void {
  foreach(config('crm_access.templates') as $slug=>$permissions){
   $id=DB::table('tenant_roles')->where('tenant_id',$tenant)->where('slug',$slug)->value('id');
   if(!$id){$id=DB::table('tenant_roles')->insertGetId(['tenant_id'=>$tenant,'name'=>Str::headline($slug),'slug'=>$slug,'protected'=>true,'created_at'=>now(),'updated_at'=>now()]);
    foreach($permissions as $permission) DB::table('tenant_role_permissions')->insert(['tenant_id'=>$tenant,'role_id'=>$id,'permission'=>$permission]);}
   DB::table('memberships')->where('tenant_id',$tenant)->where('role',$slug)->whereNull('role_id')->update(['role_id'=>$id]);
  }
 }
 public static function allows(int $tenant,int $user,string $permission):bool {
  if(!array_key_exists($permission,config('crm_access.permissions',[])) || $user<1)return false;
  // Request-scoped permission snapshot: avoid repeat joins for every sidebar link.
  $request=app()->bound('request')?request():null;
  $cacheKey='crm.permissions.'.$tenant.'.'.$user;
  $snapshot=$request?->attributes->get($cacheKey);
  if($snapshot===null){
   $member=DB::table('memberships')->where('tenant_id',$tenant)->where('user_id',$user)->first(['role_id','role']);
   if(!$member)$snapshot=['owner'=>false,'permissions'=>[]];
   elseif($member->role==='owner')$snapshot=['owner'=>true,'permissions'=>[]];
   elseif(!$member->role_id)$snapshot=['owner'=>false,'permissions'=>config('crm_access.templates.'.$member->role,[])];
   else $snapshot=['owner'=>false,'permissions'=>DB::table('tenant_roles')->join('tenant_role_permissions','tenant_role_permissions.role_id','=','tenant_roles.id')->where('tenant_roles.tenant_id',$tenant)->where('tenant_role_permissions.tenant_id',$tenant)->where('tenant_roles.id',$member->role_id)->pluck('tenant_role_permissions.permission')->all()];
   $request?->attributes->set($cacheKey,$snapshot);
  }
  return $snapshot['owner'] || in_array($permission,$snapshot['permissions'],true);
 }
 public static function canAssign(int $tenant,int $actor,int $roleId):bool {
  $role=DB::table('tenant_roles')->where('tenant_id',$tenant)->where('id',$roleId)->first();if(!$role||$role->slug==='owner')return false;
  foreach(DB::table('tenant_role_permissions')->where('tenant_id',$tenant)->where('role_id',$roleId)->pluck('permission') as $permission){if(!self::allows($tenant,$actor,$permission))return false;}
  return true;
 }
 public static function forEntity(string $entity,string $action):string{return 'records.'.$entity.'.'.$action;}
 public static function roles(int $tenant){return DB::table('tenant_roles')->where('tenant_id',$tenant)->orderBy('name')->get();}
}
