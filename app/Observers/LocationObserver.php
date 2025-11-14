<?php

namespace App\Observers;

use App\Models\Location;
use App\Jobs\GeocodeLocationJob;

class LocationObserver
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
