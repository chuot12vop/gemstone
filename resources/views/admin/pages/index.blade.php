@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin btn-admin--primary" href="{{ route('admin.pages.create') }}">+ New page</a>
@endsection

@section('content')
<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr>
            <th>Title</th>
            <th>Slug</th>
            <th>Sort</th>
            <th>Active</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($pages as $page)
            <tr>
                <td>{{ $page->title }}</td>
                <td><code>{{ $page->slug }}</code></td>
                <td>{{ $page->sort_order }}</td>
                <td>{{ $page->is_active ? 'Yes' : 'No' }}</td>
                <td class="data-table__actions">
                    <a class="btn-admin btn-admin--small" href="{{ route('admin.pages.edit', $page) }}">Edit</a>
                    <form class="inline-form" method="post" action="{{ route('admin.pages.destroy', $page) }}" onsubmit="return confirm('Delete this page?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="link-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="data-table__empty">No pages yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
