<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SponsorRecordTreeInstance extends Model
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
