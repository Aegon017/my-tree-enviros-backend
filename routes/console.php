<?php

declare(strict_types=1);

use App\Jobs\UpdateProductPricesJob;
use Illuminate\Support\Facades\Schedule;
use Spatie\OneTimePasswords\Models\OneTimePassword;

Schedule::command('model:prune', ['--model' => [OneTimePassword::class]])->daily();

Schedule::job(new UpdateProductPricesJob)->everyFiveMinutes();
