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
        Schema::create('episodes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('show_id')->constrained('shows', 'id');
            $table->longText('name')->nullable()->default(null);
            $table->longText('text')->nullable()->default(null);
            $table->longText('image_prompt')->nullable()->default(null);
            $table->string('image')->nullable()->default(null);
            $table->string('voice')->nullable()->default(null);
            $table->string('video')->nullable()->default(null);
            $table->double('prompt_cost', 10, 5)->default(0);
            $table->double('voice_cost', 10, 5)->default(0);
            $table->double('image_cost', 10, 5)->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
