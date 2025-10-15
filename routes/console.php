<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;
use Spatie\OneTimePasswords\Models\OneTimePassword;

Schedule::command('model:prune', ['--model' => [OneTimePassword::class]])->daily();

Schedule::command('passport:purge')->hourly();
