<?php

namespace App\Services;

use \Imagick;
use \ImagickDraw;
use \ImagickPixel;

class VideoSubtitleCreator
{
    private string $dir;
    private string $finalFile;
    private float $time;
    private int $fps;
    private int $width;
    private int $height;
    private int $wordsInBox;
    
    private int $centerX;
    private int $centerY;

    private string $boxColor;
    private string $defaultTextColor;
    private string $highlightedTextColor;

    private int $fontSize;
    private float $frameCount;

    public function __construct() {
        // Инициализация начальных значений
    }


    /**
     * Временная директория
     */
    public function setDir(string $dir) {
        if (!is_dir($dir)) {
            throw new \Exception("Directory does not exist: $dir");
        }
        $this->dir = $dir;
    }


    /**
     * Путь к финальному файлу
     */
    public function setFinalFile(string $finalFile) {
        $pathInfo = pathinfo($finalFile);
        if ($pathInfo['extension'] !== 'mov') {
            throw new \Exception("Final file must be a .mov file.");
        }
        $this->finalFile = $finalFile;
    }

    
    /**
     * FPS
     */
    public function setTime(float $time) {
        if ($time <= 0) {
            throw new \Exception("Time must be greater than zero.");
        }
        $this->time = $time;
    }

    
    /**
     *  
     */
    public function setFps(int $fps) {
        if ($fps <= 0) {
            throw new \Exception("FPS must be greater than zero.");
        }
        $this->fps = $fps;
    }

    
    /**
     * X центра субтитров 
     */
    public function setCenterX(int $centerX) {
        if ($centerX <= 0) {
            throw new \Exception("X must be greater than zero.");
        }
        $this->centerX = $centerX;
    }

    
    /**
     * Y центра субтитров
     */
    public function setCenterY(int $centerY) {
        if ($centerY <= 0) {
            throw new \Exception("Y must be greater than zero.");
        }
        $this->centerY = $centerY;
    }

    
    /**
     * Количество слов в боксе
     */
    public function setWordsInBox(int $wordsInBox) {
        if ($wordsInBox <= 0) {
            throw new \Exception("Words in box must be greater than zero.");
        }
        $this->wordsInBox = $wordsInBox;
    }

    
    /**
     * Ширина видео
     */
    public function setWidth(int $width) {
        $this->width = $width;
    }

    
    /**
     * Высота видео
     */
    public function setHeight(int $height) {
        $this->height = $height;
    }


    
    /**
     * Цвет фона бокса
     */
    public function setBoxColor(string $boxColor) {
        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $boxColor)) {
            throw new \Exception("Invalid box color format.");
        }
        $this->boxColor = $boxColor;
    }


    /**
     * Цвет текст
     */
    public function setDefaultTextColor(string $defaultTextColor) {
        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $defaultTextColor)) {
            throw new \Exception("Invalid text color format.");
        }
        $this->defaultTextColor = $defaultTextColor;
    }


    /**
     * Цвет текст
     */
    public function setHighlightedTextColor(string $highlightedTextColor) {
        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $highlightedTextColor)) {
            throw new \Exception("Invalid text color format.");
        }
        $this->highlightedTextColor = $highlightedTextColor;
    }

    /**
     * Устанавливает размер шрифта
     */
    public function setFontSize(int $fontSize) {
        $this->fontSize = $fontSize;
    }

    /**
     * Устанавливает текст для субтитров
     */
    public function setText(string $text) {
        $this->text = $text;
    }

    /**
     * Генерировать видео 
     */
    public function generate()
    {
        // Генерация кадров
        //$this->generateFrames();
        $this->generateFrames();

        // Создание видео с использованием ffmpeg
        $this->createVideoFromFrames();
    }


    /**
     * Создание фреймов 
     */
    private function generateFrames()
    {
        $words = explode(' ', $this->text);;
        $wordsGroups = array_chunk($words, $this->wordsInBox);
        $totalCharacters = mb_strlen(str_replace([' ', '«', '»', '.', ',', '-'], '', $this->text));
        $this->frameCount = $this->time * $this->fps; // Общее количество кадров
        $framesPerSymbol = $this->frameCount / $totalCharacters;

        $n = 0;

        foreach($wordsGroups as $wordsGroup) {

            $subtitles = [];
            foreach($wordsGroup as $word) {
                $subtitles[]= [
                    'text' => $word,
                    'color' => $this->defaultTextColor
                ];
            }

            foreach($subtitles as &$subtitle) {

                $subtitle['color'] = $this->highlightedTextColor;

                $framesCount = round(mb_strlen(str_replace([' ', '«', '»', '.', ',', '-'], '', $subtitle['text'])) * $framesPerSymbol);

                for ($i=0; $i < $framesCount; $i++) { 
                    $this->createFrame($subtitles, $n);
                    $n++;
                }
            }
        }

        if ($n < $this->frameCount) {
            for ($i=0; $i < ($this->frameCount - $n) ; $i++) { 
                $this->createFrame($subtitles, $n);
                $n++;
            }
        }
    }



    private $previousFrameSubtitles = null;
    private $previousFramePath = null;

    private function createFrame($subtitles, $frameNumber)
    {
        //print '-------------------' . PHP_EOL;
        //print 'Create frame #' . $frameNumber  . PHP_EOL;

        $framePath = $this->dir . '/frame_' . $frameNumber . '.png';

        if ($subtitles === $this->previousFrameSubtitles && $this->previousFramePath != null) {

            //print 'Frame subtitles is same as a previous! Copy file!' . PHP_EOL;
            copy($this->previousFramePath, $framePath);

        } else {

            //print 'Create frame via Imagick' . PHP_EOL;

            $frame = new \Imagick();
            $frame->newImage($this->width, $this->height, new \ImagickPixel('transparent'));
            $frame->setImageFormat('png');

            $fontPath = resource_path('fonts/OpenSans-Bold.ttf');
            $draw = new ImagickDraw();
            $draw->setFont($fontPath);
            $draw->setFontSize($this->fontSize);

            // Объединяем все тексты и разделяем на слова
            $fullText = implode(' ', array_map(function ($subtitle) { return $subtitle['text']; }, $subtitles));
            $words = explode(' ', $fullText);

            // Подготовка к отрисовке текста для вычисления общей ширины
            $totalWidth = 0;
            $lineWidth = 0;
            $lineHeight = $this->fontSize;
            $spaceWidth = $this->fontSize / 3;
            foreach ($words as $w => $word) {
                $metrics = $frame->queryFontMetrics($draw, $word);
                $lineWidth += $metrics['textWidth'] + $spaceWidth;
                if ($lineWidth > $this->width) {
                    $totalWidth = max($totalWidth, $lineWidth - $metrics['textWidth'] - $spaceWidth);
                    $lineWidth = $metrics['textWidth'] + $spaceWidth;
                    $lineHeight += $this->fontSize * 1.2;
                }
            }
            $totalWidth = max($totalWidth, $lineWidth);

            // Центрирование текста и добавление фона
            $boxX = $this->centerX - ($totalWidth / 2);
            $boxY = $this->centerY - ($lineHeight / 2);
            $draw->setFillColor(new \ImagickPixel($this->boxColor));
            $draw->rectangle($boxX - 10, $boxY - 5, $boxX + $totalWidth + 10, $boxY + $lineHeight + 10);

            // Располагаем текст на изображении
            $currentX = $boxX; // Центрируем текст по горизонтали относительно бокса
            $textY = $boxY + $this->fontSize; // Центрируем текст по вертикали относительно бокса

            $wordIndex = 0;
            foreach ($words as $w => $word) {
                $color = $subtitles[$wordIndex]['color'] ?? $this->textColor;
                $wordIndex = ($wordIndex + 1) % count($subtitles);
                
                // Получаем размеры слова для обновления текущей позиции X
                $metrics = $frame->queryFontMetrics($draw, $word);

                //print '# ' . $word . PHP_EOL;
                //print 'currentX = ' . $currentX . PHP_EOL;
                //print 'textWidth = ' . $metrics['textWidth'] . PHP_EOL;
                //print 'spaceWidth = ' . $spaceWidth . PHP_EOL;
                //print 'boxX = ' . $boxX . PHP_EOL;
                //print 'totalWidth = ' . $totalWidth . PHP_EOL;
                //print 'A = ' . ($currentX + $metrics['textWidth'] + $spaceWidth) . PHP_EOL;
                //print 'B = ' . ($boxX + $totalWidth) . PHP_EOL . PHP_EOL . PHP_EOL;
    
                // Перенос строки, если достигли максимальной ширины
                if (floor($currentX + $metrics['textWidth'] + $spaceWidth) > floor($boxX + $totalWidth)) {
                    $currentX = $boxX;
                    $textY += $this->fontSize * 1.2;
                }

                $draw->setFillColor(new \ImagickPixel($color));
                $draw->annotation($currentX, $textY, $word);

                $currentX += $metrics['textWidth'] + $spaceWidth;
            }


            $frame->drawImage($draw);


            // Сохраняем изображение
            $framePath = $this->dir . '/frame_' . $frameNumber . '.png';
            $frame->writeImage($framePath);

            // Освобождаем ресурсы
            $draw->clear();
            $draw->destroy();
            $frame->clear();
            $frame->destroy();
  
        }



        $this->previousFrameSubtitles = array_slice($subtitles, 0);
        $this->previousFramePath = $framePath;
    }



   private function wrapText($text, $draw, $maxWidth) {
        $words = explode(' ', $text);
        $wrappedText = '';
        $line = '';

        $imagick = new \Imagick(); // Создаем временный объект Imagick

        foreach ($words as $word) {
            $testLine = $line . ' ' . $word;
            $metrics = $imagick->queryFontMetrics($draw, $testLine); // Используем временный объект Imagick для вычисления метрик
            if ($metrics['textWidth'] <= $maxWidth) {
                $line = $testLine;
            } else {
                if ($line !== '') {
                    $wrappedText .= $line . "\n";
                }
                $line = $word;
            }
        }

        if ($line !== '') {
            $wrappedText .= $line;
        }

        return trim($wrappedText);
    }



    /**
     * Деление текста на блоки
     */
    private function splitTextIntoWords()
    {
        return explode(' ', $this->text);
    }


    /**
     * Создание видео из фреймов
     */
    private function createVideoFromFrames() {
        $ffmpegCommand = $this->buildFfmpegCommand();
        exec($ffmpegCommand);
    }



    private function buildFfmpegCommand()
    {
        $inputPath = $this->dir . "/frame_%d.png"; // Путь к кадрам
        $outputPath = $this->finalFile; // Путь к итоговому файлу

        // Строим команду для ffmpeg
        $ffmpegCommand = "ffmpeg -loglevel quiet -y -framerate " . $this->fps . " -i " . escapeshellarg($inputPath) .
                         " -c:v prores_ks -profile:v 4444 " .
                         " -pix_fmt yuva444p10le " . // Формат пикселей с поддержкой альфа-канала
                         escapeshellarg($outputPath);

        return $ffmpegCommand;
    }

}
