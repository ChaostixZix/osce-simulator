<?php

use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OsceAssessmentController;
use App\Http\Controllers\SeoController;

// use App\Http\Controllers\PatientController;
// use App\Http\Controllers\OsceAssessmentController;
use App\Http\Controllers\OsceRationalizationController;
use App\Http\Controllers\Admin\AdminOsceCaseController;
use App\Http\Controllers\Admin\AdminUserController;


// SEO Routes
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap.main');
Route::get('/sitemap-cases.xml', [SeoController::class, 'sitemapCases'])->name('sitemap.cases');
Route::get('/sitemap_index.xml', [SeoController::class, 'sitemapIndex'])->name('sitemap.index');

Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/privacy-policy', [LandingController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/contact', [LandingController::class, 'contact'])->name('contact');
Route::post('/contact', [LandingController::class, 'submitContact'])->name('contact.submit');
Route::get('/made-by', [LandingController::class, 'madeBy'])->name('made-by');

Route::middleware([
    'auth',
    'not-banned',
])->group(function () {

	Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
	
	Route::get('osce', [App\Http\Controllers\OsceController::class, 'index'])->name('osce.index');

	// Onboarding routes
	Route::get('osce/onboarding/{caseId}', [App\Http\Controllers\OnboardingController::class, 'show'])->name('onboarding.show');
	Route::post('osce/onboarding/{caseId}/complete', [App\Http\Controllers\OnboardingController::class, 'complete'])->name('onboarding.complete');
	Route::post('osce/onboarding/{caseId}/skip', [App\Http\Controllers\OnboardingController::class, 'skip'])->name('onboarding.skip');
	Route::post('osce/onboarding/{caseId}/practice-chat', [App\Http\Controllers\OnboardingController::class, 'practiceChat'])->name('onboarding.practice-chat');

	// Patient Visualizer routes
	Route::get('osce/visualizer/{caseId?}', [App\Http\Controllers\PatientVisualizerController::class, 'show'])->name('visualizer.show');
	Route::post('api/visualizer/generate', [App\Http\Controllers\PatientVisualizerController::class, 'generate'])->name('visualizer.generate');
	Route::post('api/visualizer/generate-common/{promptKey}', [App\Http\Controllers\PatientVisualizerController::class, 'generateFromCommon'])->name('visualizer.generate-common');
	Route::get('api/visualizer/gallery', [App\Http\Controllers\PatientVisualizerController::class, 'gallery'])->name('visualizer.gallery');
	Route::delete('api/visualizer/{visualizationId}', [App\Http\Controllers\PatientVisualizerController::class, 'delete'])->name('visualizer.delete');

	// Case Primer routes
	Route::get('api/case-primer/{caseId}', [App\Http\Controllers\CasePrimerController::class, 'show'])->name('case-primer.show');
	Route::get('api/case-primer/{caseId}/quick', [App\Http\Controllers\CasePrimerController::class, 'quick'])->name('case-primer.quick');
	Route::get('api/case-primer/{caseId}/complexity', [App\Http\Controllers\CasePrimerController::class, 'complexity'])->name('case-primer.complexity');
	Route::post('api/case-primer/compare', [App\Http\Controllers\CasePrimerController::class, 'compare'])->name('case-primer.compare');

	// Microskills Coach routes
	Route::get('api/microskills/{sessionId}/status', [App\Http\Controllers\MicroskillsCoachController::class, 'status'])->name('microskills.status');
	Route::get('api/microskills/{sessionId}/analyze', [App\Http\Controllers\MicroskillsCoachController::class, 'analyze'])->name('microskills.analyze');
	Route::get('api/microskills/{sessionId}/quiz', [App\Http\Controllers\MicroskillsCoachController::class, 'quiz'])->name('microskills.quiz');
	Route::post('api/microskills/{sessionId}/quiz-answer', [App\Http\Controllers\MicroskillsCoachController::class, 'submitQuizAnswer'])->name('microskills.submit-quiz');
	Route::post('api/microskills/{sessionId}/interventions/{interventionId}/displayed', [App\Http\Controllers\MicroskillsCoachController::class, 'markDisplayed'])->name('microskills.mark-displayed');
	Route::post('api/microskills/{sessionId}/interventions/{interventionId}/respond', [App\Http\Controllers\MicroskillsCoachController::class, 'respond'])->name('microskills.respond');
	Route::get('api/microskills/{sessionId}/history', [App\Http\Controllers\MicroskillsCoachController::class, 'history'])->name('microskills.history');
	Route::get('api/microskills/preferences', [App\Http\Controllers\MicroskillsCoachController::class, 'getPreferences'])->name('microskills.preferences');
	Route::post('api/microskills/preferences', [App\Http\Controllers\MicroskillsCoachController::class, 'updatePreferences'])->name('microskills.update-preferences');

	// Replay Studio routes
	Route::get('osce/replay/{sessionId}', [App\Http\Controllers\ReplayStudioController::class, 'show'])->name('replay.show');
	Route::post('api/replay/{sessionId}/generate', [App\Http\Controllers\ReplayStudioController::class, 'generate'])->name('replay.generate');
	Route::get('api/replay/{sessionId}', [App\Http\Controllers\ReplayStudioController::class, 'get'])->name('replay.get');
	Route::post('api/replay/{sessionId}/feedback', [App\Http\Controllers\ReplayStudioController::class, 'feedback'])->name('replay.feedback');
	Route::get('api/replay/{sessionId}/stats', [App\Http\Controllers\ReplayStudioController::class, 'stats'])->name('replay.stats');
	Route::post('api/replay/{sessionId}/export', [App\Http\Controllers\ReplayStudioController::class, 'export'])->name('replay.export');
	Route::delete('api/replay/{sessionId}', [App\Http\Controllers\ReplayStudioController::class, 'delete'])->name('replay.delete');

	// Longitudinal Growth routes
	Route::get('growth', [App\Http\Controllers\GrowthController::class, 'dashboard'])->name('growth.dashboard');
	Route::get('growth/cards', [App\Http\Controllers\GrowthController::class, 'cards'])->name('growth.cards');
	Route::get('growth/milestones', [App\Http\Controllers\GrowthController::class, 'milestones'])->name('growth.milestones');
	Route::get('growth/analytics', [App\Http\Controllers\GrowthController::class, 'analytics'])->name('growth.analytics');
	Route::get('growth/cards/{card}/review', [App\Http\Controllers\GrowthController::class, 'reviewCard'])->name('growth.card.review');
	Route::post('growth/cards/{card}/review', [App\Http\Controllers\GrowthController::class, 'submitCardReview'])->name('growth.card.review.submit');
	Route::get('growth/refresher/{refresher}', [App\Http\Controllers\GrowthController::class, 'showRefresher'])->name('growth.refresher.show');
	Route::post('growth/refresher/{refresher}', [App\Http\Controllers\GrowthController::class, 'submitRefresher'])->name('growth.refresher.submit');

	Route::post('osce/sessions/start', [App\Http\Controllers\OsceController::class, 'startSessionInertia'])->name('osce.sessions.start');
	Route::get('osce/chat/{session}', [App\Http\Controllers\OsceController::class, 'showChat'])->name('osce.chat');
	Route::get('api/osce/cases', [App\Http\Controllers\OsceController::class, 'getCases']);
	Route::get('api/osce/sessions', [App\Http\Controllers\OsceController::class, 'getUserSessions']);
	Route::post('api/osce/sessions/start', [App\Http\Controllers\OsceController::class, 'startSession']);
	Route::get('api/osce/sessions/{session}/timer', [App\Http\Controllers\OsceController::class, 'getSessionTimer']);
	Route::post('api/osce/sessions/{session}/complete', [App\Http\Controllers\OsceController::class, 'completeSession']);
	Route::post('api/osce/sessions/{session}/finalize', [App\Http\Controllers\OsceController::class, 'finalizeSession']);
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
	
	// Real-time status via WebSocket (SSE routes removed)

	// OSCE Rationalization (post-session reflection)
	Route::get('osce/rationalization/{session}', [OsceRationalizationController::class, 'show'])->name('osce.rationalization.show');
	Route::post('api/osce/sessions/{session}/rationalization/complete', [OsceRationalizationController::class, 'complete'])->name('osce.rationalization.complete');
	
	// Rationalization workflow routes
	Route::post('rationalization/cards/{card}/answer', [App\Http\Controllers\RationalizationController::class, 'answerCard'])->name('rationalization.answer-card');
	Route::post('rationalization/{rationalization}/diagnoses', [App\Http\Controllers\RationalizationController::class, 'submitDiagnoses'])->name('rationalization.submit-diagnoses');
	Route::post('rationalization/{rationalization}/care-plan', [App\Http\Controllers\RationalizationController::class, 'submitCarePlan'])->name('rationalization.submit-care-plan');
	Route::get('rationalization/{rationalization}/progress', [App\Http\Controllers\RationalizationController::class, 'progress'])->name('rationalization.progress');
	Route::post('rationalization/{rationalization}/complete', [App\Http\Controllers\RationalizationController::class, 'complete'])->name('rationalization.complete');
	
	// Admin routes
	Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
		Route::post('osce-cases/generate', [AdminOsceCaseController::class, 'generate'])->name('osce-cases.generate');
		Route::resource('osce-cases', AdminOsceCaseController::class)->except(['show']);
		Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
		Route::put('users/{user}/toggle-admin', [AdminUserController::class, 'toggleAdminStatus'])->name('users.toggle-admin');
		Route::put('users/{user}/toggle-ban', [AdminUserController::class, 'toggleBanStatus'])->name('users.toggle-ban');
	});

    // Removed MCQ, Forum, and SOAP routes

});

// Health check endpoints (internal use)
Route::prefix('health')->group(function () {
    Route::get('basic', [HealthCheckController::class, 'basic']);
    Route::get('detailed', [HealthCheckController::class, 'detailed']);
    Route::get('authentication', [HealthCheckController::class, 'authentication']);
    Route::get('migration', [HealthCheckController::class, 'migration']);
})->middleware('web');

// Migration dashboard route (admin only)
Route::get('migration-dashboard', function () {
    return view('migration-dashboard');
})->middleware(['auth', 'admin']);

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
