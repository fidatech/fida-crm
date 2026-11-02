<?php
namespace App\Http\Controllers;
use App\Services\CrmService;
use App\Services\WorkspaceContext as W;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class TicketController extends Controller {
 public function reply(Request $r,int $id){W::authorize('tickets.reply');app(CrmService::class)->query('tickets',W::id())->findOrFail($id);$d=$r->validate(['message'=>'required|string|max:20000','internal'=>'sometimes|boolean']);DB::table('ticket_messages')->insert(['tenant_id'=>W::id(),'ticket_id'=>$id,'user_id'=>auth()->id(),'message'=>$d['message'],'internal'=>$r->boolean('internal'),'created_at'=>now(),'updated_at'=>now()]);app(CrmService::class)->activity(W::id(),auth()->id(),'tickets',$id,'reply','Reply added');return back()->with('success','Reply recorded.');}
}
