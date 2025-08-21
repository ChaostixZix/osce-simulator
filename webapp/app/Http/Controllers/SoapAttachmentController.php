<?php

namespace App\Http\Controllers;

use App\Models\SoapNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
}
