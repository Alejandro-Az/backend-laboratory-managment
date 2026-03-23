<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sample_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_id')->constrained('samples')->cascadeOnDelete();
            $table->foreignId('analyst_id')->constrained('users')->restrictOnDelete();
            $table->text('result_summary')->nullable();
            $table->json('result_data')->nullable();
            $table->timestamps();

            $table->index('sample_id');
            $table->index('analyst_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sample_results');
    }
};
