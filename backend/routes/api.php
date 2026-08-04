<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\ConsultationController;
use App\Http\Controllers\Api\FournisseurController;
use App\Http\Controllers\Api\EngagementController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('users', UserController::class)->only(['update', 'destroy']);

    Route::apiResource('fournisseurs', FournisseurController::class);
    Route::apiResource('commission-membres', \App\Http\Controllers\Api\CommissionMembreController::class);
    Route::apiResource('consultations', ConsultationController::class)->only(['index', 'store', 'show']);
    Route::put('consultations/{consultation}', [ConsultationController::class, 'update']);
    Route::post('consultations/{consultation}/prestations', [ConsultationController::class, 'syncPrestations']);
    Route::get('consultations/{consultation}/pdf-estimation', [ConsultationController::class, 'generatePdf']);

    // Consultation Fournisseurs (Module 04)
    Route::post('consultations/{consultation}/fournisseurs', [ConsultationController::class, 'selectFournisseurs']);
    Route::put('consultations/{consultation}/fournisseurs/{fournisseur}/devis', [ConsultationController::class, 'updateDevis']);
    Route::get('consultations/{consultation}/comparatif', [ConsultationController::class, 'getComparatif']);

    // Commission et Ouverture des Plis (Module 05)
    Route::post('consultations/{consultation}/commission', [ConsultationController::class, 'saveCommission']);
    Route::post('consultations/{consultation}/ouverture-plis', [ConsultationController::class, 'saveOuverturePlis']);

    // Engagement (Module 06)
    Route::get('consultations/{consultation}/engagement/data', [EngagementController::class, 'getEngagementData']);
    Route::post('consultations/{consultation}/engagement', [EngagementController::class, 'store']);

    // Suivi d'exécution (Module 07)
    Route::get('consultations/{consultation}/suivi', [\App\Http\Controllers\Api\SuiviExecutionController::class, 'getSuivi']);
    Route::put('consultations/{consultation}/suivi', [\App\Http\Controllers\Api\SuiviExecutionController::class, 'updateSuivi']);
    Route::post('consultations/{consultation}/suivi/evenements', [\App\Http\Controllers\Api\SuiviExecutionController::class, 'addEvenement']);

    // Réception des prestations (Module 08)
    Route::get('consultations/{consultation}/receptions', [\App\Http\Controllers\Api\ReceptionController::class, 'getReceptions']);
    Route::post('consultations/{consultation}/receptions', [\App\Http\Controllers\Api\ReceptionController::class, 'store']);

    // Liquidation financière (Module 09)
    Route::get('consultations/{consultation}/liquidation', [\App\Http\Controllers\Api\LiquidationFinanciereController::class, 'getLiquidation']);
    Route::post('consultations/{consultation}/liquidation', [\App\Http\Controllers\Api\LiquidationFinanciereController::class, 'store']);

    // Documents et Archivage (Module 10 & 11)
    Route::get('consultations/{consultation}/documents/archive-zip', [\App\Http\Controllers\Api\DocumentController::class, 'downloadZip']);
    Route::get('consultations/{consultation}/documents/{type}', [\App\Http\Controllers\Api\DocumentController::class, 'generate']);

    Route::apiResource('budgets', BudgetController::class)->only(['index', 'store', 'show']);

    // Workflow Machine à États (Engagements Génériques)
    Route::post('/engagements/workflow', [\App\Http\Controllers\EngagementWorkflowController::class, 'store']);
    Route::get('/engagements/workflow/{id}/timeline', [\App\Http\Controllers\EngagementWorkflowController::class, 'getTimeline']);
    Route::post('/engagements/workflow/transition', [\App\Http\Controllers\EngagementWorkflowController::class, 'transitionToNextStep']);

    // Architecture 1:N - Appels d'Offres & Marchés
    Route::apiResource('aoos', \App\Http\Controllers\Api\AooController::class);
    Route::get('aoos/{id}/documents/{documentType}', [\App\Http\Controllers\Api\AooController::class, 'downloadDocument']);
    Route::get('aoos/{aoo}/lettres-notification/{fournisseur}/{lot}', [\App\Http\Controllers\Api\AooController::class, 'downloadLettreNotification']);
    Route::post('aoos/{aoo}/ouverture-plis', [\App\Http\Controllers\Api\AooController::class, 'saveOuverturePlis']);
    Route::post('aoos/{id}/cloturer', [\App\Http\Controllers\Api\AooController::class, 'cloturerAoo']);
    Route::post('aoos/{id}/analyse-multi-lots', [\App\Http\Controllers\Api\AooController::class, 'saveAnalyseMultiLots']);
    Route::post('aoos/{id}/attribuer-lots', [\App\Http\Controllers\Api\AooController::class, 'attribuerLots']);
    Route::get('lots/{lot}/export-estimation', [\App\Http\Controllers\Api\LotController::class, 'exportEstimation']);

    Route::apiResource('marches', \App\Http\Controllers\Api\MarcheController::class);
    Route::get('marches/{id}/documents/{documentType}', [\App\Http\Controllers\Api\MarcheController::class, 'downloadDocument']);
    Route::get('marches/{id}/export-pdf', [\App\Http\Controllers\Api\MarcheController::class, 'exportPdf']);
    Route::get('marches/{id}/archive-zip', [\App\Http\Controllers\Api\MarcheController::class, 'downloadArchive']);
    Route::post('marches/{id}/generer-os', [\App\Http\Controllers\Api\MarcheController::class, 'genererOsCommencement']);
    Route::post('marches/{id}/generer-decision', [\App\Http\Controllers\Api\MarcheController::class, 'genererDecisionNomination']);
});

