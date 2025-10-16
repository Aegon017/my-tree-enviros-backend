<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TreeMedia extends Model
{
    public function treeInstance(): BelongsTo
    {
        return $this->belongsTo(TreeInstance::class);
    }
}
