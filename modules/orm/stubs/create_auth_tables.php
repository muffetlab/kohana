<?php

use Kohana\Database\DB;
use Kohana\Database\Migrations\Migration;
use Kohana\Database\Schema\Blueprint;
use Kohana\Database\Schema\Schema;

/**
 * Tables of the ORM auth driver: users, roles, roles_users and user_tokens,
 * with the "login" and "admin" roles.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email', 254)->unique();
            $table->string('username', 32)->unique();
            $table->string('password');
            $table->unsignedInteger('logins')->default(0);
            $table->unsignedInteger('last_login')->nullable();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 32)->unique();
            $table->string('description');
        });

        Schema::create('roles_users', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('user_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('user_agent', 40);
            $table->string('token', 40)->unique();
            $table->unsignedInteger('created');
            $table->unsignedInteger('expires')->index();
        });

        DB::insert('roles', ['name', 'description'])
            ->values(['login', 'Login privileges, granted after account confirmation'])
            ->values(['admin', 'Administrative user, has access to everything.'])
            ->execute(Schema::$connection);
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tokens');
        Schema::dropIfExists('roles_users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
    }
};
