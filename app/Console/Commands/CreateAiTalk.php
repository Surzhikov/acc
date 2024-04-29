<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\AiTalk;
use App\Jobs\AiTalks\CreateVideoJob;

class CreateAiTalk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-ai-talk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creating and post video for next AiTalk row';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $aiTalk = AiTalk::where('status', '=', 'draft')->orderBy('id', 'asc')->first();
        if ($aiTalk) {
            CreateVideoJob::dispatchSync($aiTalk);
        }
    }
}
