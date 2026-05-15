<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')
                ->constrained('contracts')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('evento');
            $table->json('payload');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('unique_id')->unique();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['contract_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_histories');
    }
};
