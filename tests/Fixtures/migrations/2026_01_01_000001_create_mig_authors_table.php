<?php

use Kohana\Database\Migrations\Migration;
use Kohana\Database\Schema\Blueprint;
use Kohana\Database\Schema\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('mig_authors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mig_authors');
    }
};
