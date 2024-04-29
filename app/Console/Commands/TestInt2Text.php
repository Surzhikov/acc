<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\IntegerToTextConverter;

class TestInt2Text extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-int2-text';

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
        $converter = new IntegerToTextConverter;
        
        $cases = ['nominative', 'genitive', 'dative', 'accusative', 'instrumental', 'prepositional'];

        $y = 1812;


        dump('request: ' . $y);
        foreach ($cases as $case) {
            dump('return: ' . $converter->convert($y, $case));
        }
    }
}
