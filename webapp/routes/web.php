<?php

use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\OsceAssessmentController;

// use App\Http\Controllers\PatientController;
// use App\Http\Controllers\OsceAssessmentController;
use App\Http\Controllers\OsceRationalizationController;


Route::get('/', [LandingController::class, 'index'])->name('home');

Route::middleware([
    'auth',
    ValidateSessionWithWorkOS::class,
])->group(function () {

	Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
	
	Route::get('osce', [App\Http\Controllers\OsceController::class, 'index'])->name('osce');
	Route::post('osce/sessions/start', [App\Http\Controllers\OsceController::class, 'startSessionInertia'])->name('osce.sessions.start');
	Route::get('osce/chat/{session}', [App\Http\Controllers\OsceController::class, 'showChat'])->name('osce.chat');
	Route::get('api/osce/cases', [App\Http\Controllers\OsceController::class, 'getCases']);
	Route::get('api/osce/sessions', [App\Http\Controllers\OsceController::class, 'getUserSessions']);
	Route::post('api/osce/sessions/start', [App\Http\Controllers\OsceController::class, 'startSession']);
	Route::get('api/osce/sessions/{session}/timer', [App\Http\Controllers\OsceController::class, 'getSessionTimer']);
	Route::post('api/osce/sessions/{session}/complete', [App\Http\Controllers\OsceController::class, 'completeSession']);
	Route::post('api/osce/sessions/{session}/extend', [App\Http\Controllers\OsceController::class, 'extendSession']);
	
	// OSCE Chat routes (named for Ziggy)
	Route::post('api/osce/chat/start', [App\Http\Controllers\OsceChatController::class, 'startChat'])->name('osce.chat.start');
	Route::post('api/osce/chat/message', [App\Http\Controllers\OsceChatController::class, 'sendMessage'])->name('osce.chat.message');
	Route::get('api/osce/chat/history/{session}', [App\Http\Controllers\OsceChatController::class, 'getChatHistory'])->name('osce.chat.history');
	
	// OSCE Examination routes (legacy ordering replaced by clinical reasoning system)
	Route::post('osce/order-procedure', [App\Http\Controllers\OsceController::class, 'orderProcedure'])->name('osce.order-procedure');
	Route::post('osce/perform-examination', [App\Http\Controllers\OsceController::class, 'performExamination'])->name('osce.perform-examination');
	
	// JSON API variants for React (no redirects)
	Route::post('api/osce/examinations', [App\Http\Controllers\OsceController::class, 'performExaminationJson']);
	Route::post('api/osce/procedures', [App\Http\Controllers\OsceController::class, 'orderProcedureJson']);

	// Clinical reasoning ordering API
	Route::post('api/osce/order-tests', [App\Http\Controllers\OsceController::class, 'orderTests']);
	Route::post('api/osce/refresh-results/{session}', [App\Http\Controllers\OsceController::class, 'refreshTestResults']);
	Route::get('api/medical-tests/search', [App\Http\Controllers\MedicalTestController::class, 'search']);
	Route::get('api/medical-tests/categories', [App\Http\Controllers\MedicalTestController::class, 'getCategories']);
	Route::post('api/osce/cases/{case}/duration', [App\Http\Controllers\OsceController::class, 'updateCaseDuration']);
	
	// OSCE Assessment routes
	Route::post('api/osce/sessions/{session}/assess', [OsceAssessmentController::class, 'assess'])->name('osce.assess');
	// Inertia-friendly trigger that redirects back
	Route::post('osce/sessions/{session}/assess/trigger', [OsceAssessmentController::class, 'assessInertia'])->name('osce.assess.trigger');
	Route::get('api/osce/sessions/{session}/status', [OsceAssessmentController::class, 'status'])->name('osce.status');
	Route::get('api/osce/sessions/{session}/results', [OsceAssessmentController::class, 'results'])->name('osce.results');
	Route::get('osce/results/{session}', [OsceAssessmentController::class, 'show'])->name('osce.results.show');
	// Optional scoring alias: follows the same gating as results
	Route::get('osce/scoring/{session}', [OsceAssessmentController::class, 'show'])->name('osce.scoring.show');

	// OSCE Rationalization (post-session reflection)
	Route::get('osce/rationalization/{session}', [OsceRationalizationController::class, 'show'])->name('osce.rationalization.show');
	Route::post('api/osce/sessions/{session}/rationalization/complete', [OsceRationalizationController::class, 'complete'])->name('osce.rationalization.complete');
	
	// Rationalization workflow routes
	Route::post('rationalization/cards/{card}/answer', [App\Http\Controllers\RationalizationController::class, 'answerCard'])->name('rationalization.answer-card');
	Route::post('rationalization/{rationalization}/diagnoses', [App\Http\Controllers\RationalizationController::class, 'submitDiagnoses'])->name('rationalization.submit-diagnoses');
	Route::post('rationalization/{rationalization}/care-plan', [App\Http\Controllers\RationalizationController::class, 'submitCarePlan'])->name('rationalization.submit-care-plan');
	Route::get('rationalization/{rationalization}/progress', [App\Http\Controllers\RationalizationController::class, 'progress'])->name('rationalization.progress');
	Route::post('rationalization/{rationalization}/complete', [App\Http\Controllers\RationalizationController::class, 'complete'])->name('rationalization.complete');
	
    // Removed MCQ, Forum, and SOAP routes

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
