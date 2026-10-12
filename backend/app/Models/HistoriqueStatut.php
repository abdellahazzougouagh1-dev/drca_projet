<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoriqueStatut extends Model
{
    protected $fillable = [
        'engagement_id',
        'statut_precedent',
        'statut_suivant',
        'commentaire',
        'user_id'
    ];

    public function engagement()
    {
        return $this->belongsTo(Engagement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
