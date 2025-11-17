<?php

namespace App\Models;

use App\Enums\TreeConditionEnum;
use Illuminate\Database\Eloquent\Model;

class TreeConditionUpdate extends Model
{
    protected $casts = [
        'condition' => TreeConditionEnum::class,
    ];

    public function treeInstance()
    {
        return $this->belongsTo(TreeInstance::class);
    }
}
