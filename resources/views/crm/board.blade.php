@extends('layouts.app')

@section('title','Pipeline board')

@section('content')
<div class="crm-page-intro"><div><div class="eyebrow">SALES / PIPELINE</div><h1>Opportunity board</h1><p>Move deals between your pipeline stages. Changes are saved immediately.</p></div><a href="{{ route('entities.create','deals') }}" class="btn btn-fida-amber"><i class="bi bi-plus-lg me-1"></i>New deal</a></div>

@if(!$pipeline || $stages->isEmpty())<div class="crm-card p-5 text-center"><h3>Set up your first pipeline</h3><p>Create a pipeline and add stages to begin moving deals.</p><div><a href="{{ route('entities.create','pipelines') }}" class="btn btn-fida-amber">Create pipeline</a><a href="{{ route('entities.create','stages') }}" class="btn btn-outline-secondary">Add stage</a></div></div>

@else
<div class="crm-board" data-board-url="{{ url('/pipeline') }}">

@foreach($stages as $stage)<div class="crm-board-column" data-stage="{{ $stage->id }}"><div class="crm-board-heading"><div><span class="stage-pip"></span><strong>{{ $stage->name }}</strong></div><span>{{ $deals->where('stage_id',$stage->id)->count() }}</span></div><div class="crm-board-amount">{{ \App\Services\WorkspaceContext::tenant()->currency }} {{ number_format((float)$deals->where('stage_id',$stage->id)->where('currency',\App\Services\WorkspaceContext::tenant()->currency)->sum('amount'),2) }}</div><div class="crm-board-cards" data-dropzone data-stage="{{ $stage->id }}">

@forelse($deals->where('stage_id',$stage->id) as $deal)<div class="crm-deal-card" draggable="true" data-deal="{{ $deal->id }}"><a href="{{ route('entities.show',['deals',$deal->id]) }}">{{ $deal->title }}</a><p>{{ $deal->currency }} {{ number_format((float)$deal->amount,2) }}</p><small><i class="bi bi-calendar me-1"></i>{{ $deal->expected_close_at ?: 'No close date' }}</small></div>

@empty<div class="board-placeholder">Drop deals here</div>

@endforelse</div></div>

@endforeach</div>

@endif

@endsection
