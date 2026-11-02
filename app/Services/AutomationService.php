<?php
namespace App\Services;
use App\Models\Record;
use Illuminate\Support\Facades\DB;
class AutomationService {
 public function onRecord(int $tenant,string $entity,string $event,Record $record,?int $actor):void{
  if($entity==='workflows'||$entity==='tasks')return; // protect from recursive rules
  $rules=(new Record())->useEntity('workflows')->newQuery()->tenant($tenant)->where('active',1)->where('trigger_entity',$entity)->where('trigger_event',$event)->limit(50)->get();
  foreach($rules as $flow){
   if($flow->condition_field && (!array_key_exists($flow->condition_field,$record->getAttributes())||strval($record->{$flow->condition_field})!==strval($flow->condition_value)))continue;
   $status='success';$message=null;
   try{
    if($flow->action_type==='create_task'){
     (new Record())->useEntity('tasks')->fill(['tenant_id'=>$tenant,'title'=>$flow->action_value ?: 'Follow up: '.$entity.' #'.$record->id,'status'=>'open','priority'=>'normal','created_by'=>$actor])->save();
    }elseif($flow->action_type==='assign_owner'){
     $target=(int)$flow->action_value;
     if(!DB::table('memberships')->where('tenant_id',$tenant)->where('user_id',$target)->exists())throw new \DomainException('Assigned member must belong to workspace');
     $attrs=$record->getAttributes();if(array_key_exists('owner_id',$attrs))DB::table($entity)->where('tenant_id',$tenant)->where('id',$record->id)->update(['owner_id'=>$target]);
    }elseif($flow->action_type==='set_status'){
     $statusOptions=collect(config('crm.entities.'.$entity.'.fields'))->first(fn($f)=>$f[0]==='status');
     if(!$statusOptions || !in_array($flow->action_value,explode('|',substr($statusOptions[1],7)),true))throw new \DomainException('Invalid status action');
     DB::table($entity)->where('tenant_id',$tenant)->where('id',$record->id)->update(['status'=>$flow->action_value]);
    }
   }catch(\Throwable $e){$status='failed';$message=$e->getMessage();}
   DB::table('automation_runs')->insert(['tenant_id'=>$tenant,'workflow_id'=>$flow->id,'entity'=>$entity,'entity_id'=>$record->id,'status'=>$status,'message'=>$message,'created_at'=>now(),'updated_at'=>now()]);
  }
 }
}
