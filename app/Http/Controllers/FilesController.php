<?php
namespace App\Http\Controllers;
use App\Services\CrmService;
use App\Services\WorkspaceContext as W;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class FilesController extends Controller {
 public function upload(Request $r,string $entity,int $id){W::authorize('attachments.manage');app(CrmService::class)->query($entity,W::id())->findOrFail($id);$r->validate(['file'=>'required|file|max:10240']);$file=$r->file('file');$path=$file->store('tenant-'.W::id().'/attachments','local');DB::table('attachments')->insert(['tenant_id'=>W::id(),'entity'=>$entity,'entity_id'=>$id,'original_name'=>$file->getClientOriginalName(),'path'=>$path,'mime'=>$file->getMimeType()?:'application/octet-stream','size'=>$file->getSize(),'created_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]);return back()->with('success','File uploaded securely.');}
 public function download(int $id){W::authorize('attachments.manage');$file=DB::table('attachments')->where('tenant_id',W::id())->where('id',$id)->first();abort_unless($file,404);app(CrmService::class)->query($file->entity,W::id())->findOrFail($file->entity_id);abort_unless(Storage::disk('local')->exists($file->path),404);return Storage::disk('local')->download($file->path,$file->original_name);}
}
