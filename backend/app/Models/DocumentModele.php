<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentModele extends Model
{
    protected $fillable = [
        'etape_workflow_id',
        'code',
        'libelle',
        'chemin_blade'
    ];

    public function etapeWorkflow()
    {
        return $this->belongsTo(EtapeWorkflow::class);
    }
}
