<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DevTokenSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'development', 'testing')) {
            return;
        }

        $user = User::firstOrCreate(
            ['email' => 'dev@pactum.local'],
            [
                'name' => 'Dev Pactum',
                'password' => bcrypt('change-me-in-dev'),
            ],
        );

        $user->tokens()->where('name', 'dev-token')->delete();

        $token = $user->createToken('dev-token')->plainTextToken;

        $this->command->info('Token de desenvolvimento criado para dev@pactum.local:');
        $this->command->info($token);
    }
}
