<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DevTokenSeeder extends Seeder
{
    private const EMAIL = 'dev@pactum.local';

    private const PASSWORD = 'change-me-in-dev';

    public function run(): void
    {
        if (! app()->environment('local', 'development', 'testing')) {
            return;
        }

        $user = User::firstOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Dev Pactum',
                'password' => bcrypt(self::PASSWORD),
            ],
        );

        // Garante que rodar o seeder de novo nao deixe o usuario com uma
        // senha alterada manualmente — o seed e a fonte de verdade em dev.
        if (! $user->wasRecentlyCreated) {
            $user->forceFill(['password' => bcrypt(self::PASSWORD)])->save();
        }

        $user->tokens()->where('name', 'dev-token')->delete();

        $token = $user->createToken('dev-token')->plainTextToken;

        $this->command->info('Usuario de desenvolvimento pronto:');
        $this->command->line('  email:    '.self::EMAIL);
        $this->command->line('  password: '.self::PASSWORD);
        $this->command->line('  token:    '.$token);
        $this->command->newLine();
        $this->command->comment('Use email/senha para logar pela SPA em http://localhost:8000');
        $this->command->comment('ou exporte o token para chamadas curl: export PACTUM_TOKEN='.$token);
    }
}
