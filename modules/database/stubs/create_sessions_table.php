<?php

use Kohana\Database\Migrations\Migration;
use Kohana\Database\Schema\Blueprint;
use Kohana\Database\Schema\Schema;

/**
 * Table of the database session driver (SESSION_DRIVER=database). The column
 * names match the "columns" setting in config/session.php.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('session_id', 64)->primary();
            $table->unsignedInteger('last_active')->index();
            $table->longText('contents');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
