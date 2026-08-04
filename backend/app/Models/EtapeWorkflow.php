<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtapeWorkflow extends Model
{
    protected $fillable = [
        'type_engagement_id',
        'code',
        'libelle',
        'ordre',
        'est_bloquante'
    ];

    protected $casts = [
        'est_bloquante' => 'boolean',
    ];

    public function typeEngagement()
    {
        return $this->belongsTo(TypeEngagement::class);
    }

    public function documentModeles()
    {
        return $this->hasMany(DocumentModele::class);
    }

    public function etapeInstances()
    {
        return $this->hasMany(EtapeInstance::class);
    }
}
