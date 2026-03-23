<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('samples', function (Blueprint $table): void {
            $table->unsignedInteger('rejection_count')->default(0)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('samples', function (Blueprint $table): void {
            $table->dropColumn('rejection_count');
        });
    }
};
