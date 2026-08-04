<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtapeInstance extends Model
{
    protected $fillable = [
        'engagement_id',
        'etape_workflow_id',
        'statut',
        'date_realisation'
    ];

    protected $casts = [
        'date_realisation' => 'datetime',
    ];

    public function engagement()
    {
        return $this->belongsTo(Engagement::class);
    }

    public function etapeWorkflow()
    {
        return $this->belongsTo(EtapeWorkflow::class);
    }

    public function documentGeneres()
    {
        return $this->hasMany(DocumentGenere::class);
    }
}
