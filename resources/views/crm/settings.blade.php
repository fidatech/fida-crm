@extends('layouts.app')

@section('title','Workspace settings')

@section('content')
<div class="crm-page-intro"><div><div class="eyebrow">ADMINISTRATION</div><h1>Workspace settings</h1><p>Manage your company details, teammates, invitations and API access.</p></div></div>
<div class="d-flex flex-wrap gap-2 mb-4"><a class="hub-button hub-button-quiet" href="{{ route('roles.index') }}"><i class="bi bi-shield-lock"></i> Roles & permissions</a><a class="hub-button hub-button-quiet" href="{{ route('currencies.index') }}"><i class="bi bi-currency-exchange"></i> Currency management</a><a class="hub-button hub-button-quiet" href="{{ route('discovery.index') }}"><i class="bi bi-bullseye"></i> Prospect discovery</a></div><div class="row g-4"><div class="col-xl-7"><div class="crm-card mb-4"><div class="crm-card-header"><h3>Organization profile</h3></div><form class="p-4" action="{{ route('workspace.update') }}" method="POST" data-smart-form>

@csrf

@method('PUT')<div class="row g-3"><div class="col-md-6"><label class="form-label">Workspace name</label><input class="form-control crm-input" name="name" value="{{ $tenant->name }}" required></div><div class="col-md-6"><label class="form-label">Base currency</label><div class="d-flex gap-2 align-items-center"><strong>{{ $tenant->currency }}</strong><a class="hub-button hub-button-quiet" href="{{ route('currencies.index') }}">Manage currencies</a></div></div><div class="col-md-6"><label class="form-label">Timezone</label><select class="form-select crm-input" name="timezone">

@foreach(['UTC','America/New_York','America/Chicago','America/Denver','America/Los_Angeles','Europe/London','Asia/Kabul'] as $tz)<option 

@selected($tz===$tenant->timezone)>{{ $tz }}</option>

@endforeach</select></div><div class="col-12"><button class="btn btn-fida-amber">Save settings</button></div></div></form></div>
<div class="crm-card"><div class="crm-card-header"><h3>Team members</h3><span class="crm-status">{{ $members->count() }} seats</span></div><div class="table-responsive"><table class="table crm-table"><thead><tr><th>Name</th><th>Email</th><th>Role</th></tr></thead><tbody>

@foreach($members as $member)<tr><td><strong>{{ $member->name }}</strong></td><td>{{ $member->email }}</td><td>

@if(\App\Services\WorkspaceContext::allows('team.manage') && $member->role!=='owner' && $member->id!==auth()->id())<form action="{{ route('workspace.member.role',$member->id) }}" method="POST" class="d-flex gap-2" data-smart-form>

@csrf

@method('PATCH')<select name="role" class="form-select form-select-sm" style="width:130px">

@foreach($availableRoles->where('slug','!=','owner') as $workspaceRole)<option value="{{ $workspaceRole->slug }}"
 

@selected($member->role===$workspaceRole->slug)>{{ $workspaceRole->name }}</option>

@endforeach</select><button class="btn btn-sm btn-outline-secondary">Save</button></form>

@else<span class="crm-status">{{ ucfirst($member->role) }}</span>

@endif</td></tr>

@endforeach</tbody></table></div></div></div>
<div class="col-xl-5"><div class="crm-card mb-4"><div class="crm-card-header"><h3>Invite teammates</h3></div><form class="p-4" action="{{ route('workspace.invite') }}" method="POST" data-smart-form>

@csrf<label class="form-label">Email address</label><input type="email" name="email" class="form-control crm-input mb-3" required><label class="form-label">Workspace role</label><select name="role" class="form-select crm-input mb-3">

@foreach($availableRoles->where('slug','!=','owner') as $workspaceRole)<option value="{{ $workspaceRole->slug }}">{{ $workspaceRole->name }}</option>

@endforeach</select><button class="btn btn-fida-amber w-100">Send invitation</button><p class="text-muted small mt-3 mb-0">Requires configured email delivery; invitations expire after 7 days.</p></form></div>
<div class="crm-card mb-4"><div class="crm-card-header"><h3>API keys</h3></div><div class="p-4">

@if(session('new_api_token'))<div class="alert alert-warning"><strong>Copy this key now (shown once):</strong><div class="mt-2 text-break user-select-all"><code>{{ session('new_api_token') }}</code></div></div>

@endif

@foreach($keys as $key)<div class="d-flex justify-content-between py-2 align-items-center"><div><strong>{{ $key->name }}</strong><small class="d-block text-muted">Last used: {{ $key->last_used_at?:'Never' }}</small></div><form method="POST" action="{{ route('workspace.keys.delete',$key->id) }}" data-smart-form>

@csrf

@method('DELETE')<button class="btn btn-sm btn-outline-danger">Revoke</button></form></div>

@endforeach<form action="{{ route('workspace.keys.create') }}" method="POST" class="d-flex gap-2 mt-3" data-smart-form>

@csrf<input name="name" class="form-control crm-input" placeholder="Integration name" required><button class="btn btn-fida-amber">Create</button></form></div></div>
<div class="crm-card"><div class="crm-card-header"><h3>Pending invitations</h3></div><div class="p-3">

@forelse($invitations as $invite)<div class="small py-2">{{ $invite->email }} <span class="crm-status">{{ $invite->role }}</span></div>

@empty<p class="small text-muted">No pending invitations.</p>

@endforelse</div></div></div></div>

@endsection
