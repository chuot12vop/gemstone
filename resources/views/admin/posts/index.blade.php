@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin btn-admin--primary" href="{{ route('admin.posts.create') }}">+ New article</a>
@endsection

@section('content')
<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr>
            <th>Title</th>
            <th>Slug</th>
            <th>Published</th>
            <th>Active</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($posts as $post)
            <tr>
                <td>{{ $post->title }}</td>
                <td><code>{{ $post->slug }}</code></td>
                <td>{{ $post->published_at?->format('Y-m-d') ?? '—' }}</td>
                <td>{{ $post->is_active ? 'Yes' : 'No' }}</td>
                <td class="data-table__actions">
                    <a class="btn-admin btn-admin--small" href="{{ route('admin.posts.edit', $post) }}">Edit</a>
                    <form class="inline-form" method="post" action="{{ route('admin.posts.destroy', $post) }}" onsubmit="return confirm('Delete this article?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="link-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="data-table__empty">No articles yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
