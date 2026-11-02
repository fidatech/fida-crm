@extends('layouts.app')

@section('title','Platform console')

@section('content')
<div class="crm-page-intro"><div><div class="eyebrow">SAAS OPERATIONS</div><h1>Platform console</h1><p>Tenant overview, subscriptions, plans and workspace status.</p></div></div><div class="crm-kpi-grid">

@foreach($stats as $title=>$value)<div class="crm-kpi"><div class="crm-kpi-top">{{ Str::headline($title) }}</div><div class="crm-kpi-big">{{ $value }}</div></div>

@endforeach</div><div class="row g-4"><div class="col-xl-8"><div class="crm-card"><div class="crm-card-header"><h3>Workspaces</h3></div><div class="table-responsive"><table class="table crm-table"><thead><tr><th>Company</th><th>Slug</th><th>Status</th><th>Change status</th></tr></thead><tbody>

@foreach($tenants as $tenant)<tr><td>{{ $tenant->name }}</td><td>{{ $tenant->slug }}</td><td><span class="crm-status">{{ $tenant->status }}</span></td><td><form method="POST" action="{{ route('platform.status',$tenant->id) }}" class="d-flex gap-2" data-smart-form>

@csrf<select class="form-select form-select-sm" name="status">

@foreach(['trial','active','suspended'] as $status)<option 

@selected($tenant->status===$status)>{{ $status }}</option>

@endforeach</select><button class="btn btn-sm btn-outline-secondary">Save</button></form></td></tr>

@endforeach</tbody></table></div><div class="crm-card-footer">{{ $tenants->links() }}</div></div></div><div class="col-xl-4"><div class="crm-card"><div class="crm-card-header"><h3>Create plan</h3></div><form method="POST" action="{{ route('platform.plans') }}" class="p-4" data-smart-form>

@csrf

@foreach(['name'=>'Name','slug'=>'Slug','monthly_cents'=>'Price in USD cents','seat_limit'=>'Seat limit','stripe_price_id'=>'Stripe price ID'] as $key=>$label)<label class="form-label">{{ $label }}</label><input class="form-control crm-input mb-3" name="{{ $key }}" 

@if(in_array($key,['monthly_cents','seat_limit'])) type="number" min="0" 

@endif 

@if($key!=='stripe_price_id') required @endif>

@endforeach<button class="btn btn-fida-amber w-100">Save plan</button></form></div></div></div>

@endsection
