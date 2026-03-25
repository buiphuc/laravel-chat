<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('chat.table_names.participants', 'chat_participants'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('room_id');
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id');
            $table->unsignedBigInteger('role_id');
            $table->dateTime('joined_at');
            $table->dateTime('last_read_at')->nullable();
            $table->boolean('is_muted')->default(false);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['room_id', 'actor_type', 'actor_id']);
            $table->index(['actor_type', 'actor_id']);
            $table->index('room_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('chat.table_names.participants', 'chat_participants'));
    }
};
