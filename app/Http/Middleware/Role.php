<?php
namespace App\Http\Middleware;
use Closure;
use App\Services\WorkspaceContext as W;
class Role {public function handle($request,Closure $next,string ...$roles){abort_unless(in_array(W::role(),$roles,true),403);return $next($request);}}
