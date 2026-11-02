<?php
namespace App\Http\Controllers;
use App\Services\SalesService;
use App\Services\WorkspaceContext as W;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class SalesController extends Controller {
 public function __construct(private SalesService $sales){}
 public function index(string $kind){W::authorize('sales.view');abort_unless(in_array($kind,['quote','invoice']),404);$docs=DB::table('sales_documents')->where('tenant_id',W::id())->where('kind',$kind)->latest('id')->paginate(20);return view('crm.sales.index',compact('kind','docs'));}
 public function create(string $kind){W::authorize('sales.create');abort_unless(in_array($kind,['quote','invoice']),404);$doc=null;$lines=collect();return view('crm.sales.form',compact('kind','doc','lines'));}
 public function store(Request $r,string $kind){W::authorize('sales.create');$id=$this->sales->save(W::id(),array_merge($r->all(),['kind'=>$kind]),null,auth()->id());return redirect()->route('sales.show',[$kind,$id])->with('success','Sales document created.');}
 public function show(string $kind,int $id){W::authorize('sales.view');abort_unless(in_array($kind,['quote','invoice'],true),404);$doc=DB::table('sales_documents')->where('tenant_id',W::id())->where('kind',$kind)->where('id',$id)->first();abort_unless($doc,404);$lines=DB::table('sales_lines')->where('tenant_id',W::id())->where('sales_document_id',$id)->get();$payments=DB::table('payments')->where('tenant_id',W::id())->where('sales_document_id',$id)->orderByDesc('id')->get();return view('crm.sales.show',compact('kind','doc','lines','payments'));}
 public function edit(string $kind,int $id){W::authorize('sales.update');$doc=DB::table('sales_documents')->where('tenant_id',W::id())->where('kind',$kind)->where('id',$id)->first();abort_unless($doc,404);abort_unless($doc->status==='draft',422);$lines=DB::table('sales_lines')->where('tenant_id',W::id())->where('sales_document_id',$id)->get();return view('crm.sales.form',compact('kind','doc','lines'));}
 public function update(Request $r,string $kind,int $id){W::authorize('sales.update');$this->sales->save(W::id(),array_merge($r->all(),['kind'=>$kind]),$id,auth()->id());return redirect()->route('sales.show',[$kind,$id])->with('success','Saved.');}
 public function transition(Request $r,string $kind,int $id){W::authorize('sales.transition');$d=$r->validate(['status'=>'required|in:sent,accepted,rejected,void']);$this->sales->status(W::id(),$id,$d['status'],auth()->id());return back()->with('success','Status updated.');}
 public function convert(int $id){W::authorize('sales.create');$invoice=$this->sales->convert(W::id(),$id,auth()->id());return redirect()->route('sales.show',['invoice',$invoice])->with('success','Invoice created from quote.');}
 public function payment(Request $r,int $id){W::authorize('sales.payment');$this->sales->payment(W::id(),$id,$r->all(),auth()->id());return back()->with('success','Payment recorded.');}
}
