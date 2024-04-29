<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GeneratePanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-pan-command';

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


        $y0 = 1590; // Начальная координата прокрутки
        $y1 = 2890; // Конечная координата прокрутки
        $t = 1;    // Время прокрутки в секундах
        $fps = 25; // Частота кадров в секунду
        $width = 1512; // Ширина видео
        $height = 982; // Высота видео
        $inputFile = "_screenshot.png"; // Имя файла скриншота
        $outputFile = "s5.mp4"; // Имя выходного файла

        // Расчет скорости прокрутки в пикселях за кадр
        $scrollSpeedPerFrame = ($y1 - $y0) / ($t * $fps);

        // Формирование универсальной строки команды FFmpeg
        $ffmpegCommand = "ffmpeg -loop 1 -i $inputFile -vf \"crop=$width:$height:0:'if(gte(n*$scrollSpeedPerFrame+$y0, ih-$height), ih-$height, max(0, n*$scrollSpeedPerFrame+$y0))'\" -t $t -c:v libx264 -r $fps -pix_fmt yuv420p $outputFile";

        echo "FFmpeg command: \n";
        echo $ffmpegCommand . PHP_EOL;





        $y0 = 1590; // Начальная координата прокрутки
        $y1 = 2890; // Конечная координата прокрутки
        $t = 1;    // Время прокрутки в секундах
        $fps = 25; // Частота кадров в секунду
        $width = 1512; // Ширина видео
        $height = 982; // Высота видео
        $inputFile = "_screenshot.png"; // Имя файла скриншота
        $outputFile = "s5.mp4"; // Имя выходного файла

        // Расчет скорости прокрутки в пикселях за кадр
        $scrollSpeedPerFrame = ($y1 - $y0) / ($t * $fps);

        // Определение направления прокрутки
        $scrollDirection = $y0 > $y1 ? -1 : 1;

        // Формирование универсальной строки команды FFmpeg
        $ffmpegCommand = "ffmpeg -loop 1 -i $inputFile -vf \"crop=$width:$height:0:'if(gte(n*$scrollSpeedPerFrame*$scrollDirection+$y0, ih-$height), ih-$height, max(0, n*$scrollSpeedPerFrame*$scrollDirection+$y0))'\" -t $t -c:v libx264 -r $fps -pix_fmt yuv420p $outputFile";

        echo "FFmpeg command: \n";
        echo $ffmpegCommand . PHP_EOL;






    }
}
