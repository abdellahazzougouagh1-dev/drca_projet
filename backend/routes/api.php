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
    Route::post('fournisseurs/bulk', [FournisseurController::class, 'bulkStore']);
    Route::apiResource('commission-membres', \App\Http\Controllers\Api\CommissionMembreController::class);
    Route::apiResource('consultations', ConsultationController::class)->only(['index', 'store', 'show', 'destroy']);
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
    Route::match(['get', 'post'], 'consultations/{consultation}/documents/{type}', [\App\Http\Controllers\Api\DocumentController::class, 'generateWithData']);

    // Notifications budgétaires (Module Base)
    Route::apiResource('notifications', \App\Http\Controllers\Api\NotificationController::class);
    Route::get('dashboard/budget', [\App\Http\Controllers\Api\NotificationController::class, 'getDashboardStats']);
    Route::get('notification-lignes', [\App\Http\Controllers\Api\NotificationController::class, 'getLignes']);
    Route::put('notification-lignes/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'updateLigne']);

    Route::apiResource('budgets', BudgetController::class)->only(['index', 'store', 'show']);

    // Workflow Machine à États (Engagements Génériques)
    Route::post('/engagements/workflow', [\App\Http\Controllers\EngagementWorkflowController::class, 'store']);
    Route::get('/engagements/workflow/{id}/timeline', [\App\Http\Controllers\EngagementWorkflowController::class, 'getTimeline']);
    Route::post('/engagements/workflow/transition', [\App\Http\Controllers\EngagementWorkflowController::class, 'transitionToNextStep']);

    // Architecture 1:N - Appels d'Offres & Marchés
    Route::post('aoos/{aoo}/passer-commission', [\App\Http\Controllers\Api\AooController::class, 'passerCommissionOuverture']);
    Route::apiResource('aoos', \App\Http\Controllers\Api\AooController::class);
    Route::post('translate', [\App\Http\Controllers\Api\AooController::class, 'translateFrToAr']);
    Route::post('aoos/{id}/generate-publication-avis', [\App\Http\Controllers\Api\AooController::class, 'generatePublicationAvis']);
    Route::match(['get', 'post'], 'aoos/{id}/documents/{documentType}', [\App\Http\Controllers\Api\AooController::class, 'downloadDocument']);
    Route::get('aoos/{aoo}/documents-generes/{type}', [\App\Http\Controllers\Api\AooController::class, 'generateDocument']);
    Route::get('aoos/{aoo}/lettres-notification/{fournisseur}/{lot}', [\App\Http\Controllers\Api\AooController::class, 'downloadLettreNotification']);

    // Consultation Documents Generation
    Route::post('consultations/{id}/documents/{typeDocument}/generate', [\App\Http\Controllers\ConsultationDocumentController::class, 'generate']);
    Route::get('consultations/documents/{id}/download', [\App\Http\Controllers\ConsultationDocumentController::class, 'download']);

    Route::post('aoos/{aoo}/validate-commission', [\App\Http\Controllers\Api\AooController::class, 'validateCommission']);
    Route::post('aoos/{aoo}/generate-pv-ouverture', [\App\Http\Controllers\Api\AooController::class, 'generatePvOuverture']);
    Route::post('aoos/{aoo}/generate-letters-ecartement', [\App\Http\Controllers\Api\AooController::class, 'generateLettersEcartement']);
    Route::post('aoos/{aoo}/send-lettre-ecartement/{fournisseur}', [\App\Http\Controllers\Api\AooController::class, 'sendLettreEcartement']);
    Route::post('aoos/{aoo}/send-lettres-ecartement', [\App\Http\Controllers\Api\AooController::class, 'sendLettresEcartement']);
    Route::post('aoos/{aoo}/ouverture-plis', [\App\Http\Controllers\Api\AooController::class, 'saveOuverturePlis']);
    Route::post('aoos/{id}/cloturer', [\App\Http\Controllers\Api\AooController::class, 'cloturerAoo']);
    Route::post('aoos/{id}/analyse-multi-lots', [\App\Http\Controllers\Api\AooController::class, 'saveAnalyseMultiLots']);
    Route::post('aoos/{id}/attribuer-lots', [\App\Http\Controllers\Api\AooController::class, 'attribuerLots']);
    Route::get('lots/{lot}/export-estimation', [\App\Http\Controllers\Api\LotController::class, 'exportEstimation']);

    // Phase Liquidation
    Route::get('liquidations/marches', [\App\Http\Controllers\Api\LiquidationController::class, 'marches']);
    Route::get('liquidations/marches/{id}', [\App\Http\Controllers\Api\LiquidationController::class, 'show']);
    Route::post('liquidations/marches/{id}', [\App\Http\Controllers\Api\LiquidationController::class, 'store']);
    Route::put('liquidations/marches/{marcheId}/{liquidationId}', [\App\Http\Controllers\Api\LiquidationController::class, 'update']);
    Route::patch('liquidations/marches/{marcheId}/{liquidationId}/statut', [\App\Http\Controllers\Api\LiquidationController::class, 'updateStatus']);
    Route::post('liquidations/marches/{marcheId}/{liquidationId}/pieces', [\App\Http\Controllers\Api\LiquidationController::class, 'uploadPiece']);
    Route::get('liquidations/marches/{marcheId}/{liquidationId}/pieces/{pieceId}', [\App\Http\Controllers\Api\LiquidationController::class, 'downloadPiece']);
    Route::delete('liquidations/marches/{marcheId}/{liquidationId}/pieces/{pieceId}', [\App\Http\Controllers\Api\LiquidationController::class, 'destroyPiece']);
    Route::get('liquidations/marches/{marcheId}/documents/{type}', [\App\Http\Controllers\Api\LiquidationDocumentController::class, 'generateMarcheDocument']);
    Route::get('liquidations/marches/{marcheId}/{liquidationId}/documents/{type}', [\App\Http\Controllers\Api\LiquidationDocumentController::class, 'generate']);

    Route::apiResource('marches', \App\Http\Controllers\Api\MarcheController::class);
    Route::get('marches/{id}/workflow', [\App\Http\Controllers\Api\MarcheController::class, 'workflowState']);
    Route::post('marches/{id}/workflow/start', [\App\Http\Controllers\Api\MarcheController::class, 'startExecution']);
    
    Route::get('marches/{id}/cloture', [\App\Http\Controllers\Api\ClotureController::class, 'show']);
    Route::post('marches/{id}/cloture', [\App\Http\Controllers\Api\ClotureController::class, 'store']);
    Route::post('marches/{id}/cloture/valider', [\App\Http\Controllers\Api\ClotureController::class, 'valider']);
    Route::get('marches/{id}/cloture/documents/{type}', [\App\Http\Controllers\Api\ClotureDocumentController::class, 'generate']);
    Route::get('marches/{id}/documents/{documentType}', [\App\Http\Controllers\Api\MarcheController::class, 'downloadDocument']);
    Route::get('marches/{id}/export-pdf', [\App\Http\Controllers\Api\MarcheController::class, 'exportPdf']);
    Route::get('marches/{id}/archive-zip', [\App\Http\Controllers\Api\MarcheController::class, 'downloadArchive']);
    Route::post('marches/{id}/generer-os', [\App\Http\Controllers\Api\MarcheController::class, 'genererOsCommencement']);
    Route::post('marches/{id}/generer-decision', [\App\Http\Controllers\Api\MarcheController::class, 'genererDecisionNomination']);

    // Routes de génération PDF pour l'Engagement
    Route::get('marches/{id}/generate/acte', [\App\Http\Controllers\DocumentController::class, 'generateActe']);
    Route::get('marches/{id}/generate/notification', [\App\Http\Controllers\DocumentController::class, 'generateNotification']);
    Route::get('marches/{id}/generate/os', [\App\Http\Controllers\DocumentController::class, 'generateOs']);
    Route::get('marches/{id}/generate/os-arret-reprise', [\App\Http\Controllers\DocumentController::class, 'generateOsArretReprise']);
    Route::get('marches/{id}/generate/bordereau', [\App\Http\Controllers\DocumentController::class, 'generateBordereau']);
    Route::get('marches/{id}/generate/cps', [\App\Http\Controllers\DocumentController::class, 'generateCps']);
    Route::get('marches/{id}/generate/contrat-marche', [\App\Http\Controllers\DocumentController::class, 'generateContrat']);
    Route::get('marches/{id}/generate/designation-agent', [\App\Http\Controllers\DocumentController::class, 'generateDesignationAgent']);
    
    // Upload CPS pour le marché
    Route::post('marches/{id}/upload-cps', [\App\Http\Controllers\Api\MarcheController::class, 'uploadCps']);
});

