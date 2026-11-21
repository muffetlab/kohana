<?php

use Kohana\Database\Migrations\Migration;
use Kohana\Database\Schema\Blueprint;
use Kohana\Database\Schema\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('mig_posts', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('mig_posts', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
