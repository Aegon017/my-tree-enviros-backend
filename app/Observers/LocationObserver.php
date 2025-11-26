<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\GeocodeLocationJob;
use App\Models\Location;

final class LocationObserver
{
    public function created(Location $location): void
    {
        GeocodeLocationJob::dispatch($location->id);
    }

    public function updating(Location $location): void
    {
        if ($location->isDirty('name')) {
            GeocodeLocationJob::dispatch($location->id);
        }
    }
}
