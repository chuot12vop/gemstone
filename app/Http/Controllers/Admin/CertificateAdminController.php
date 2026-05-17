<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateAdminController extends Controller
{
    private const PUBLIC_STORAGE_PREFIX = '/storage/';

    public function index()
    {
        return view('admin.certificates.index', [
            'title' => 'Certificates',
            'breadcrumbs' => [
                ['label' => 'Certificates'],
            ],
            'certificates' => Certificate::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.certificates.form', [
            'title' => 'New certificate',
            'breadcrumbs' => [
                ['label' => 'Certificates', 'url' => route('admin.certificates.index')],
                ['label' => 'New'],
            ],
            'certificate' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        $imageUrl = $this->storeImage($request->file('image'), 'certificates');
        if ($imageUrl === null) {
            return back()->withInput()->withErrors(['image' => 'Please choose a certificate image (JPG, PNG, or WebP, max 12 MB).']);
        }
        $data['image'] = $imageUrl;

        Certificate::query()->create($data);

        return redirect()->route('admin.certificates.index')->with('success', 'Certificate created.');
    }

    public function edit(Certificate $certificate)
    {
        return view('admin.certificates.form', [
            'title' => 'Edit certificate',
            'breadcrumbs' => [
                ['label' => 'Certificates', 'url' => route('admin.certificates.index')],
                ['label' => $certificate->name],
            ],
            'certificate' => $certificate,
        ]);
    }

    public function update(Request $request, Certificate $certificate)
    {
        $data = $this->validated($request, $certificate);
        $imageUrl = $this->storeImage($request->file('image'), 'certificates');
        if ($imageUrl !== null) {
            $this->deletePublicPath($certificate->image);
            $data['image'] = $imageUrl;
        }

        $certificate->update($data);

        return redirect()->route('admin.certificates.index')->with('success', 'Certificate updated.');
    }

    public function destroy(Certificate $certificate)
    {
        $this->deletePublicPath($certificate->image);
        $certificate->delete();

        return redirect()->route('admin.certificates.index')->with('success', 'Certificate deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Certificate $certificate = null): array
    {
        $imageRule = $certificate === null
            ? 'required|image|mimes:jpg,jpeg,png,webp|max:12288'
            : 'nullable|image|mimes:jpg,jpeg,png,webp|max:12288';

        $validated = $request->validate([
            'name' => 'required|string|max:160',
            'description' => 'nullable|string',
            'image' => $imageRule,
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'image.required' => 'Please choose a certificate image.',
            'image.image' => 'The file must be an image (JPG, PNG, or WebP).',
            'image.mimes' => 'Allowed formats: JPG, PNG, WebP.',
            'image.max' => 'Image must be smaller than 12 MB.',
        ]);

        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];
    }

    private function storeImage(?UploadedFile $file, string $directory): ?string
    {
        if ($file === null || ! $file->isValid()) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (! in_array($extension, $allowed, true)) {
            $extension = 'jpg';
        }

        $path = $file->storeAs(trim($directory, '/'), Str::uuid()->toString().'.'.$extension, 'public');

        return is_string($path) && $path !== '' ? self::PUBLIC_STORAGE_PREFIX.$path : null;
    }

    private function deletePublicPath(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $relativePath = Str::startsWith($path, self::PUBLIC_STORAGE_PREFIX)
            ? Str::after($path, self::PUBLIC_STORAGE_PREFIX)
            : ltrim($path, '/');

        if ($relativePath !== '') {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
