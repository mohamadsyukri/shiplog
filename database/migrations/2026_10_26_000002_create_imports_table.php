<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table): void {
            $table->id();
            $table->string('file_name');
            $table->date('range_from');
            $table->date('range_to');
            $table->string('format', 20);
            $table->string('source', 20);
            $table->unsignedInteger('lanes_upserted')->default(0);
            $table->unsignedInteger('entries_created')->default(0);
            $table->unsignedInteger('entries_replaced')->default(0);
            // Empty for the shiplog:import command, which runs with no signed-in user.
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            // Users are deactivated, never deleted, so the uploader always exists.
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
