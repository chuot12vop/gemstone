@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin btn-admin--primary" href="{{ route('admin.certificates.create') }}">+ New certificate</a>
@endsection

@section('content')
<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr>
            <th></th>
            <th>Name</th>
            <th>Sort</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($certificates as $certificate)
            <tr>
                <td style="width:56px;">
                    @if(!empty($certificate->image))
                        <img src="{{ \App\Support\PublicAssetUrl::to($certificate->image) }}" alt="" width="48" height="48" style="object-fit:cover;border-radius:8px;display:block;">
                    @else
                        <span class="data-table__muted">—</span>
                    @endif
                </td>
                <td>{{ $certificate->name }}</td>
                <td>{{ $certificate->sort_order }}</td>
                <td class="data-table__actions">
                    <a class="btn-admin btn-admin--small" href="{{ route('admin.certificates.edit', $certificate) }}">Edit</a>
                    <form class="inline-form" method="post" action="{{ route('admin.certificates.destroy', $certificate) }}" onsubmit="return confirm('Delete this certificate?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="link-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="data-table__empty">No certificates yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
