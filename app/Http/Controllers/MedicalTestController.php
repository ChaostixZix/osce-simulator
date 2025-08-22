<?php

namespace App\Http\Controllers;

use App\Models\MedicalTest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicalTestController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $category = $request->get('category');
        $type = $request->get('type');

        $tests = MedicalTest::query()
            ->where('is_active', true)
            ->when($query, function ($q) use ($query) {
                $q->where(function ($q2) use ($query) {
                    $q2->where('name', 'like', "%{$query}%")
                       ->orWhere('category', 'like', "%{$query}%")
                       ->orWhere('description', 'like', "%{$query}%");
                });
            })
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('name')
            ->limit(50)
            ->get();

        return response()->json($tests);
    }

    public function getCategories(): JsonResponse
    {
        $categories = MedicalTest::select('category', 'type')
            ->where('is_active', true)
            ->distinct()
            ->orderBy('category')
            ->get()
            ->groupBy('type');

        return response()->json($categories);
    }
}


