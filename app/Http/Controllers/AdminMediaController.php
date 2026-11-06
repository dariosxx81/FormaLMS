<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminMediaController extends Controller
{
    public function index(Request $request)
    {
        $settings = PlatformSetting::getAllSettings();

        $typeFilter = $request->query('type');
        $search = $request->query('q');

        $query = MediaFile::with('uploader')->latest();

        if ($typeFilter && in_array($typeFilter, ['badge', 'video', 'document', 'other'])) {
            $query->where('media_type', $typeFilter);
        }

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        $mediaFiles = $query->paginate(24)->withQueryString();

        $stats = [
            'total' => MediaFile::count(),
            'badges' => MediaFile::where('media_type', 'badge')->count(),
            'videos' => MediaFile::where('media_type', 'video')->count(),
            'documents' => MediaFile::where('media_type', 'document')->count(),
        ];

        return view('admin.media.index', compact('settings', 'mediaFiles', 'stats', 'typeFilter', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'media_type' => 'required|in:badge,video,document,other',
            'file' => 'required|file|max:512000', // up to 500MB
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $title = $validated['title'] ?: pathinfo($originalName, PATHINFO_FILENAME);
        $mime = $file->getClientMimeType() ?: $file->getMimeType();
        $size = $file->getSize();

        $folder = match ($validated['media_type']) {
            'badge' => 'course-badges',
            'video' => 'course-videos',
            'document' => 'media-documents',
            default => 'media-uploads',
        };

        $path = $file->store($folder, 'public');

        $media = MediaFile::create([
            'title' => $title,
            'file_path' => $path,
            'disk' => 'public',
            'media_type' => $validated['media_type'],
            'mime_type' => $mime,
            'file_size_bytes' => $size,
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->route('admin.media.index')
            ->with('success', "File \"{$media->title}\" caricato con successo! Link pubblico generato.");
    }

    public function destroy(MediaFile $media)
    {
        if ($media->disk && Storage::disk($media->disk)->exists($media->file_path)) {
            Storage::disk($media->disk)->delete($media->file_path);
        }

        $media->delete();

        return redirect()->route('admin.media.index')
            ->with('success', 'File eliminato correttamente dalla libreria media.');
    }
}
