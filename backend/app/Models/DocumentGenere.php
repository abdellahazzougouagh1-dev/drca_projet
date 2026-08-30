<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentGenere extends Model
{
    protected $fillable = [
        'etape_instance_id',
        'document_modele_id',
        'chemin_pdf',
        'aoo_id',
        'consultation_id',
        'type_document',
        'nom_fichier',
        'chemin_fichier',
        'version',
        'statut',
        'utilisateur_id',
    ];

    public function etapeInstance()
    {
        return $this->belongsTo(EtapeInstance::class);
    }

    public function documentModele()
    {
        return $this->belongsTo(DocumentModele::class);
    }
}
