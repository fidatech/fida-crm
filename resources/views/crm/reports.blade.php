@extends('layouts.app')

@section('title','Analytics & reports')

@section('content')
<div class="crm-page-intro"><div><div class="eyebrow">DECISION INTELLIGENCE</div><h1>Analytics & reporting</h1><p>Operational totals are grouped by status. Monetary totals use {{ $tenant->currency }} only, without currency conversion.</p></div></div>
<div class="row g-4">

@foreach([['Deal performance',$byStage],['Support performance',$byTicket],['Invoice performance',$invoices],['Marketing delivery',$campaigns]] as $report)<div class="col-xl-6"><section class="crm-card"><div class="crm-card-header"><h3>{{ $report[0] }}</h3><span class="crm-status">Live database</span></div><table class="table crm-table"><thead><tr><th>Status</th><th>Count</th><th>Amount if applicable</th></tr></thead><tbody>

@forelse($report[1] as $row)<tr><td><span class="crm-status">{{ Str::headline($row->status?:'Unknown') }}</span></td><td>{{ number_format($row->count) }}</td><td>{{ isset($row->amount)?($tenant->currency.' '.number_format((float)$row->amount,2)):'—' }}</td></tr>

@empty<tr><td colspan="3" class="empty-state">No activity recorded.</td></tr>

@endforelse</tbody></table></section></div>

@endforeach</div>

@endsection
