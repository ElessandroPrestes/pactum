<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')
                ->constrained('clients')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->string('status')->default('ativo');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
