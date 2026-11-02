<?php
namespace App\Services;
use App\Models\Record;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
class CrmService {
 public function definition(string $entity):array{$definition=config('crm.entities.'.$entity);abort_unless(is_array($definition),404);return $definition;}
 public function query(string $entity,int $tenant){$this->definition($entity);return (new Record())->useEntity($entity)->newQuery()->tenant($tenant);}
 public function rules(string $entity,int $tenant):array{
  $def=$this->definition($entity);$rules=[];
  foreach($def['fields'] as [$name,$type,$required]){
   $a=[$required?'required':'nullable'];
   if(str_starts_with($type,'select:'))$a[]=Rule::in(explode('|',substr($type,7)));
   elseif(str_starts_with($type,'fk:')||$type==='user'||$type==='number')$a[]='integer';
   elseif($type==='money')$a[]='numeric';
   elseif($type==='bool')$a[]='boolean';
   elseif($type==='email')$a[]='email:rfc';
   elseif($type==='url')$a[]='url:http,https';
   elseif($type==='date')$a[]='date_format:Y-m-d';
   elseif($type==='datetime')$a[]='date';
   elseif($type==='json')$a[]='json';
   else $a[]='string';
   if(in_array($type,['text','email','currency'],true)||str_starts_with($type,'select:'))$a[]='max:255';
   if($type==='number'){$a[]='min:0';$a[]='max:1000000000';}
   if($type==='money'){$a[]='min:0';$a[]='decimal:0,2';}
   if($type==='currency')$a[]=Rule::in(CurrencyService::options($tenant));
   $rules[$name]=$a;
  }
  return $rules;
 }
 public function save(string $entity,int $tenant,array $input,?int $recordId=null,?int $actorId=null):Record{
  $definition=$this->definition($entity);
  $rules=$this->rules($entity,$tenant);if($entity==='web_forms')$rules['slug'][]=Rule::unique('web_forms','slug')->ignore($recordId)->whereNull('deleted_at');if($entity==='custom_fields'){$rules['entity'][]=Rule::in(array_keys(config('crm.entities')));$rules['field_key'][]='alpha_dash';} $data=Validator::make($input,$rules)->validate();
  if(!$recordId && in_array($entity,['leads','deals','tasks','tickets','knowledge_articles','campaigns','web_forms'],true) && empty($data['status']))$data['status']=match($entity){'leads'=>'new','deals'=>'open','tasks'=>'open','tickets'=>'new','knowledge_articles'=>'draft','campaigns'=>'draft','web_forms'=>'draft'};
  if(in_array($entity,['deals','products','campaigns'],true) && !array_key_exists('currency',$data) && !$recordId)$data['currency']=CurrencyService::base($tenant);
  foreach($definition['fields'] as [$nm,$type]){
   if($type==='bool')$data[$nm]=(bool)($input[$nm]??false);
   if($type==='datetime' && !empty($data[$nm]))$data[$nm]=date('Y-m-d H:i:s',strtotime($data[$nm]));
  }
  WorkspaceContext::checkLinkedForTenant($data,$definition['fields'],$tenant);
  if($entity==='deals' && isset($data['currency']) && array_key_exists('amount',$data) && $data['amount']!==null){$converted=CurrencyService::converted($tenant,$data['currency'],$data['amount']);$data['rate_to_base']=$converted['rate_to_base'];$data['amount_base']=$converted['amount_base'];}
  foreach(['contacts'=>'email','companies'=>'domain'] as $type=>$field){if($entity===$type && !empty($data[$field])){$data[$field]=mb_strtolower(trim($data[$field]));$q=$this->query($entity,$tenant)->where($field,$data[$field]);if($recordId)$q->where('id','!=',$recordId);if($q->exists())throw ValidationException::withMessages([$field=>'A record with this '.str_replace('_',' ',$field).' already exists in this workspace.']);}}

  $custom=[];if($entity!=='custom_fields'){
   $fields=$this->query('custom_fields',$tenant)->where('entity',$entity)->get();
   foreach($fields as $f){$key='custom_'.$f->field_key;$v=$input[$key]??null;$rule=match($f->field_type){'number'=>'nullable|numeric','date'=>'nullable|date_format:Y-m-d','boolean'=>'nullable|boolean',default=>'nullable|string|max:20000'};
    if($f->required)$rule='required|'.substr($rule,9);
    $custom[$f->id]=Validator::make([$key=>$v],[$key=>$rule])->validate()[$key]??null;
   }
  }
  if($entity==='deals' && !empty($data['stage_id'])){
   $stage=$this->query('stages',$tenant)->findOrFail($data['stage_id']);
   if(!empty($data['pipeline_id']) && (int)$stage->pipeline_id!==(int)$data['pipeline_id'])throw ValidationException::withMessages(['stage_id'=>'Selected stage does not belong to the pipeline.']);
   $data['pipeline_id']=$stage->pipeline_id;$data['probability']=$stage->probability;
  }
  if($entity==='stages' && !empty($data['pipeline_id'])){
   $pipeline=$this->query('pipelines',$tenant)->findOrFail($data['pipeline_id']);
   if($pipeline->entity!=='deals' && $pipeline->entity!=='tickets')throw ValidationException::withMessages(['pipeline_id'=>'Invalid pipeline.']);
  }
  return DB::transaction(function()use($entity,$tenant,$recordId,$actorId,$data,$custom){
   $record=$recordId?$this->query($entity,$tenant)->lockForUpdate()->findOrFail($recordId):(new Record())->useEntity($entity);
   $created=!$record->exists;$before=$record->exists?$record->getAttributes():[];
   $record->fill($data);if($created){$record->tenant_id=$tenant;$record->created_by=$actorId;}
   $record->save();
   foreach($custom as $fieldId=>$value){DB::table('custom_field_values')->updateOrInsert(['tenant_id'=>$tenant,'entity'=>$entity,'entity_id'=>$record->id,'field_id'=>$fieldId],['value'=>is_bool($value)?($value?'1':'0'):$value,'updated_at'=>now()]);}
   $this->activity($tenant,$actorId,$entity,$record->id,$created?'created':'updated',null,['before'=>$before,'after'=>$data]);
   app(AutomationService::class)->onRecord($tenant,$entity,$created?'created':'updated',$record,$actorId);
   return $record;
  });
 }
 public function activity(int $tenant,?int $actor,string $entity,int $id,string $event,?string $msg=null,?array $change=null):void{
  DB::table('activities')->insert(['tenant_id'=>$tenant,'user_id'=>$actor,'entity'=>$entity,'entity_id'=>$id,'event'=>$event,'message'=>$msg,'changes'=>$change?json_encode($change):null,'created_at'=>now(),'updated_at'=>now()]);DB::table('audit_logs')->insert(['tenant_id'=>$tenant,'user_id'=>$actor,'action'=>$event,'entity'=>$entity,'entity_id'=>$id,'details'=>$change?json_encode($change):null,'ip'=>request()?->ip(),'created_at'=>now(),'updated_at'=>now()]);
 }
 public function delete(string $entity,int $tenant,int $id,?int $actor):void{
  DB::transaction(function()use($entity,$tenant,$id,$actor){$r=$this->query($entity,$tenant)->findOrFail($id);$r->delete();$this->activity($tenant,$actor,$entity,$id,'deleted');});
 }
}
