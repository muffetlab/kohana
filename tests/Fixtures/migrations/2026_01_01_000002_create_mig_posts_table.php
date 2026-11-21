<?php

use Kohana\Database\Migrations\Migration;
use Kohana\Database\Schema\Blueprint;
use Kohana\Database\Schema\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('mig_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mig_author_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->boolean('published')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mig_posts');
    }
};
