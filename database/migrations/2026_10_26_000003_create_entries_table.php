<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('lane_id');
            $table->text('summary');
            $table->json('sources');
            // Empty for cards written without an import file, such as seeded demo data.
            $table->unsignedBigInteger('import_id')->nullable();
            $table->timestamps();

            // One summary card per lane per day; an import replaces it, never adds a second.
            $table->unique(['date', 'lane_id']);
            // Restrict, not cascade: removing a lane or an import must never take diary cards with it.
            $table->foreign('lane_id')->references('id')->on('lanes')->restrictOnDelete();
            $table->foreign('import_id')->references('id')->on('imports')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
