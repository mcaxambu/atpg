<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_items', function (Blueprint $table) {
            $table->id();
            $table->string('module', 80)->index();
            $table->string('title');
            $table->string('slug');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('link_url')->nullable();
            $table->string('button_label')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['module', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_items');
    }
};
