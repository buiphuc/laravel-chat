<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('chat.table_names.message_reports', 'chat_message_reports'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('message_id');
            $table->string('reporter_type');
            $table->unsignedBigInteger('reporter_id');
            $table->string('reason');
            $table->string('status')->default('pending');
            $table->string('reviewer_type')->nullable();
            $table->unsignedBigInteger('reviewer_id')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index('message_id');
            $table->index(['reporter_type', 'reporter_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('chat.table_names.message_reports', 'chat_message_reports'));
    }
};
