@extends('layouts.app')

@section('title','Search results')

@section('content')<div class="crm-page-intro"><div><div class="eyebrow">GLOBAL SEARCH</div><h1>Results for “{{ $q }}”</h1><p>Matching contacts, companies, opportunities, leads and support tickets.</p></div></div><div class="crm-card"><div class="list-group list-group-flush">

@forelse($out as $r)<a href="{{ route('entities.show',[$r['entity'],$r['id']]) }}" class="list-group-item list-group-item-action d-flex justify-content-between"><span><strong>{{ $r['name'] }}</strong><small class="ms-2 text-muted">{{ Str::headline($r['entity']) }}</small></span><i class="bi bi-arrow-up-right"></i></a>

@empty<div class="empty-state">No matching records.</div>

@endforelse</div></div>

@endsection
