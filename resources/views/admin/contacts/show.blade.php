@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin" href="{{ route('admin.contacts.index') }}">← Back to list</a>
@endsection

@section('content')
<div class="grid-2col">
    <div>
        <h2 class="admin-h2">Contact</h2>
        <p><strong>{{ $contact->name }}</strong></p>
        <p>📞 <a href="tel:{{ $contact->phone }}">{{ $contact->phone }}</a></p>
        <h2 class="admin-h2">Address</h2>
        <p>{!! nl2br(e($contact->address)) !!}</p>
    </div>
    <div>
        <h2 class="admin-h2">Status</h2>
        <p><span class="badge badge--{{ $contact->status }}">{{ $contact->status }}</span></p>
        <h2 class="admin-h2">Received</h2>
        <p>{{ $contact->created_at?->format('Y-m-d H:i') }}</p>
        @if($contact->ip)
            <p class="muted" style="font-size: 0.85rem;">IP: {{ $contact->ip }}</p>
        @endif
        @if($contact->user_agent)
            <p class="muted" style="font-size: 0.85rem; word-break: break-all;">UA: {{ $contact->user_agent }}</p>
        @endif
    </div>
</div>

<form class="stack-form form-inline" method="post" action="{{ route('admin.contacts.status', $contact) }}">
    @csrf
    <label class="form-inline__field">
        Update status
        <select name="status">
            @foreach($statuses as $s)
                <option value="{{ $s }}" @selected($contact->status === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </label>
    <button class="btn-admin btn-admin--primary" type="submit">Save status</button>
</form>
@endsection
