@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin btn-admin--primary" href="{{ route('admin.brands.create') }}">+ New brand</a>
@endsection

@section('content')
<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr>
            <th></th>
            <th>Name</th>
            <th>Slug</th>
            <th>Sort</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($brands as $b)
            <tr>
                <td style="width:56px;">
                    @if(!empty($b->image))
                        <img src="{{ \App\Support\PublicAssetUrl::to($b->image) }}" alt="" width="48" height="48" style="object-fit:cover;border-radius:8px;display:block;">
                    @else
                        <span class="data-table__muted">—</span>
                    @endif
                </td>
                <td>{{ $b->name }}</td>
                <td><code>{{ $b->slug }}</code></td>
                <td>{{ $b->sort_order }}</td>
                <td class="data-table__actions">
                    <a class="btn-admin btn-admin--small" href="{{ route('admin.brands.edit', $b) }}">Edit</a>
                    <form class="inline-form" method="post" action="{{ route('admin.brands.destroy', $b) }}" onsubmit="return confirm('Delete this brand?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="link-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="data-table__empty">No brands yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
