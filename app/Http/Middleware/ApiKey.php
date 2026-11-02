<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\DB;
class ApiKey {
 public function handle($request,Closure $next){
  $token=$request->bearerToken();abort_unless($token && str_starts_with($token,'fida_'),401);
  $key=DB::table('api_keys')->where('token_hash',hash('sha256',$token))->first();
  abort_unless($key && (!$key->expires_at || strtotime($key->expires_at)>time()),401);
  $member=DB::table('memberships')->where('tenant_id',$key->tenant_id)->where('user_id',$key->user_id)->exists();abort_unless($member,401);
  $tenant=DB::table('tenants')->where('id',$key->tenant_id)->first();abort_unless($tenant && ($tenant->status==='active'||($tenant->status==='trial'&&strtotime($tenant->trial_ends_at)>=time())),402);
  $request->attributes->set('crm_tenant',(int)$key->tenant_id);$request->attributes->set('crm_key',$key);
  DB::table('api_keys')->where('id',$key->id)->update(['last_used_at'=>now()]);return $next($request);
 }
}
