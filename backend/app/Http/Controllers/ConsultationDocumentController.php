<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Consultation;
use App\Models\DocumentGenere;
use App\Services\DocumentGenerationService;
use Illuminate\Support\Facades\Storage;

class ConsultationDocumentController extends Controller
{
    public function generate(Request $request, $id, $typeDocument)
    {
        $consultation = Consultation::with(['prestations', 'budget'])->findOrFail($id);

        try {
            $extraData = $request->all();
            $document = DocumentGenerationService::generate($consultation, $typeDocument, $extraData);

            return response()->json([
                'message' => 'Document généré avec succès',
                'document' => $document
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la génération: ' . $e->getMessage()
            ], 500);
        }
    }

    public function download($id)
    {
        $document = DocumentGenere::findOrFail($id);
        $path = storage_path('app/' . $document->chemin_fichier);

        if (!file_exists($path)) {
            return response()->json(['error' => 'Fichier introuvable'], 404);
        }

        return response()->download($path, $document->nom_fichier);
    }
}
