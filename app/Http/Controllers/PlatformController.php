<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PlatformController extends Controller {
 public function index(){abort_unless(auth()->user()->platform_admin,403);return view('crm.platform',['stats'=>['workspaces'=>DB::table('tenants')->count(),'users'=>DB::table('users')->count(),'subscriptions'=>DB::table('subscriptions')->where('status','active')->count()],'tenants'=>DB::table('tenants')->latest()->paginate(30),'plans'=>DB::table('plans')->get()]);}
 public function plan(Request $r){abort_unless(auth()->user()->platform_admin,403);$d=$r->validate(['name'=>'required|string|max:120','slug'=>'required|alpha_dash|max:100|unique:plans,slug','monthly_cents'=>'required|integer|min:0','seat_limit'=>'required|integer|min:1','stripe_price_id'=>'nullable|string|max:100']);DB::table('plans')->insert($d+['created_at'=>now(),'updated_at'=>now()]);return back()->with('success','Plan created.');}
 public function status(Request $r,int $id){abort_unless(auth()->user()->platform_admin,403);$d=$r->validate(['status'=>'required|in:active,suspended,trial']);DB::table('tenants')->where('id',$id)->update($d);return back()->with('success','Workspace status updated.');}
}
