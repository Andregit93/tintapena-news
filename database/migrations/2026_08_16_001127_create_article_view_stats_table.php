<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_view_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->dateTime('period_start');
            $table->bigInteger('views_count')->default(0);
            $table->timestamps();

            $table->unique(['article_id', 'period_start']);
            $table->index('period_start');
            $table->index('views_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_view_stats');
    }
};
