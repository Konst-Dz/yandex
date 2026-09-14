<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('external_id')->index();
            $table->string('author');
            $table->text('text');
            $table->smallInteger('rating')->nullable();
            $table->timestamp('reviewed_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['organization_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
