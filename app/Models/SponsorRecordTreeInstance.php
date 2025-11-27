<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SponsorRecordTreeInstance extends Model
{
    public function sponsorRecord(): BelongsTo
    {
        return $this->belongsTo(SponsorRecord::class);
    }

    public function treeInstance(): BelongsTo
    {
        return $this->belongsTo(TreeInstance::class);
    }
}
