<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionMembre extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_prenom',
        'fonction',
    ];
}
