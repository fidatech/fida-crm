@extends('layouts.app')

@section('title','AI workspace assistant')

@section('content')
<div class="crm-page-intro"><div><div class="eyebrow">AI WORKSPACE</div><h1>Account intelligence</h1><p>Ask for account summaries, next actions and suggested outreach drafts using records in your current workspace.</p></div></div>
<div class="row g-4"><div class="col-lg-6"><div class="crm-card"><div class="crm-card-header"><div><h3>Ask the assistant</h3><p>Only selected record details and 10 recent activities are sent to the AI provider.</p></div></div><form class="p-4" action="{{ route('assistant.ask') }}" method="POST" data-smart-form>

@csrf<label class="form-label crm-label">Record type</label><select name="entity" class="form-select crm-input mb-3">

@foreach(['contacts','companies','deals','leads','tickets'] as $type)<option value="{{ $type }}" 

@selected(old('entity',$selected['entity']??'contacts')===$type)>{{ Str::headline($type) }}</option>

@endforeach</select><label class="form-label crm-label">Record ID</label><input type="number" min="1" name="record_id" class="form-control crm-input mb-3" required value="{{ old('record_id',$selected['record_id']??'') }}"><p class="small text-muted">Open the record to find its ID. An account must belong to your current workspace.</p><label class="form-label crm-label">Question</label><textarea name="question" class="form-control crm-input mb-4" rows="5" required placeholder="Summarize the deal risks and recommend the next customer follow-up.">{{ old('question',$selected['question']??'') }}</textarea><button class="btn btn-fida-amber">Generate response</button></form></div></div><div class="col-lg-6"><div class="crm-card p-4"><div class="eyebrow">ASSISTANT RESPONSE</div>

@if($result)<div style="white-space:pre-wrap;line-height:1.8;font-size:13px;margin-top:22px">{{ $result }}</div>

@else<div class="empty-state">Select a record and ask a question to see a response here.</div>

@endif<p class="small text-muted border-top pt-3 mt-3">AI-generated text may contain mistakes. Verify all customer information before sharing it.</p></div></div></div>

@endsection
