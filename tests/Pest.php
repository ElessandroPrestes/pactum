<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\AssertsApiJson;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

uses(AssertsApiJson::class)->in('Feature');

uses()->beforeEach(function () {
    Sanctum::actingAs(User::factory()->create(), ['*']);
})->in('Feature/Api');
