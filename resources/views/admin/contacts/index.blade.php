@extends('layouts.admin')

@section('module-meta')
    {{ $contacts->count() }} contact{{ $contacts->count() === 1 ? '' : 's' }}
    @if($newCount > 0)
        · <strong style="color: var(--gold-deep);">{{ $newCount }} new</strong>
    @endif
@endsection

@section('content')
<form class="stack-form form-inline" method="get" action="{{ route('admin.contacts.index') }}" style="margin-bottom:14px;">
    <label class="form-inline__field">
        Search
        <input type="text" name="q" value="{{ $q }}" placeholder="Name, phone, email, address, message">
    </label>
    <label class="form-inline__field">
        Status
        <select name="status">
            <option value="">All</option>
            @foreach($statuses as $s)
                <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </label>
    <button class="btn-admin" type="submit">Filter</button>
</form>

<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr>
            <th>Received</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Address</th>
            <th>Message</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($contacts as $contact)
            <tr class="{{ $contact->status === 'new' ? 'data-table__row--highlight' : '' }}">
                <td>{{ $contact->created_at?->format('Y-m-d H:i') }}</td>
                <td><strong>{{ $contact->name }}</strong></td>
                <td><a href="tel:{{ $contact->phone }}">{{ $contact->phone }}</a></td>
                <td>
                    @if($contact->email)
                        <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                    @else
                        <span class="muted">—</span>
                    @endif
                </td>
                <td><span class="admin-contact-snippet">{{ Str::limit($contact->address, 80) }}</span></td>
                <td>
                    <span class="admin-contact-snippet">
                        @if($contact->product)
                            <span class="muted">Product:</span> {{ Str::limit($contact->product, 40) }}<br>
                        @endif
                        @if($contact->message)
                            {{ Str::limit($contact->message, 60) }}
                        @elseif(! $contact->product)
                            <span class="muted">—</span>
                        @endif
                    </span>
                </td>
                <td><span class="badge badge--{{ $contact->status }}">{{ $contact->status }}</span></td>
                <td class="data-table__actions">
                    <a class="btn-admin btn-admin--small" href="{{ route('admin.contacts.show', $contact) }}">View</a>
                    <form method="post" action="{{ route('admin.contacts.destroy', $contact) }}"
                          onsubmit="return confirm('Delete this contact?');" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn-admin btn-admin--small btn-admin--danger" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="data-table__empty">No contacts match these filters.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
