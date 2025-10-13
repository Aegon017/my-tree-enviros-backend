<?php

use Illuminate\Support\Facades\Schedule;
use Spatie\OneTimePasswords\Models\OneTimePassword;

Schedule::command('model:prune', [
    '--model' => [OneTimePassword::class],
])->daily();