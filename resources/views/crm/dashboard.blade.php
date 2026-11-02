@extends('layouts.app')
@section('title','Overview')
@section('content')
<div class="hub-page-head"><div><div class="hub-breadcrumb">Home <i class="bi bi-chevron-right"></i> Overview</div><h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ explode(' ',auth()->user()->name)[0] }}.</h1><p>See what needs attention across your customer relationships and pipeline.</p></div><div class="hub-page-actions"><a href="{{ route('reports') }}" class="hub-button hub-button-quiet"><i class="bi bi-bar-chart-line"></i> View reports</a>@if(\App\Services\WorkspaceContext::allows('records.deals.create'))<a href="{{ route('entities.create','deals') }}" class="hub-button hub-button-primary"><i class="bi bi-plus-lg"></i> New deal</a>@endif</div></div>
<div class="hub-dashboard-intro"><div><span class="hub-dash-date"><i class="bi bi-calendar3"></i> {{ now()->format('l, F j, Y') }}</span><h2>Your work, all in one place.</h2><p>Track your opportunities, manage customer conversations and prioritize next actions.</p></div><a href="{{ route('board') }}" class="hub-button hub-button-primary">Open sales workspace <i class="bi bi-arrow-up-right"></i></a></div>
@if(\App\Services\WorkspaceContext::allows('discovery.view'))
<div class="crm-card p-3 mb-4 d-flex flex-row align-items-center justify-content-between gap-3"><div><small class="text-muted">PROSPECT INTELLIGENCE</small><h3 class="h6 mt-2 mb-1">{{ $prospectQueue }} business prospects awaiting review</h3><p class="small text-muted mb-0">Source-backed searches, duplicate checks and approval before import.</p></div><a class="hub-button hub-button-primary" href="{{ route('discovery.index') }}"><i class="bi bi-bullseye"></i> Open prospecting</a></div>
@endif
<div class="hub-metric-grid">
    @foreach([
        ['Contacts',$stats['contacts'],'contacts','bi-people'],
        ['Companies',$stats['companies'],'companies','bi-buildings'],
        ['Deals',$stats['deals'],'deals','bi-graph-up-arrow'],
        ['Open tickets',$stats['tickets'],'tickets','bi-headset']
    ] as $metric)
        <a class="hub-metric" href="{{ route('entities.index',$metric[2]) }}"><div><span>{{ $metric[0] }}</span><i class="bi {{ $metric[3] }}"></i></div><strong>{{ number_format($metric[1]) }}</strong><small>View {{ strtolower($metric[0]) }} <i class="bi bi-arrow-up-right"></i></small></a>
    @endforeach
</div>
<div class="hub-revenue-row"><a href="{{ route('board') }}" class="hub-revenue-stat"><div><i class="bi bi-cash-stack"></i><span>Open deal value</span></div><strong>{{ $tenant->currency }} {{ number_format((float)$dealTotal,2) }}</strong><small>Active deals in your workspace base currency</small></a><a href="{{ route('entities.index','tasks') }}" class="hub-revenue-stat"><div><i class="bi bi-check2-square"></i><span>Tasks requiring action</span></div><strong>{{ number_format($openTasks) }}</strong><small>Across your workspace</small></a><a href="{{ route('sales.index','invoice') }}" class="hub-revenue-stat"><div><i class="bi bi-clock-history"></i><span>Overdue invoices</span></div><strong>{{ $tenant->currency }} {{ number_format((float)$due,2) }}</strong><small>Unpaid balance in base currency</small></a></div>
<section class="hub-priorities"><div class="hub-card-head"><div><h2><i class="bi bi-magic me-2"></i>Smart priorities <span>{{ $priority->count() }}</span></h2><p>Actionable reminders based on overdue tasks, past-close-date deals and high-scoring leads</p></div></div>
    @if($priority->isEmpty())
        <div class="hub-priority-empty"><i class="bi bi-check-circle-fill"></i> No urgent priorities detected by these rules. <a href="{{ route('entities.create','tasks') }}">Plan your next task</a></div>
    @else
        <div class="hub-priority-items">
        @foreach($priority->take(6) as $attention)
            <a href="{{ $attention['url'] }}" class="hub-priority-item"><span class="hub-priority-icon"><i class="bi {{ $attention['icon'] }}"></i></span><span class="hub-priority-copy"><small>{{ $attention['type'] }}</small><strong>{{ $attention['title'] }}</strong><em>{{ $attention['detail'] }}</em></span><i class="bi bi-arrow-up-right ms-auto"></i></a>
        @endforeach
        </div>
    @endif
</section>
<div class="hub-dashboard-columns"><section class="hub-dashboard-panel"><div class="hub-card-head"><div><h2>Recent opportunities</h2><p>Keep your most recent deals moving forward</p></div><a href="{{ route('entities.index','deals') }}">View all <i class="bi bi-arrow-right"></i></a></div><div class="table-responsive"><table class="table hub-record-table"><thead><tr><th>Deal</th><th>Stage</th><th>Amount</th></tr></thead><tbody>
    @forelse($deals as $deal)
        <tr><td><a href="{{ route('entities.show',['deals',$deal->id]) }}" class="hub-dashboard-deal">{{ $deal->title }}</a></td><td><span class="hub-deal-status">{{ Str::headline($deal->status ?? 'open') }}</span></td><td>{{ $deal->currency }} {{ number_format((float)$deal->amount,2) }}</td></tr>
    @empty
        <tr><td colspan="3" class="hub-dashboard-empty"><p>No deals yet.</p><a href="{{ route('entities.create','deals') }}">Create your first deal</a></td></tr>
    @endforelse
    </tbody></table></div></section>
    <section class="hub-dashboard-panel"><div class="hub-card-head"><div><h2>Next actions</h2><p>Tasks and follow-ups that need attention</p></div><a href="{{ route('entities.create','tasks') }}"><i class="bi bi-plus-lg"></i> Add task</a></div><div class="hub-next-actions">
        @forelse($tasks as $task)
            <a class="hub-next-task" href="{{ route('entities.show',['tasks',$task->id]) }}"><span class="hub-task-check"><i class="bi bi-circle"></i></span><div><strong>{{ $task->title }}</strong><small>{{ $task->due_at ? \Illuminate\Support\Carbon::parse($task->due_at)->format('M j, g:i A') : 'No due date' }}</small></div><i class="bi bi-chevron-right ms-auto"></i></a>
        @empty
            <div class="hub-dashboard-empty"><i class="bi bi-check-circle"></i><p>Nothing urgent in your task list.</p><a href="{{ route('entities.create','tasks') }}">Create a task</a></div>
        @endforelse
    </div></section></div>
@endsection
