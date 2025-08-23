<?php

namespace App\Http\Controllers;

use App\Models\McqTest;
use Inertia\Inertia;
use Inertia\Response;

class MCQController extends Controller
{
    public function index(): Response
    {
        $tests = McqTest::all();

        return Inertia::render('MCQ/Index', [
            'tests' => $tests,
        ]);
    }

    public function show(McqTest $test): Response
    {
        $test->load(['questions.options']);

        return Inertia::render('MCQ/Show', [
            'test' => $test,
        ]);
    }
}
