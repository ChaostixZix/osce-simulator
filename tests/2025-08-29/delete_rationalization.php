<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AnamnesisRationalizationCard;
use App\Models\OsceDiagnosisEntry;
use App\Models\OsceSessionRationalization;
use App\Models\RationalizationEvaluation;
use Illuminate\Support\Facades\DB;

echo "=== DELETING ALL RATIONALIZATION DATA ===\n\n";

echo "⚠️  WARNING: This will delete ALL rationalization data!\n";
echo "   This action cannot be undone.\n\n";

echo "Current data counts:\n";
echo "- osce_session_rationalizations: " . OsceSessionRationalization::count() . "\n";
echo "- anamnesis_rationalization_cards: " . AnamnesisRationalizationCard::count() . "\n";
echo "- osce_diagnosis_entries: " . OsceDiagnosisEntry::count() . "\n";
echo "- rationalization_evaluations: " . RationalizationEvaluation::count() . "\n\n";

try {
    DB::beginTransaction();

    echo "🗑️  Starting deletion process...\n";

    // Delete in reverse order of dependencies to avoid foreign key constraints

    // 1. Delete evaluation records first (they depend on rationalization)
    $evaluationCount = RationalizationEvaluation::count();
    RationalizationEvaluation::query()->delete();
    echo "✅ Deleted {$evaluationCount} rationalization evaluations\n";

    // 2. Delete diagnosis entries (they depend on rationalization)
    $diagnosisCount = OsceDiagnosisEntry::count();
    OsceDiagnosisEntry::query()->delete();
    echo "✅ Deleted {$diagnosisCount} diagnosis entries\n";

    // 3. Delete rationalization cards (they depend on rationalization)
    $cardCount = AnamnesisRationalizationCard::count();
    AnamnesisRationalizationCard::query()->delete();
    echo "✅ Deleted {$cardCount} rationalization cards\n";

    // 4. Finally delete the rationalizations themselves
    $rationalizationCount = OsceSessionRationalization::count();
    OsceSessionRationalization::query()->delete();
    echo "✅ Deleted {$rationalizationCount} rationalization records\n";

    DB::commit();

    echo "\n🎉 SUCCESS: All rationalization data deleted successfully!\n";
    echo "   You can now start fresh with the rationalization process.\n";

    // Verify deletion
    echo "\n📊 Verification - Remaining counts:\n";
    echo "- osce_session_rationalizations: " . OsceSessionRationalization::count() . "\n";
    echo "- anamnesis_rationalization_cards: " . AnamnesisRationalizationCard::count() . "\n";
    echo "- osce_diagnosis_entries: " . OsceDiagnosisEntry::count() . "\n";
    echo "- rationalization_evaluations: " . RationalizationEvaluation::count() . "\n";

} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: Failed to delete rationalization data\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} catch (Throwable $e) {
    DB::rollBack();
    echo "\n❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
