@extends('layouts.app')
@section('title', $def['label'])
@section('content')
@php
    $statusField = collect($def['fields'])->first(fn ($f) => $f[0] === 'status');
    $statuses = $statusField && str_starts_with($statusField[1], 'select:') ? explode('|', substr($statusField[1], 7)) : [];
@endphp
<div class="hub-page-head">
    <div><div class="hub-breadcrumb">{{ $def['group'] }} <i class="bi bi-chevron-right"></i> {{ $def['label'] }}</div><h1>{{ $def['label'] }} <span class="hub-counter">{{ number_format($rows->total()) }}</span></h1><p>All {{ strtolower($def['label']) }} in your workspace, in one place.</p></div>
    <div class="hub-page-actions">
        @if(\App\Services\WorkspaceContext::allows('records.'.$entity.'.export'))<a href="{{ route('entities.export',$entity) }}" class="hub-button hub-button-quiet"><i class="bi bi-download"></i> Export</a>@endif
        @if(\App\Services\WorkspaceContext::allows('records.'.$entity.'.create'))<a href="{{ route('entities.create',$entity) }}" class="hub-button hub-button-primary"><i class="bi bi-plus-lg"></i> Create {{ Str::singular($def['label']) }}</a>@endif
    </div>
</div>
<div class="hub-index-surface">
    <div class="hub-list-tabs"><span class="hub-tab is-selected"><i class="bi bi-table"></i> All {{ strtolower($def['label']) }}</span>
        @if($entity === 'deals')<a href="{{ route('board') }}" class="hub-tab"><i class="bi bi-kanban"></i> Board view</a>@endif
        <span class="hub-list-help">{{ $rows->total() }} records</span>
    </div>
    <form method="GET" action="{{ route('entities.index',$entity) }}" class="hub-list-toolbar">
        <div class="hub-filter-search"><i class="bi bi-search"></i><input type="search" name="search" aria-label="Search records" placeholder="Search {{ strtolower($def['label']) }}..." value="{{ request('search') }}"></div>
        @if(count($statuses))
            <label class="hub-filter-field"><i class="bi bi-funnel"></i><span class="visually-hidden">Status</span><select name="status" class="hub-select-minimal" aria-label="Filter by status"><option value="">All statuses</option>
                @foreach($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ Str::headline($status) }}</option>@endforeach
            </select></label>
        @endif
        <button class="hub-button hub-button-quiet" type="submit">Apply</button>
        @if(request()->filled('search') || request()->filled('status'))<a href="{{ route('entities.index',$entity) }}" class="hub-button hub-button-clear">Reset</a>@endif
        <div class="hub-toolbar-spacer"></div>
        @if(\App\Services\WorkspaceContext::allows('records.'.$entity.'.import') || \App\Services\WorkspaceContext::allows('records.'.$entity.'.export'))<div class="dropdown hub-import-dropdown"><button class="hub-button hub-button-quiet dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i> More</button>
            <div class="dropdown-menu dropdown-menu-end">@if(\App\Services\WorkspaceContext::allows('records.'.$entity.'.export'))<a href="{{ route('entities.export',$entity) }}" class="dropdown-item"><i class="bi bi-download me-2"></i>Export CSV</a>@endif @if(\App\Services\WorkspaceContext::allows('records.'.$entity.'.import'))<label for="hub-import-{{ $entity }}" class="dropdown-item" role="button"><i class="bi bi-upload me-2"></i>Import CSV</label>@endif</div>
        </div>@endif
    </form>
    @if(\App\Services\WorkspaceContext::allows('records.'.$entity.'.import'))<form method="POST" action="{{ route('entities.import',$entity) }}" enctype="multipart/form-data" class="hub-hidden-import" data-auto-import data-smart-form>
        @csrf
        <input id="hub-import-{{ $entity }}" type="file" name="file" accept=".csv,text/csv" aria-label="Import CSV">
    </form>@endif
    <div class="table-responsive hub-table-scroll"><table class="table hub-record-table"><thead><tr><th class="hub-name-col">{{ Str::singular($def['label']) }}</th>
        @foreach(array_slice($def['fields'],1,4) as $f)<th>{{ Str::headline($f[0]) }}</th>@endforeach
        <th>Updated</th><th class="hub-actions-col">Actions</th></tr></thead><tbody>
        @forelse($rows as $record)
            <tr><td><a class="hub-record-name" href="{{ route('entities.show',[$entity,$record->id]) }}"><span class="hub-initials">{{ strtoupper(mb_substr((string)($record->{$def['title']} ?? 'R'),0,2)) }}</span><span><strong>{{ $record->{$def['title']} }}@if($entity === 'contacts') {{ $record->last_name }}@endif</strong><small>#{{ $record->id }}</small></span></a></td>
                @foreach(array_slice($def['fields'],1,4) as $f)
                    <td>@if($f[1] === 'bool')<span class="hub-pill">{{ $record->{$f[0]} ? 'Yes' : 'No' }}</span>@else{{ Str::limit((string)($record->{$f[0]} ?? ''),55) ?: '—' }}@endif</td>
                @endforeach
                <td class="hub-table-muted">{{ $record->updated_at?->format('M j, Y') }}</td>
                <td>@if(\App\Services\WorkspaceContext::allows('records.'.$entity.'.update'))<a href="{{ route('entities.edit',[$entity,$record->id]) }}" class="hub-icon-button" aria-label="Edit {{ $record->{$def['title']} }}"><i class="bi bi-pencil-square"></i></a>@endif</td>
            </tr>
        @empty
            <tr><td class="hub-table-empty" colspan="{{ count(array_slice($def['fields'],1,4)) + 3 }}"><i class="bi bi-inbox"></i><h3>No {{ strtolower($def['label']) }} found</h3><p>Try clearing the filters or create your first record.</p>@if(\App\Services\WorkspaceContext::allows('records.'.$entity.'.create'))<a href="{{ route('entities.create',$entity) }}" class="hub-button hub-button-primary">Add {{ Str::singular($def['label']) }}</a>@endif</td></tr>
        @endforelse
    </tbody></table></div>
    <div class="hub-list-footer"><span>Showing {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} of {{ $rows->total() }}</span>{{ $rows->links() }}</div>
</div>
@endsection
