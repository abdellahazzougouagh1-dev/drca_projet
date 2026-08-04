<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeEngagement extends Model
{
    protected $fillable = ['code', 'libelle', 'description'];

    public function etapeWorkflows()
    {
        return $this->hasMany(EtapeWorkflow::class);
    }

    public function engagements()
    {
        return $this->hasMany(Engagement::class);
    }
}
