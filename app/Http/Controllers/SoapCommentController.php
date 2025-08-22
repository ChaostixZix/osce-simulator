<?php

namespace App\Http\Controllers;

use App\Models\SoapNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SoapCommentController extends Controller
{
    public function index(SoapNote $note): JsonResponse
    {
        $comments = $note->comments()
            ->with('author:id,name')
            ->latest()
            ->paginate(10);

        return response()->json($comments);
    }

    public function store(Request $request, SoapNote $note): Response
    {
        $validated = $request->validate([
            'body' => 'required|string',
        ]);

        $note->comments()->create([
            'author_id' => Auth::id(),
            'body' => $validated['body'],
        ]);

        return response()->noContent();
    }
}
