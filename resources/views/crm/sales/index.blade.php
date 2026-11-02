@extends('layouts.app')

@section('title',$kind==='quote'?'Quotations':'Invoices')

@section('content')
<div class="crm-page-intro"><div><div class="eyebrow">REVENUE OPERATIONS</div><h1>{{ $kind==='quote'?'Quotations':'Invoices' }}</h1><p>Create professional documents, manage statuses and record customer payments.</p></div><a href="{{ route('sales.create',$kind) }}" class="btn btn-fida-amber"><i class="bi bi-plus-lg me-1"></i>New {{ $kind }}</a></div>
<div class="crm-card"><div class="table-responsive"><table class="table crm-table"><thead><tr><th>Document</th><th>Issued</th><th>Due date</th><th>Status</th><th>Total</th><th>Balance</th></tr></thead><tbody>

@forelse($docs as $doc)<tr><td><a href="{{ route('sales.show',[$kind,$doc->id]) }}" class="fw-bold">{{ $doc->number }}</a><small class="d-block text-muted">{{ $doc->title }}</small></td><td>{{ $doc->issued_on }}</td><td>{{ $doc->due_on?:'—' }}</td><td><span class="crm-status">{{ Str::headline($doc->status) }}</span></td><td>{{ $doc->currency }} {{ number_format((float)$doc->total,2) }}</td><td>{{ $doc->currency }} {{ number_format((float)($doc->total-$doc->amount_paid),2) }}</td></tr>

@empty<tr><td colspan="6" class="empty-state">No documents. Create your first {{ $kind }}.</td></tr>

@endforelse</tbody></table></div><div class="crm-card-footer">{{ $docs->links() }}</div></div>

@endsection
