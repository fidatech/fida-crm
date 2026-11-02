<?php
namespace App\Http\Controllers;
use App\Services\CrmService;
use App\Services\WorkspaceContext as W;
use Illuminate\Support\Facades\DB;
class DashboardController extends Controller {
 public function __construct(private CrmService $crm){}
 public function index(){W::authorize('dashboard.view');
  $tenant=W::tenant();$stats=[];foreach(['contacts','companies','leads','deals','tickets','tasks'] as $e)$stats[$e]=$this->crm->query($e,$tenant->id)->count();
  $openTasks=$this->crm->query('tasks',$tenant->id)->whereNotIn('status',['completed','cancelled'])->count();
  $dealTotal=$this->crm->query('deals',$tenant->id)->where('status','open')->selectRaw('COALESCE(SUM(COALESCE(amount_base,CASE WHEN currency = ? THEN amount ELSE 0 END)),0) as value',[$tenant->currency])->value('value');
  $deals=$this->crm->query('deals',$tenant->id)->latest()->limit(6)->get();$tasks=$this->crm->query('tasks',$tenant->id)->where('status','!=','completed')->orderBy('due_at')->limit(8)->get();
  $due=DB::table('sales_documents')->where('tenant_id',$tenant->id)->where('kind','invoice')->where('status','!=','void')->whereDate('due_on','<',now()->toDateString())->selectRaw('COALESCE(SUM((total-amount_paid)*COALESCE(rate_to_base,CASE WHEN currency = ? THEN 1 ELSE 0 END)),0) as balance',[$tenant->currency])->value('balance');
  // Actionable, tenant-scoped priority queue; explicit rules, not an unverified AI prediction.
  $priority = collect();
  $overdueTasks = $this->crm->query('tasks',$tenant->id)->whereNotIn('status',['completed','cancelled'])->whereNotNull('due_at')->where('due_at','<',now())->orderBy('due_at')->limit(4)->get();
  foreach($overdueTasks as $task){$priority->push(['type'=>'Overdue task','icon'=>'bi-calendar-x','title'=>$task->title,'detail'=>'Due '.\Illuminate\Support\Carbon::parse($task->due_at)->format('M j, Y'),'url'=>route('entities.show',['tasks',$task->id])]);}
  $lateDeals = $this->crm->query('deals',$tenant->id)->where('status','open')->whereNotNull('expected_close_at')->whereDate('expected_close_at','<',now()->toDateString())->orderBy('expected_close_at')->limit(4)->get();
  foreach($lateDeals as $deal){$priority->push(['type'=>'Past close date','icon'=>'bi-graph-down-arrow','title'=>$deal->title,'detail'=>'Expected '.\Illuminate\Support\Carbon::parse($deal->expected_close_at)->format('M j, Y'),'url'=>route('entities.show',['deals',$deal->id])]);}
  $qualifiedLeads = $this->crm->query('leads',$tenant->id)->whereNotIn('status',['converted','unqualified'])->where('score','>=',70)->orderByDesc('score')->limit(4)->get();
  foreach($qualifiedLeads as $lead){$priority->push(['type'=>'High-scoring lead','icon'=>'bi-person-check','title'=>$lead->name,'detail'=>'Lead score: '.$lead->score,'url'=>route('entities.show',['leads',$lead->id])]);}
  $prospectQueue=DB::table('discovery_candidates')->where('tenant_id',$tenant->id)->where('status','pending')->count();
  return view('crm.dashboard',compact('tenant','stats','dealTotal','deals','tasks','due','openTasks','priority','prospectQueue'));
 }
 public function reports(){W::authorize('reports.view');
  $tenant=W::tenant();$byStage=$this->crm->query('deals',$tenant->id)->select('status')->selectRaw('COUNT(*) as count, COALESCE(SUM(COALESCE(amount_base,CASE WHEN currency = ? THEN amount ELSE 0 END)),0) as amount',[$tenant->currency])->groupBy('status')->get();
  $byTicket=$this->crm->query('tickets',$tenant->id)->select('status')->selectRaw('COUNT(*) as count')->groupBy('status')->get();
  $invoices=DB::table('sales_documents')->where('tenant_id',$tenant->id)->where('kind','invoice')->select('status')->selectRaw('COUNT(*) as count,COALESCE(SUM(COALESCE(total_base,CASE WHEN currency = ? THEN total ELSE 0 END)),0) as amount',[$tenant->currency])->groupBy('status')->get();
  $campaigns=DB::table('campaign_deliveries')->where('tenant_id',$tenant->id)->select('status')->selectRaw('COUNT(*) as count')->groupBy('status')->get();
  return view('crm.reports',compact('tenant','byStage','byTicket','invoices','campaigns'));
 }
 public function search(){W::authorize('search.view'); $q=mb_substr((string)request('q'),0,80);if(mb_strlen($q)<2)return redirect()->route('dashboard');$out=[];
  foreach(['contacts','companies','deals','leads','tickets'] as $type){$title=config('crm.entities.'.$type.'.title');$items=$this->crm->query($type,W::id())->where($title,'like','%'.$q.'%')->limit(12)->get();foreach($items as $item)$out[]= ['entity'=>$type,'name'=>$item->{$title},'id'=>$item->id];}
  return view('crm.search',compact('out','q'));
 }
}
