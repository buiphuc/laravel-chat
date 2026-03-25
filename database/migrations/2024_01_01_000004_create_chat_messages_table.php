<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('chat.table_names.messages', 'chat_messages'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('room_id');
            $table->string('sender_type');
            $table->unsignedBigInteger('sender_id');
            $table->string('type')->default('text');
            $table->text('body')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->index('room_id');
            $table->index(['sender_type', 'sender_id']);
            $table->index('parent_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('chat.table_names.messages', 'chat_messages'));
    }
};
