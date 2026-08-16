<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('featured_media_id')->nullable()->constrained('media')->nullOnDelete();
            
            $table->string('title', 255);
            $table->text('subtitle')->nullable();
            $table->string('slug', 255)->unique();
            $table->text('excerpt')->nullable();
            $table->text('content')->nullable();
            
            $table->string('status')->default('draft');
            
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            
            $table->string('seo_title', 255)->nullable();
            $table->string('meta_description', 320)->nullable();
            
            $table->bigInteger('views_count')->default(0);
            
            $table->timestamps();
            
            $table->index('status');
            $table->index('published_at');
            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status', 'published_at']);
            $table->index(['region_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
