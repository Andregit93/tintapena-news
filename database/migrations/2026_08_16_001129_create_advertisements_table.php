<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('type');
            $table->string('placement_key', 100);
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->text('content')->nullable();
            $table->string('target_url', 500)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('placement_key');
            $table->index('is_active');
            $table->index('starts_at');
            $table->index('ends_at');
            $table->index(['placement_key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
