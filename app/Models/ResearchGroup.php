<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ResearchGroup extends Model
{
    protected $guarded = [];

    public function currentProject(): HasOne
    {
        return $this->hasOne(ResearchProject::class)->latestOfMany();
    }
}
