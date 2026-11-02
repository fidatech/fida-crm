@extends('layouts.app')
@section('title', $record->{$def['title']})
@section('content')
<div class="hub-record-header"><div class="hub-record-title-row"><a href="{{ route('entities.index',$entity) }}" class="hub-record-back" aria-label="Return to {{ $def['label'] }}"><i class="bi bi-arrow-left"></i></a><span class="hub-record-large-avatar">{{ strtoupper(mb_substr((string)$record->{$def['title']},0,2)) }}</span><div class="hub-record-heading"><div class="hub-breadcrumb"><a href="{{ route('entities.index',$entity) }}">{{ $def['label'] }}</a> <i class="bi bi-chevron-right"></i> Record #{{ $record->id }}</div><h1>{{ $record->{$def['title']} }}@if($entity === 'contacts') {{ $record->last_name }}@endif</h1><p><i class="bi bi-clock"></i> Updated {{ $record->updated_at?->diffForHumans() }}</p></div></div>
    <div class="hub-record-actions"><a href="{{ route('entities.edit',[$entity,$record->id]) }}" class="hub-button hub-button-primary"><i class="bi bi-pencil"></i> Edit</a><form method="POST" action="{{ route('entities.destroy',[$entity,$record->id]) }}" data-confirm="Archive this record? This action may affect linked records." data-smart-form>@csrf @method('DELETE')<button class="hub-button hub-button-quiet" type="submit"><i class="bi bi-archive"></i> Archive</button></form></div>
</div>
<div class="hub-record-layout">
    <aside class="hub-record-properties"><section class="hub-record-sidecard"><div class="hub-sidecard-head"><h2>About this {{ Str::singular($def['label']) }}</h2><a href="{{ route('entities.edit',[$entity,$record->id]) }}" aria-label="Edit details"><i class="bi bi-pencil-square"></i></a></div><div class="hub-record-properties-list">
        @foreach($def['fields'] as $field)
            @php
                $propertyName = $field[0];
                $propertyType = $field[1];
                $propertyValue = $record->{$propertyName};
            @endphp
            <div class="hub-property"><small>{{ Str::headline($propertyName) }}</small><div>
                @if($propertyType === 'bool')
                    <span class="hub-tag">{{ $propertyValue ? 'Yes' : 'No' }}</span>
                @elseif($propertyType === 'email' && $propertyValue)
                    <a href="mailto:{{ $propertyValue }}">{{ $propertyValue }}</a>
                @elseif($propertyType === 'url' && $propertyValue)
                    <a href="{{ $propertyValue }}" target="_blank" rel="noopener noreferrer">{{ Str::limit((string)$propertyValue,35) }}</a>
                @else
                    {{ filled($propertyValue) ? $propertyValue : '—' }}
                @endif
            </div></div>
        @endforeach
        @php
            $customFields = app(\App\Services\CrmService::class)->query('custom_fields',\App\Services\WorkspaceContext::id())->where('entity',$entity)->get();
        @endphp
        @foreach($customFields as $custom)
            @php
                $customValue = \Illuminate\Support\Facades\DB::table('custom_field_values')->where('tenant_id',\App\Services\WorkspaceContext::id())->where('field_id',$custom->id)->where('entity',$entity)->where('entity_id',$record->id)->value('value');
            @endphp
            <div class="hub-property"><small>{{ $custom->name }}</small><div>{{ $customValue ?: '—' }}</div></div>
        @endforeach
    </div></section></aside>
    <div class="hub-record-center">
        <div class="hub-record-tabs"><span class="hub-record-tab is-active"><i class="bi bi-clock-history"></i> Activities</span><a href="{{ route('entities.edit',[$entity,$record->id]) }}" class="hub-record-tab"><i class="bi bi-sliders"></i> Properties</a><a href="{{ route('assistant.index') }}" class="hub-record-tab"><i class="bi bi-stars"></i> AI assistant</a></div>
        <div class="hub-action-strip"><a href="#hubNoteComposer" class="hub-action-tile"><span><i class="bi bi-journal-plus"></i></span> Note</a><a href="{{ route('entities.create','tasks') }}" class="hub-action-tile"><span><i class="bi bi-check2-square"></i></span> Task</a><a href="{{ route('entities.create','meetings') }}" class="hub-action-tile"><span><i class="bi bi-calendar-plus"></i></span> Meeting</a><a href="{{ route('entities.create','deals') }}" class="hub-action-tile"><span><i class="bi bi-graph-up-arrow"></i></span> Deal</a></div>
        <section class="hub-activity-card" id="hubNoteComposer"><div class="hub-card-head"><h2>Activity feed</h2><span>{{ $activities->count() }} recent events</span></div><form method="POST" action="{{ route('entities.note',[$entity,$record->id]) }}" data-smart-form class="hub-note-form">@csrf<label class="visually-hidden" for="hubNoteMessage">Write a note</label><textarea id="hubNoteMessage" name="message" class="form-control" rows="3" required placeholder="Write a note about this customer, opportunity, or conversation..."></textarea><div class="hub-note-actions"><small><i class="bi bi-lock"></i> Visible to your workspace team</small><button class="hub-button hub-button-primary" type="submit"><span class="hub-button-spinner"></span><span data-submit-label>Add note</span></button></div></form>
            <div class="hub-timeline">
            @forelse($activities as $activity)
                <article class="hub-activity-item"><span class="hub-activity-icon"><i class="bi {{ $activity->event === 'note' ? 'bi-chat-left-text' : 'bi-activity' }}"></i></span><div class="hub-activity-body"><div><strong>{{ Str::headline($activity->event) }}</strong><time>{{ \Illuminate\Support\Carbon::parse($activity->created_at)->diffForHumans() }}</time></div><p>{{ $activity->message ?: 'Record '.Str::lower(Str::headline($activity->event)) }}</p></div></article>
            @empty
                <div class="hub-empty-timeline"><i class="bi bi-chat-left-dots"></i><h3>Start the conversation</h3><p>No activities yet. Add a note, log a follow-up, or create a task.</p></div>
            @endforelse
            </div>
        </section>
        @if($entity === 'leads' && $record->status !== 'converted')
            <section class="hub-activity-card hub-extra-card"><div class="hub-card-head"><h2>Convert lead</h2></div><form method="POST" action="{{ route('leads.convert',$record->id) }}" class="hub-feature-form" data-smart-form>@csrf<p>Turn this qualified lead into a contact and connect it with a company or deal.</p><label class="form-check"><input class="form-check-input" type="checkbox" name="create_company" value="1" checked> Create a company</label><label class="form-check"><input class="form-check-input" type="checkbox" name="create_deal" value="1" checked> Create a deal</label><button class="hub-button hub-button-primary" type="submit"><span class="hub-button-spinner"></span><span data-submit-label>Convert lead</span></button></form></section>
        @endif
        @if($entity === 'tickets')
            <section class="hub-activity-card hub-extra-card"><div class="hub-card-head"><h2>Ticket conversation</h2></div><div class="hub-feature-form">
                @foreach(\Illuminate\Support\Facades\DB::table('ticket_messages')->where('tenant_id',\App\Services\WorkspaceContext::id())->where('ticket_id',$record->id)->orderBy('id')->get() as $message)
                    <div class="hub-ticket-message"><strong>{{ $message->internal ? 'Internal note' : 'Customer reply' }}</strong><small>{{ $message->created_at }}</small><p>{{ $message->message }}</p></div>
                @endforeach
                <form method="POST" action="{{ route('tickets.reply',$record->id) }}" data-smart-form>@csrf<textarea name="message" class="form-control mb-2" rows="3" placeholder="Add a message..." required></textarea><label class="form-check mb-3"><input type="checkbox" name="internal" value="1" checked class="form-check-input"> Internal note</label><button type="submit" class="hub-button hub-button-primary"><span class="hub-button-spinner"></span><span data-submit-label>Add message</span></button></form>
            </div></section>
        @endif
        @if($entity === 'campaigns')
            <section class="hub-activity-card hub-extra-card"><div class="hub-card-head"><h2>Campaign outreach</h2></div><form method="POST" action="{{ route('campaigns.launch',$record->id) }}" class="hub-feature-form" data-smart-form>@csrf<p>Queue email delivery to eligible subscribers. Outbound email and scheduling must be configured.</p><label class="form-label">Email template</label><select name="template_id" class="form-select hub-enhanced-select mb-3" required><option value="">Choose a template</option>
            @foreach(app(\App\Services\CrmService::class)->query('email_templates',\App\Services\WorkspaceContext::id())->get() as $template)<option value="{{ $template->id }}">{{ $template->name }}</option>@endforeach
            </select><label class="form-label">Audience</label><select name="segment_id" class="form-select hub-enhanced-select mb-3"><option value="">All eligible contacts</option>
            @foreach(app(\App\Services\CrmService::class)->query('segments',\App\Services\WorkspaceContext::id())->get() as $segment)<option value="{{ $segment->id }}">{{ $segment->name }}</option>@endforeach
            </select><button type="submit" class="hub-button hub-button-primary"><span class="hub-button-spinner"></span><span data-submit-label>Queue campaign</span></button></form></section>
        @endif
        @if($entity === 'web_forms' && $record->status === 'published')
            <section class="hub-activity-card hub-extra-card"><div class="hub-feature-form"><strong>Public form URL</strong><p><a href="{{ route('form.public',$record->slug) }}" target="_blank" rel="noopener">{{ route('form.public',$record->slug) }}</a></p></div></section>
        @endif
    </div>
    <aside class="hub-record-associations">
        @if(in_array($entity,['contacts','companies'],true))
            @php
                $associationColumn = $entity === 'companies' ? 'company_id' : 'contact_id';
            @endphp
            @foreach(['deals','tickets','tasks'] as $relatedEntity)
                @php
                    $hasAssociation = collect(config('crm.entities.'.$relatedEntity.'.fields'))->contains(fn ($f) => $f[0] === $associationColumn);
                    $relatedRows = $hasAssociation ? app(\App\Services\CrmService::class)->query($relatedEntity,\App\Services\WorkspaceContext::id())->where($associationColumn,$record->id)->latest()->limit(8)->get() : collect();
                @endphp
                <section class="hub-association-card"><div class="hub-card-head"><h2>{{ Str::headline($relatedEntity) }} <span>{{ $relatedRows->count() }}</span></h2><a href="{{ route('entities.create',$relatedEntity) }}" title="Create {{ Str::singular($relatedEntity) }}"><i class="bi bi-plus-lg"></i> Add</a></div><div class="hub-association-body">
                @forelse($relatedRows as $related)
                    <a class="hub-related-row" href="{{ route('entities.show',[$relatedEntity,$related->id]) }}"><span class="hub-related-icon"><i class="bi {{ config('crm.entities.'.$relatedEntity.'.icon') }}"></i></span><span>{{ $related->{config('crm.entities.'.$relatedEntity.'.title')} }}</span><i class="bi bi-chevron-right"></i></a>
                @empty
                    <div class="hub-related-empty">No linked {{ $relatedEntity }} yet.</div>
                @endforelse
                </div></section>
            @endforeach
        @endif
        <section class="hub-association-card"><div class="hub-card-head"><h2>Attachments <span>{{ $files->count() }}</span></h2></div><div class="hub-association-body">
            @foreach($files as $file)<a class="hub-file-row" href="{{ route('attachments.download',$file->id) }}"><i class="bi bi-paperclip"></i>{{ $file->original_name }}</a>@endforeach
            <form method="POST" action="{{ route('entities.attachment',[$entity,$record->id]) }}" enctype="multipart/form-data" class="hub-attachment-form" data-smart-form>@csrf<input type="file" class="form-control" name="file" required aria-label="Attach a file"><button class="hub-button hub-button-quiet" type="submit"><span class="hub-button-spinner"></span><span data-submit-label><i class="bi bi-upload"></i> Upload</span></button></form>
        </div></section>
        <div class="hub-context-tip"><i class="bi bi-shield-check"></i><span>Record access is controlled by your workspace permissions.</span></div>
    </aside>
</div>
@endsection
