<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
class Record extends Model {
 use SoftDeletes;
 protected $guarded=[];
 protected $table='contacts';
 public $timestamps=true;
 public function useEntity(string $entity):static{
  abort_unless(isset(config('crm.entities')[$entity]),404);
  $this->setTable($entity);return $this;
 }
 public function newInstance($attributes=[], $exists=false){$m=parent::newInstance($attributes,$exists);$m->setTable($this->getTable());return $m;}
 public function newFromBuilder($attributes=[], $connection=null){$m=parent::newFromBuilder($attributes,$connection);$m->setTable($this->getTable());return $m;}
 public function scopeTenant(Builder $q, int $tenant):Builder{return $q->where($this->getTable().'.tenant_id',$tenant);}
}
