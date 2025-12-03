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
        Schema::create('newspapers', function (Blueprint $table) {
            $table->id();
            $table->date('published_date');
            $table->string('file_path');
            $table->string('file_name');
            $table->text('extracted_content')->nullable();
            $table->json('content_embeddings')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('mime_type')->default('image/jpeg');
            $table->timestamps();
            
            $table->index('published_date');
            $table->index(['published_date', 'file_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newspapers');
    }
};
