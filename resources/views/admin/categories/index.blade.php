@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin btn-admin--primary" href="{{ route('admin.categories.create') }}">+ New category</a>
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
        @forelse($categories as $c)
            <tr>
                <td style="width:56px;">
                    @if(!empty($c->image))
                        <img src="{{ \App\Support\PublicAssetUrl::to($c->image) }}" alt="" width="48" height="48" style="object-fit:cover;border-radius:8px;display:block;">
                    @else
                        <span class="data-table__muted">—</span>
                    @endif
                </td>
                <td>{{ $c->name }}</td>
                <td><code>{{ $c->slug }}</code></td>
                <td>{{ $c->sort_order }}</td>
                <td class="data-table__actions">
                    <a class="btn-admin btn-admin--small" href="{{ route('admin.categories.edit', $c) }}">Edit</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="data-table__empty">No categories yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
