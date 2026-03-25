<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('chat.table_names.blocked_users', 'chat_blocked_users'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('blocker_type');
            $table->unsignedBigInteger('blocker_id');
            $table->string('blocked_type');
            $table->unsignedBigInteger('blocked_id');
            $table->string('reason')->nullable();
            $table->dateTime('created_at')->nullable();

            $table->unique(['blocker_type', 'blocker_id', 'blocked_type', 'blocked_id'], 'chat_blocked_unique');
            $table->index(['blocker_type', 'blocker_id']);
            $table->index(['blocked_type', 'blocked_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('chat.table_names.blocked_users', 'chat_blocked_users'));
    }
};
