<?php
namespace App\Http\Controllers;
use App\Services\CrmService;
use App\Services\WorkspaceContext as W;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class LeadController extends Controller {
 public function convert(Request $r,int $id){W::authorize('leads.convert');$d=$r->validate(['create_deal'=>'sometimes|boolean','create_company'=>'sometimes|boolean']);$crm=app(CrmService::class);
  $lead=$crm->query('leads',W::id())->findOrFail($id);abort_unless($lead->status!=='converted',422,'Lead is already converted.');
  return DB::transaction(function()use($r,$d,$crm,$lead){
   $companyId=$lead->company_id;
   if(!$companyId&&$r->boolean('create_company')&&$lead->company_name){$company=$crm->save('companies',W::id(),['name'=>$lead->company_name],null,auth()->id());$companyId=$company->id;}
   $contact=$crm->save('contacts',W::id(),['first_name'=>$lead->name,'last_name'=>'','email'=>$lead->email,'phone'=>$lead->phone,'company_id'=>$companyId,'lifecycle_stage'=>'sales_qualified','source'=>$lead->source,'owner_id'=>$lead->owner_id],null,auth()->id());
   $lead->contact_id=$contact->id;$lead->company_id=$companyId;$lead->status='converted';$lead->save();
   if($r->boolean('create_deal')){$pipeline=$crm->query('pipelines',W::id())->where('entity','deals')->first();$stage=$pipeline?$crm->query('stages',W::id())->where('pipeline_id',$pipeline->id)->orderBy('sort_order')->first():null;
    $crm->save('deals',W::id(),['title'=>$lead->name.' opportunity','amount'=>$lead->estimated_value??0,'currency'=>W::tenant()->currency,'company_id'=>$companyId,'contact_id'=>$contact->id,'owner_id'=>$lead->owner_id,'pipeline_id'=>$pipeline?->id,'stage_id'=>$stage?->id,'status'=>'open','probability'=>$stage?->probability??10],null,auth()->id());}
   $crm->activity(W::id(),auth()->id(),'leads',$lead->id,'converted','Converted into contact #'.$contact->id);
   return redirect()->route('entities.show',['contacts',$contact->id])->with('success','Lead converted to customer contact.');
  });
 }
}
