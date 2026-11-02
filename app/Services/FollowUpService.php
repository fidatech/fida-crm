<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
class FollowUpService {public function sendDueReminders():void{
 $tasks=DB::table('tasks')->whereNull('deleted_at')->whereNotIn('status',['completed','cancelled'])->where('due_at','>=',now()->subMinutes(5))->where('due_at','<=',now())->whereNotNull('assigned_to')->limit(100)->get();
 foreach($tasks as $task){$user=DB::table('users')->join('memberships','memberships.user_id','=','users.id')->where('memberships.tenant_id',$task->tenant_id)->where('users.id',$task->assigned_to)->first();if(!$user)continue;try{Mail::raw('Task reminder: '.$task->title,fn($m)=>$m->to($user->email)->subject('CRM task due'));}catch(\Throwable $e){report($e);}}
}}
