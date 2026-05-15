<?php

declare(strict_types=1);

use Tests\Concerns\AssertsApiJson;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

uses(AssertsApiJson::class)->in('Feature');
