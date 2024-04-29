<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VideoSubtitleCreator;


class TestSubtitles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-subtitles';

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
        $d = storage_path('app/test');
        if (is_dir($d) == false) {
            mkdir($d);
        }

        // Создание экземпляра класса
        $subtitleCreator = new VideoSubtitleCreator();

        // Установка свойств
        $subtitleCreator->setDir($d); // Путь к директории для временных файлов
        $subtitleCreator->setFinalFile(storage_path('app/video1.mov')); // Путь к итоговому видео файлу
        $subtitleCreator->setTime(1); // Общее время субтитров в секундах
        $subtitleCreator->setFps(25); // Частота кадров
        $subtitleCreator->setWidth(1080); // Ширина видео
        $subtitleCreator->setHeight(1920); // Высота видео
        $subtitleCreator->setWordsInBox(3); // Количество слов в каждом кадре
        $subtitleCreator->setCenterX(540); // Координата X для бокса субтитров
        $subtitleCreator->setCenterY(1400); // Координата Y для бокса субтитров
        $subtitleCreator->setBoxColor('#000000');
        $subtitleCreator->setDefaultTextColor('#FFFFFF');
        $subtitleCreator->setHighlightedTextColor('#FFFF00');
        $subtitleCreator->setFontSize(50); // размер шрифта

        // Установка текста субтитров
        $text = "Основательные документы ООН";
        $subtitleCreator->setText($text); // Этот метод вам нужно добавить в класс для установки текста

        // Запуск процесса генерации
        $subtitleCreator->generate();

    }
}
