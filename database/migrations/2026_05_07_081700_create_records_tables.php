<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('records', function (Blueprint $table): void {
            $table->id();
            $table->date('record_date')->unique();
            $table->string('calendar_note', 80)->nullable();
            $table->text('ramblings')->nullable();
            $table->json('health')->nullable();
            $table->json('study')->nullable();
            $table->timestamps();
        });

        Schema::create('record_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('record_id')->constrained()->cascadeOnDelete();
            $table->text('url');
            $table->string('path');
            $table->string('caption')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index('record_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_images');
        Schema::dropIfExists('records');
    }
};
