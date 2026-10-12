<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reception extends Model
{
    protected $fillable = [
        'consultation_id',
        'type_reception',
        'date_reunion',
        'commission_reception',
        'conformite',
        'reserves_observations'
    ];

    protected $casts = [
        'commission_reception' => 'array',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }
}
