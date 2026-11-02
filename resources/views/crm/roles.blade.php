@extends('layouts.app')
@section('title','Roles & permissions')
@section('content')
<div class="hub-page-head"><div><div class="hub-breadcrumb">Settings <i class="bi bi-chevron-right"></i> Access control</div><h1>Roles & permissions</h1><p>Every permission is enforced on the server and isolated to this workspace.</p></div><a class="hub-button hub-button-quiet" href="{{ route('workspace.settings') }}"><i class="bi bi-arrow-left"></i> Team settings</a></div>
@php
    $permissionGroups = config('crm_access.groups');
    $grantedMap = $permissions;
@endphp
<div class="row g-4">
 <div class="col-xl-8">
  @foreach($roles as $role)
   @php
       $granted = collect($grantedMap->get($role->id, collect()))->pluck('permission')->all();
       $seatCount = $members->filter(fn($m) => (int)$m->role_id === (int)$role->id)->count();
   @endphp
   <details class="crm-card crm-permission-card mb-3" @if(!$role->protected && request('role') == $role->id) open @endif>
    <summary class="crm-card-header crm-role-summary"><div><h3><i class="bi bi-shield-lock me-2"></i>{{ $role->name }} <span class="crm-status ms-2">{{ $seatCount }} members</span></h3><small>{{ $role->protected ? 'Built-in role · immutable template' : 'Custom workspace role' }} · {{ count($granted) }} permissions</small></div><i class="bi bi-chevron-down"></i></summary>
    <form method="POST" action="{{ route('roles.update',$role->id) }}" data-smart-form class="p-4">
     @csrf
     @method('PUT')
     <label class="form-label">Role name</label><input class="form-control crm-input mb-3" name="name" value="{{ $role->name }}" maxlength="100" required @if($role->protected) disabled @endif>
     @foreach($permissionGroups as $group => $entries)
      <details class="crm-permission-group mb-3"><summary>{{ $group }} <small class="text-muted">{{ count(array_intersect(array_keys($entries),$granted)) }} / {{ count($entries) }}</small></summary><div class="crm-permission-grid">
       @foreach($entries as $permission => $label)
        <label class="crm-permission"><input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission,$granted,true)) @if($role->protected) disabled @endif><span>{{ $label }}</span></label>
       @endforeach
      </div></details>
     @endforeach
     @if(!$role->protected && \App\Services\WorkspaceContext::allows('roles.manage'))
      <div class="d-flex flex-wrap gap-2"><button class="hub-button hub-button-primary">Save role</button></div>
     @endif
    </form>
    @if(!$role->protected && \App\Services\WorkspaceContext::allows('roles.manage'))
     <form method="POST" action="{{ route('roles.delete',$role->id) }}" class="px-4 pb-4" data-smart-form data-confirm="Delete this role? Only empty roles can be deleted.">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete role</button></form>
    @endif
   </details>
  @endforeach
 </div>
 <div class="col-xl-4"><div class="crm-card crm-sticky-panel"><div class="crm-card-header"><h3>Create a custom role</h3></div><form action="{{ route('roles.store') }}" method="POST" class="p-4" data-smart-form>
  @csrf
  @if(\App\Services\WorkspaceContext::allows('roles.manage'))
   <label class="form-label">Role title</label><input name="name" class="form-control crm-input mb-3" placeholder="e.g. Account Executive" required maxlength="100">
   <p class="small text-muted">Choose access permissions below. New custom roles start without permissions.</p>
   @foreach($permissionGroups as $group => $entries)
    <details class="crm-permission-group mb-2"><summary>{{ $group }}</summary><div class="crm-permission-grid">
    @foreach($entries as $permission => $label)
      <label class="crm-permission"><input type="checkbox" name="permissions[]" value="{{ $permission }}"><span>{{ $label }}</span></label>
    @endforeach
    </div></details>
   @endforeach
   <button class="hub-button hub-button-primary mt-3 w-100">Create role</button>
  @else<p class="small">Your current role can view access policies. An owner must manage custom roles.</p>@endif
 </form></div></div>
</div>
@endsection
