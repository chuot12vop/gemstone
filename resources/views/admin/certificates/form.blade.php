@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin" href="{{ route('admin.certificates.index') }}">← Back to list</a>
@endsection

@section('content')
@if($errors->any())
    <div class="admin-banner admin-banner--err" role="alert" style="margin-bottom:1rem;">
        <ul style="margin:0;padding-left:1.2rem;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form class="stack-form" method="post" enctype="multipart/form-data" action="{{ $certificate ? route('admin.certificates.update', $certificate) : route('admin.certificates.store') }}" id="certificate-form">
    @csrf
    @if($certificate)
        @method('PUT')
    @endif

    <div class="form-grid">
        <label>
            Name
            <input type="text" name="name" required value="{{ old('name', $certificate->name ?? '') }}">
        </label>
        <label>
            Sort order
            <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $certificate ? (string) $certificate->sort_order : '0') }}">
        </label>
    </div>

    <fieldset class="form-fieldset">
        <legend>Certificate image</legend>
        @include('partials.file-upload', [
            'name' => 'image',
            'previewUrl' => !empty($certificate?->image) ? \App\Support\PublicAssetUrl::to($certificate->image) : null,
            'previewFit' => 'contain',
            'required' => !$certificate,
            'statusText' => $certificate?->image
                ? 'Current image on file. Choose a new file to replace it.'
                : 'No image selected yet.',
        ])
        @error('image')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </fieldset>

    <label>
        Description
        <textarea name="description" rows="4">{{ old('description', $certificate->description ?? '') }}</textarea>
    </label>

    <div class="form-actions">
        <button class="btn-admin btn-admin--primary" type="submit">{{ $certificate ? 'Save changes' : 'Create certificate' }}</button>
        <a class="btn-admin" href="{{ route('admin.certificates.index') }}">Cancel</a>
    </div>
</form>
@endsection
