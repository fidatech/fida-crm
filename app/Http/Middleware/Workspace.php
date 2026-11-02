<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class Workspace {
 public function handle(Request $request,Closure $next){
  $id=(int)$request->session()->get('tenant_id');
  if(!$id || !DB::table('memberships')->where('tenant_id',$id)->where('user_id',$request->user()->id)->exists()){
   $first=DB::table('memberships')->where('user_id',$request->user()->id)->first();
   if(!$first)return redirect()->route('workspace.new');
   $id=(int)$first->tenant_id;$request->session()->put('tenant_id',$id);
  }
  $status=DB::table('tenants')->where('id',$id)->first(['status','trial_ends_at']);
  abort_unless($status && ($status->status==='active'||($status->status==='trial'&&$status->trial_ends_at && strtotime($status->trial_ends_at)>=time())),402,'Subscription is not active. Contact workspace owner.');
  return $next($request);
 }
}
