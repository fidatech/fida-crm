@extends('layouts.app')

@section('title',$doc->number)

@section('content')
<div class="crm-page-intro"><div><div class="eyebrow"><a href="{{ route('sales.index',$kind) }}">{{ strtoupper($kind) }}S</a> / {{ $doc->number }}</div><h1>{{ $doc->title }}</h1><p>{{ $doc->number }} · Issued {{ $doc->issued_on }} · <span class="crm-status">{{ Str::headline($doc->status) }}</span></p></div><div class="intro-actions"><button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>

@if($doc->status==='draft')<a href="{{ route('sales.edit',[$kind,$doc->id]) }}" class="btn btn-fida-amber">Edit document</a>

@endif</div></div>
<div class="row g-4"><div class="col-lg-8"><div class="crm-card crm-invoice"><div class="crm-invoice-banner"><div><div class="eyebrow">FIDA CRM · {{ strtoupper($kind) }}</div><h2>{{ $doc->number }}</h2></div><span class="crm-status">{{ strtoupper($doc->status) }}</span></div><div class="row g-3 p-4"><div class="col-6"><small>Issue date</small><h6>{{ $doc->issued_on }}</h6></div><div class="col-6"><small>Due / expiry date</small><h6>{{ $doc->due_on?:'—' }}</h6></div></div><div class="table-responsive"><table class="table crm-table"><thead><tr><th>Description</th><th>Qty</th><th>Unit price</th><th>Discount</th><th>Tax</th><th>Total</th></tr></thead><tbody>

@foreach($lines as $line)<tr><td>{{ $line->description }}</td><td>{{ $line->quantity }}</td><td>{{ $doc->currency }} {{ number_format((float)$line->unit_price,2) }}</td><td>{{ $line->discount_percent }}%</td><td>{{ $line->tax_percent }}%</td><td>{{ $doc->currency }} {{ number_format((float)$line->line_total,2) }}</td></tr>

@endforeach</tbody></table></div><div class="crm-invoice-totals"><div>Subtotal <strong>{{ $doc->currency }} {{ number_format((float)$doc->subtotal,2) }}</strong></div><div>Discount <strong>-{{ $doc->currency }} {{ number_format((float)$doc->discount_total,2) }}</strong></div><div>Tax <strong>{{ $doc->currency }} {{ number_format((float)$doc->tax_total,2) }}</strong></div><div class="grand">Total <strong>{{ $doc->currency }} {{ number_format((float)$doc->total,2) }}</strong></div>

@if($kind==='invoice')<div>Paid <strong>{{ $doc->currency }} {{ number_format((float)$doc->amount_paid,2) }}</strong></div><div class="grand">Balance <strong>{{ $doc->currency }} {{ number_format((float)($doc->total-$doc->amount_paid),2) }}</strong></div>

@endif</div>

@if($doc->notes)<div class="p-4 border-top"><strong>Notes</strong><p>{{ $doc->notes }}</p></div>

@endif</div></div>
<div class="col-lg-4"><div class="crm-card mb-4"><div class="crm-card-header"><h3>Document actions</h3></div><div class="p-4">

@if($kind==='quote' && $doc->status==='accepted')<form method="POST" action="{{ route('sales.convert',$doc->id) }}" class="mb-3" data-smart-form>

@csrf<button class="btn btn-fida-amber w-100">Convert to invoice</button></form>

@endif

@if(in_array($doc->status,['draft','sent','partial']))<form method="POST" action="{{ route('sales.transition',[$kind,$doc->id]) }}" data-smart-form>

@csrf<label class="form-label">Change status</label><div class="d-flex gap-2"><select name="status" class="form-select crm-input">

@foreach(($kind==='quote'?($doc->status==='draft'?['sent','void']:['accepted','rejected','void']):($doc->status==='draft'?['sent','void']:['void'])) as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>

@endforeach</select><button class="btn btn-outline-secondary">Update</button></div></form>

@endif</div></div>

@if($kind==='invoice')<div class="crm-card"><div class="crm-card-header"><h3>Payment history</h3></div><div class="p-4">

@forelse($payments as $payment)<div class="crm-payment"><strong>{{ $doc->currency }} {{ number_format((float)$payment->amount,2) }}</strong><small>{{ $payment->method }} · {{ $payment->paid_on }}</small></div>

@empty<p class="small text-muted">No payments recorded yet.</p>

@endforelse

@if(in_array($doc->status,['sent','partial']) && $doc->total>$doc->amount_paid)<hr><h6>Record payment</h6><form action="{{ route('sales.payment',$doc->id) }}" method="POST" data-smart-form>

@csrf<label class="form-label">Amount ({{ $doc->currency }})</label><input class="form-control crm-input mb-2" name="amount" type="number" min="0.01" step="0.01" max="{{ number_format((float)($doc->total-$doc->amount_paid),2,'.','') }}" required><label class="form-label">Method</label><select class="form-select crm-input mb-2" name="method">

@foreach(['bank','card','cash','wire','other'] as $method)<option>{{ $method }}</option>

@endforeach</select><label class="form-label">Reference</label><input class="form-control crm-input mb-2" name="reference"><label class="form-label">Date</label><input class="form-control crm-input mb-3" type="date" name="paid_on" value="{{ date('Y-m-d') }}" required><button class="btn btn-fida-amber w-100">Record payment</button></form>

@endif</div></div>

@endif</div></div>

@endsection
