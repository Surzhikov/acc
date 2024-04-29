<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Show;
use App\Jobs\Show\CreateHistoryAnswerJob;


class CreateShows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-shows';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        print 'Введите список тем: ' . PHP_EOL;
        $input = readline();

        $subjects = explode(PHP_EOL, $input);

       

        foreach ($subjects as $subject) {
            $show = new Show;

            $show->name = $subject;
            $show->type = 'history_answers';
            $show->status = 'draft';
            $show->content = $subject;
            $show->voice_id = 'B7pH6KUrQfF0n48XZPGJ';
            $show->music = null;
            $show->save();


            CreateHistoryAnswerJob::dispatch($show);
            print '[' . $show->id . '] cоздано и запущено:  ' . $show->name . PHP_EOL;
        }

    }
}
