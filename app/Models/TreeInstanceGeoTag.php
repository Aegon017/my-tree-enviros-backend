<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreeInstanceGeoTag extends Model
{
    public function treeInstance()
    {
        return $this->belongsTo(TreeInstance::class);
    }
}
