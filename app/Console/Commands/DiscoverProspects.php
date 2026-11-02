<?php
namespace App\Console\Commands;
use App\Services\LeadDiscoveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class DiscoverProspects extends Command {
 protected $signature='crm:discover-prospects';protected $description='Run due saved Apollo prospect searches (one bounded page per search).';
 public function handle(LeadDiscoveryService $discovery):int {
  $rows=DB::table('discovery_searches')->join('tenants','tenants.id','=','discovery_searches.tenant_id')->where('discovery_searches.active',1)->whereIn('discovery_searches.frequency',['daily','weekly'])->where(function($q){$q->where('tenants.status','active')->orWhere(function($q){$q->where('tenants.status','trial')->where('tenants.trial_ends_at','>=',now());});})->select('discovery_searches.*')->limit(50)->get();
  foreach($rows as $search){$hours=$search->frequency==='weekly'?168:24;if($search->last_run_at && strtotime($search->last_run_at)>time()-$hours*3600)continue;
   try{$count=$discovery->search((int)$search->tenant_id,(int)$search->id,(int)$search->created_by);$this->line('Search #'.$search->id.': '.$count.' candidates');}
   catch(\Throwable $e){$this->error('Search #'.$search->id.' failed; inspect discovery run log.');report($e);DB::table('discovery_searches')->where('id',$search->id)->update(['last_run_at'=>now()]);}
  }return self::SUCCESS;
 }
}
