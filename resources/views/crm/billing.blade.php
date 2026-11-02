@extends('layouts.app')

@section('title','Subscriptions')

@section('content')
<div class="crm-page-intro"><div><div class="eyebrow">SAAS BILLING</div><h1>Plans & subscriptions</h1><p>Manage your workspace subscription and seats.</p></div></div><div class="alert alert-light">Current subscription: <strong>{{ ucfirst($current?->status??'trial') }}</strong>. Paid checkout requires Stripe keys and configured plan price IDs.</div><div class="row g-4">

@forelse($plans as $plan)<div class="col-md-4"><div class="crm-card p-4"><div class="eyebrow">FIDA CRM</div><h3 class="mt-2">{{ $plan->name }}</h3><h2 class="mb-3">${{ number_format($plan->monthly_cents/100,2) }}<small class="fs-6 text-muted"> / month</small></h2><p>{{ $plan->seat_limit }} included team seats</p><form action="{{ route('billing.checkout',$plan->id) }}" method="POST" data-smart-form>

@csrf<button class="btn btn-fida-amber w-100">Choose plan</button></form></div></div>

@empty<div class="col-12"><div class="crm-card p-5 text-center">Plans are not published yet. Contact the platform operator.</div></div>

@endforelse</div>

@endsection
