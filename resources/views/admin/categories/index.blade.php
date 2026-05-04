@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin btn-admin--primary" href="{{ route('admin.categories.create') }}">+ New category</a>
@endsection

@section('content')
<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Slug</th>
            <th>Sort</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($categories as $c)
            <tr>
                <td>{{ $c->name }}</td>
                <td><code>{{ $c->slug }}</code></td>
                <td>{{ $c->sort_order }}</td>
                <td class="data-table__actions">
                    <a class="btn-admin btn-admin--small" href="{{ route('admin.categories.edit', $c) }}">Edit</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="data-table__empty">No categories yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
