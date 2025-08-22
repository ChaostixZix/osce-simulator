<?php

namespace App\Http\Controllers;

use App\Models\SoapNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SoapAttachmentController extends Controller
{
    public function store(Request $request, SoapNote $note): RedirectResponse
    {
        $this->authorize('update', $note);

        $request->validate([
            'files.*' => 'required|file|max:5120',
        ]);

        foreach ($request->file('files') as $file) {
            $path = $file->store("soap/{$note->id}", 'local');

            $note->attachments()->create([
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ]);
        }

        return back();
    }

    public function uploadImage(Request $request, SoapNote $note): JsonResponse
    {
        $this->authorize('update', $note);

        $request->validate([
            'image' => 'required|image|max:5120', // 5MB max
        ]);

        $file = $request->file('image');
        $path = $file->store("soap/{$note->id}", 'public');

        // Store the attachment record
        $attachment = $note->attachments()->create([
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ]);

        // Return the URL for the editor
        return response()->json([
            'url' => asset("storage/{$path}"),
            'alt' => $file->getClientOriginalName(),
            'attachment_id' => $attachment->id,
        ]);
    }
}
