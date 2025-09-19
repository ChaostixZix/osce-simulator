<?php

namespace App\Http\Controllers;

use App\Models\OsceCase;
use App\Services\PatientVisualizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PatientVisualizerController extends Controller
{
    private PatientVisualizerService $visualizerService;

    public function __construct(PatientVisualizerService $visualizerService)
    {
        $this->visualizerService = $visualizerService;
    }

    public function show(Request $request, $caseId = null)
    {
        $osceCase = $caseId ? OsceCase::findOrFail($caseId) : null;
        $commonPrompts = $this->visualizerService->getCommonPrompts();

        return Inertia::render('PatientVisualizer/Gallery', [
            'osceCase' => $osceCase,
            'commonPrompts' => $commonPrompts,
            'recentVisualizations' => $this->getRecentVisualizations($caseId),
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'custom_prompt' => 'required|string|max:500',
            'case_id' => 'sometimes|exists:osce_cases,id',
            'prompt_type' => 'sometimes|string',
        ]);

        // Use custom_prompt if provided, otherwise fallback to prompt
        $prompt = $request->get('custom_prompt') ?: $request->get('prompt', '');

        $options = [
            'case_id' => $request->get('case_id'),
            'prompt_type' => $request->get('prompt_type', 'case_specific'),
        ];

        $result = $this->visualizerService->getCachedOrGenerate($prompt, $options);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'blocked_category' => $result['blocked_category'] ?? null
            ], 400);
        }

        // Associate with user and case if provided
        if (!($result['cached'] ?? false)) {
            $visualization = \App\Models\PatientVisualization::where('prompt_hash', md5($request->prompt))
                ->latest()
                ->first();

            if ($visualization) {
                $visualization->update([
                    'user_id' => Auth::id(),
                    'osce_case_id' => $request->get('osce_case_id'),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'image_url' => $result['image_url'],
            'prompt' => $result['prompt'],
            'demographics' => $result['demographics'] ?? null,
            'type' => $result['type'] ?? 'generated',
            'generated_at' => $result['generated_at'],
        ]);
    }

    public function generateFromCommon(Request $request, $promptKey)
    {
        $commonPrompts = $this->visualizerService->getCommonPrompts();

        if (!isset($commonPrompts[$promptKey])) {
            return response()->json(['error' => 'Invalid prompt key'], 404);
        }

        $promptData = $commonPrompts[$promptKey];

        $options = [
            'style' => $request->get('style', 'medical-illustration'),
            'setting' => $request->get('setting', 'clinical'),
        ];

        $result = $this->visualizerService->getCachedOrGenerate($promptData['prompt'], $options);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'blocked_category' => $result['blocked_category'] ?? null
            ], 400);
        }

        return response()->json([
            'success' => true,
            'visualization' => [
                'image_url' => $result['image_url'],
                'prompt' => $result['prompt'],
                'description' => $promptData['description'],
                'category' => $promptData['category'],
                'cached' => $result['cached'] ?? false,
                'generated_at' => $result['generated_at'],
                'watermarked' => $result['watermarked'] ?? true,
            ]
        ]);
    }

    public function gallery(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 12);

        $visualizations = \App\Models\PatientVisualization::where('user_id', $user->id)
            ->whereNotNull('image_path')
            ->with('osceCase:id,title')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Filter out visualizations where the image file no longer exists
        $visualizations->getCollection()->transform(function ($viz) {
            if (!$viz->imageExists()) {
                $viz->deleteWithFile();
                return null;
            }
            return $viz;
        })->filter();

        return response()->json($visualizations);
    }

    public function delete(Request $request, $visualizationId)
    {
        $visualization = \App\Models\PatientVisualization::where('id', $visualizationId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $visualization->deleteWithFile();

        return response()->json(['success' => true]);
    }

    private function getRecentVisualizations($caseId = null)
    {
        $query = \App\Models\PatientVisualization::with('osceCase:id,title')
            ->whereNotNull('image_path')
            ->orderBy('created_at', 'desc')
            ->limit(6);

        if ($caseId) {
            $query->where('osce_case_id', $caseId);
        } else {
            $query->where('user_id', Auth::id());
        }

        return $query->get()->filter(function ($viz) {
            return $viz->imageExists();
        })->values();
    }
}