<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Location;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

final class GeocodeLocationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $locationId) {}

    public function handle(): void
    {
        $location = Location::with('parent')->find($this->locationId);

        if (! $location) {
            return;
        }

        $query = $this->buildFullAddress($location);

        $response = Http::withHeaders([
            'User-Agent' => 'YourApp/1.0',
        ])
            ->timeout(15)
            ->get('https://nominatim.openstreetmap.org/search', [
                'format' => 'json',
                'q' => $query,
            ])
            ->json();

        logger('Geocode response for location '.$location->name, $response);

        if (! empty($response)) {
            $location->latitude = (float) $response[0]['lat'];
            $location->longitude = (float) $response[0]['lon'];
            $location->saveQuietly();
        }
    }

    private function buildFullAddress(Location $location): string
    {
        $parts = [$location->name];

        $parent = $location->parent;
        while ($parent) {
            $parts[] = $parent->name;
            $parent = $parent->parent;
        }

        $parts[] = 'India';

        return implode(', ', $parts);
    }
}
