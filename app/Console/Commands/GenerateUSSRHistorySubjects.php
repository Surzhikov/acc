<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GptService;


class GenerateUSSRHistorySubjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-ussr-history-subjects';

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

        $prompt = 'Я создаю научно-популярные видео по истории, длиной до 1 минуты. ' . PHP_EOL;
        $prompt.= 'Для каждой из трех эпох и каждой из 12 категорий и сформулируй по одной теме для короткого видео.' . PHP_EOL;
        $prompt.= 'Сформулируй тему максимально простыми словами' . PHP_EOL;
        $prompt.= 'В ответе пришли список из 36 узких конкретных тем (без дополнительного указания эпохи и категории!). Не используй знак двоеточия ":" в называниях тем. ' . PHP_EOL . PHP_EOL . PHP_EOL;
        $prompt.= 'Эпохи:' . PHP_EOL;
        $prompt.= '- Российская империя' . PHP_EOL;
        $prompt.= '- СССР' . PHP_EOL;
        $prompt.= '- Россия' . PHP_EOL . PHP_EOL . PHP_EOL;
        $prompt.= 'Категории:' . PHP_EOL;
        $prompt.= '- Образование' . PHP_EOL;
        $prompt.= '- Спорт и здоровье' . PHP_EOL;
        $prompt.= '- Наука' . PHP_EOL;
        $prompt.= '- Космос' . PHP_EOL;
        $prompt.= '- Отношения' . PHP_EOL;
        $prompt.= '- Быт, нравы и традиции' . PHP_EOL;
        $prompt.= '- Промышленность и достижения народного хозяйства' . PHP_EOL;
        $prompt.= '- Внутренняя и внешняя политика' . PHP_EOL;
        $prompt.= '- Войны, военные операции, битвы' . PHP_EOL;
        $prompt.= '- Известные личности и их заслуги' . PHP_EOL;
        $prompt.= '- Необычные случаи и истории' . PHP_EOL;
        $prompt.= '- Культура' . PHP_EOL . PHP_EOL . PHP_EOL;
        $prompt.= 'Примеры тем:' . PHP_EOL;
        $prompt.= '- Чем прославился исследователь Миклухо-Маклай?' . PHP_EOL;
        $prompt.= '- Что такое Карибский Кризис?' . PHP_EOL;
        $prompt.= '- Что могло быть, если бы СССР не распался' . PHP_EOL;
        $prompt.= '- Как Сталин изменил СССР?' . PHP_EOL;
        $prompt.= '- Восстание Декабристов 1825 года: Первая революция в России' . PHP_EOL;
        $prompt.= '- Почему произошла Октябрьская революция?' . PHP_EOL;
        $prompt.= '- Как Советская система образования смогла воспитать академиков из детей простых крестьян?' . PHP_EOL;
        $prompt.= '- Отмена крепостного права в России. Начало новой эры.' . PHP_EOL;
        $prompt.= '- Александр Медведь: Легенда Олимпийской Борьбы' . PHP_EOL;
        $prompt.= '- Как альпинисты покоряли вершины СССР' . PHP_EOL;
        $prompt.= '- Битва за Сталинград' . PHP_EOL;
        $prompt.= '- Как раскулачивали крестьян в СССР' . PHP_EOL;
        $prompt.= '- Альпинисты СССР: покорение Пика Победы в 1956 году' . PHP_EOL;
        $prompt.= '- Как советские альпинисты покоряли Эверест?' . PHP_EOL;
        $prompt.= '- Катарина Витт на Олимпийских играх в Сараево' . PHP_EOL;
        $prompt.= '- Почему в СССР запрещали религию?' . PHP_EOL;
        $prompt.= '- Как закончилась карьера Ричарда Никсона (37-й президент США)' . PHP_EOL;
        $prompt.= '- Советско-Финская война 1939 года' . PHP_EOL;
        $prompt.= '- Восхождение на вершину "Пик Коммунизма"' . PHP_EOL;

        print '- Propmpt for GPT:' . PHP_EOL;
        print $prompt . PHP_EOL . PHP_EOL;

        print '- Requesting GPT...' . PHP_EOL;
        $gptService = new GptService;
        //$gptService->setModel('gpt-4-1106-preview');
        //$gptService->asJson = true;
        $gptService->setModel('gpt-4');
        $answer = $gptService->request($prompt);
        dd($answer);



    }
}
