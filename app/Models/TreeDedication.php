<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreeDedication extends Model
{
    public function dedicatable()
    {
        return $this->morphTo();
    }
}
