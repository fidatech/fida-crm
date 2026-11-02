<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Tenant extends Model {protected $guarded=[];protected function casts():array{return ['trial_ends_at'=>'datetime'];}public function users(){return $this->belongsToMany(User::class,'memberships')->withPivot('role')->withTimestamps();}}
