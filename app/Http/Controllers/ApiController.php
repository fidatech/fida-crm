<?php
namespace App\Http\Controllers;
use App\Services\CrmService;
use Illuminate\Http\Request;
class ApiController extends Controller {
 public function __construct(private CrmService $crm){}
 private function allowRead(Request $r,string $entity):void{$key=$r->attributes->get('crm_key');abort_unless(in_array('read',json_decode($key->abilities,true)??[],true) && \App\Services\RoleService::allows((int)$key->tenant_id,(int)$key->user_id,'records.'.$entity.'.view'),403);}
 private function allowWrite(Request $r,string $entity,string $action):void{$key=$r->attributes->get('crm_key');abort_unless(in_array('write',json_decode($key->abilities,true)??[],true),403);abort_unless(\App\Services\RoleService::allows((int)$key->tenant_id,(int)$key->user_id,'records.'.$entity.'.'.$action),403);}
 public function index(Request $r,string $entity){$this->allowRead($r,$entity);$tenant=$r->attributes->get('crm_tenant');$q=$this->crm->query($entity,$tenant);return response()->json($q->latest('id')->paginate(min(max((int)$r->query('per_page',20),1),100)));}
 public function show(Request $r,string $entity,int $id){$this->allowRead($r,$entity);return response()->json($this->crm->query($entity,$r->attributes->get('crm_tenant'))->findOrFail($id));}
 public function store(Request $r,string $entity){$this->allowWrite($r,$entity,'create');return response()->json($this->crm->save($entity,$r->attributes->get('crm_tenant'),$r->all(),null,$r->attributes->get('crm_key')->user_id),201);}
 public function update(Request $r,string $entity,int $id){$this->allowWrite($r,$entity,'update');return response()->json($this->crm->save($entity,$r->attributes->get('crm_tenant'),$r->all(),$id,$r->attributes->get('crm_key')->user_id));}
}
