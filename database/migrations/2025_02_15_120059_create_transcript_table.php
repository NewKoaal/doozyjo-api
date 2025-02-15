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
        Schema::create('transcript', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->varchar('reminder');
            $table->varchar('note');
            $table->varchar('phone');
            $table->text('summary');
            $table->text('analysis');
            $table->integer('user_id');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transcript');
    }
};
