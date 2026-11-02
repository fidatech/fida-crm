<?php
namespace App\Http\Controllers;
use App\Services\WorkspaceContext as W;
use App\Services\CrmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
class MarketingController extends Controller {
 public function launch(Request $r,int $id){W::authorize('campaigns.launch');$campaign=app(CrmService::class)->query('campaigns',W::id())->findOrFail($id);abort_unless($campaign->channel==='email',422,'Only email campaigns can send messages.');$d=$r->validate(['template_id'=>'required|integer','segment_id'=>'nullable|integer']);$template=app(CrmService::class)->query('email_templates',W::id())->findOrFail($d['template_id']);$contacts=app(CrmService::class)->query('contacts',W::id())->where('email_opt_out','!=',1)->whereNotNull('email');if(!empty($d['segment_id'])){$segment=app(CrmService::class)->query('segments',W::id())->findOrFail($d['segment_id']);foreach(['lifecycle_stage','country'] as $f)if($segment->{$f})$contacts->where($f,$segment->{$f});}
  $ids=$contacts->limit(2000)->pluck('id');foreach($ids as $cid)DB::table('campaign_deliveries')->insertOrIgnore(['tenant_id'=>W::id(),'campaign_id'=>$id,'contact_id'=>$cid,'status'=>'pending','created_at'=>now(),'updated_at'=>now()]);
  $campaign->status='running';$campaign->save();
  // Template selected explicitly per campaign; persisted in a tenant-specific setting, not global state.
  DB::table('campaigns')->where('tenant_id',W::id())->where('id',$id)->update(['objective'=>'[template:'.$template->id.'] '.Str::limit($campaign->objective??'',300)]);
  return back()->with('success',count($ids).' non-opted-out contacts queued for campaign delivery. Configure SMTP and cron first.');
 }
 public function processQueue():int{
  $deliveries=DB::table('campaign_deliveries')->where('status','pending')->orderBy('id')->limit(40)->get();$sent=0;
  foreach($deliveries as $delivery){DB::transaction(function()use($delivery,&$sent){$row=DB::table('campaign_deliveries')->where('id',$delivery->id)->where('status','pending')->lockForUpdate()->first();if(!$row)return;
   $campaign=DB::table('campaigns')->where('id',$row->campaign_id)->where('tenant_id',$row->tenant_id)->first();$contact=DB::table('contacts')->where('id',$row->contact_id)->where('tenant_id',$row->tenant_id)->whereNull('deleted_at')->first();
   if(!$campaign || $campaign->status!=='running'||!$contact||$contact->email_opt_out||!$contact->email){DB::table('campaign_deliveries')->where('id',$row->id)->update(['status'=>'skipped']);return;}
   preg_match('/^\\[template:(\\d+)\\]/',$campaign->objective??'',$m);$template=isset($m[1])?DB::table('email_templates')->where('tenant_id',$row->tenant_id)->where('id',(int)$m[1])->whereNull('deleted_at')->first():null;
   if(!$template){DB::table('campaign_deliveries')->where('id',$row->id)->update(['status'=>'failed','error'=>'Template missing']);return;}
   try{$unsubscribe=URL::temporarySignedRoute('marketing.unsubscribe.confirm',now()->addYears(2),['tenant'=>$row->tenant_id,'contact'=>$contact->id]);Mail::raw(str_replace('{{first_name}}',$contact->first_name,$template->body)."\n\nUnsubscribe: ".$unsubscribe,fn($msg)=>$msg->to($contact->email)->subject($template->subject));DB::table('campaign_deliveries')->where('id',$row->id)->update(['status'=>'sent','sent_at'=>now(),'updated_at'=>now()]);$sent++;}catch(\Throwable $e){DB::table('campaign_deliveries')->where('id',$row->id)->update(['status'=>'failed','error'=>mb_substr($e->getMessage(),0,500),'updated_at'=>now()]);}
  });}return $sent;
 }
 public function unsubscribeConfirm(int $tenant,int $contact){$record=DB::table('contacts')->where('tenant_id',$tenant)->where('id',$contact)->whereNull('deleted_at')->first();abort_unless($record,404);return view('public.unsubscribe',compact('tenant','contact'));}
 public function unsubscribe(int $tenant,int $contact){$record=DB::table('contacts')->where('tenant_id',$tenant)->where('id',$contact)->whereNull('deleted_at')->first();abort_unless($record,404);DB::table('contacts')->where('tenant_id',$tenant)->where('id',$contact)->update(['email_opt_out'=>true,'updated_at'=>now()]);return view('public.thanks',['message'=>'Your email preference was updated. You will no longer receive marketing messages from this workspace.']);}
 public function publicForm(string $slug){$form=DB::table('web_forms')->where('slug',$slug)->where('status','published')->whereNull('deleted_at')->first();abort_unless($form,404);return view('public.form',compact('form'));}
 public function submitForm(Request $r,string $slug){$form=DB::table('web_forms')->where('slug',$slug)->where('status','published')->whereNull('deleted_at')->first();abort_unless($form,404);if($r->filled('website_confirm'))return redirect()->back();$d=$r->validate(['name'=>'required|string|max:255','email'=>'required|email|max:255','phone'=>'nullable|string|max:80','company_name'=>'nullable|string|max:255']);app(CrmService::class)->save('leads',(int)$form->tenant_id,$d+['status'=>'new','source'=>'web-form:'.$slug],null,null);return view('public.thanks',['message'=>$form->success_message?:'Thank you. We received your request.']);}
}
