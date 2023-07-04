<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('storage', function (Blueprint $table) {
            $table->id();
            $table->string('item_id')->unique();
            $table->string('user_id');
            $table->string('name');
            $table->string('type')->default('folder');
            $table->string('real_path')->nullable();
            $table->string('access')->default('private');
            $table->string('belongs_to')->default('root');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage');
    }
};
