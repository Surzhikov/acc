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
        Schema::create('ai_talks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('languauge');
            $table->enum('status', ['draft', 'queued', 'process', 'done'])->default('draft');
            $table->string('name');
            $table->string('cover_art')->nullable()->default(null);
            $table->string('cover_image')->nullable()->default(null);
            $table->string('cover_audio')->nullable()->default(null);
            $table->string('cover_video')->nullable()->default(null);
            $table->longText('question_text');
            $table->string('question_audio')->nullable()->default(null);
            $table->string('question_video')->nullable()->default(null);
            $table->longText('answer_text')->nullable()->default(null);
            $table->string('answer_audio')->nullable()->default(null);
            $table->string('answer_video')->nullable()->default(null);
            $table->string('final_video')->nullable()->default(null);
            $table->double('cover_audio_costs', 10, 5)->default(0);
            $table->double('cover_art_costs', 10, 5)->default(0);
            $table->double('question_audio_costs', 10, 5)->default(0);
            $table->double('answer_text_costs', 10, 5)->default(0);
            $table->double('answer_audio_costs', 10, 5)->default(0);
            $table->double('total_costs', 10, 5)->storedAs('cover_audio_costs + cover_art_costs + question_audio_costs + answer_text_costs + answer_audio_costs');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_talks');
    }
};
