<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdoptRecord extends Model
{
    public function treeInstance(): BelongsTo
    {
        return $this->belongsTo(TreeInstance::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
