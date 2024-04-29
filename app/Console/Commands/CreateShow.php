<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Show;

use App\Jobs\Show\CreatePoemJob;
use App\Jobs\Show\CreateHistoryAnswerJob;
use App\Jobs\Show\CreateHistoryAnswerUsaJob;

class CreateShow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-show {show_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creating show by ID';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $show = Show::where('id', '=', $this->argument('show_id'))->firstOrFail();


        if ($show->type == 'poems') {
            CreatePoemJob::dispatch($show);
        } else if ($show->type == 'history_answers') {
            //CreateHistoryAnswerJob::dispatch($show);
            CreateHistoryAnswerJob::dispatchSync($show);
        } else if ($show->type == 'history_answers_usa') {
            CreateHistoryAnswerUsaJob::dispatch($show);
        } else {
            throw new \Exception("Unknown poem type.");
        }
    }
}
