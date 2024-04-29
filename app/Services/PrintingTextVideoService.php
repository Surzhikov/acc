<?php

namespace App\Services;

use \Imagick;
use \ImagickDraw;
use \ImagickPixel;


class PrintingTextVideoService
{

    private int $width;
    private int $height;
    private string|null $backgroundColor = null;
    private string|null $backgroundImage = null;
    private string $tempDir;
    private int $framerate = 25;

    private int $textBoxX;
    private int $textBoxY;
    private int $textBoxW;
    private int $textBoxH;

    private string $textColor;
    private string $textFontFile;
    private string $textAlign;
    private int $textFontSize;
    private string $text;

    private float $animationTime = 5;
    private int $framesCount;
    private float $framesPerLetterCount;
    
    private $audioFile; // Путь к аудиофайлу

    private $currentFrame;



    public function setWidth(int $width)
    {
        $this->width = $width;
    }

    public function setHeight(int $height)
    {
        $this->height = $height;
    }

    public function setBackgroundColor(string $color): void
    {
        $this->backgroundColor = $color;
    }

    public function setBackgroundImage(string $path): void
    {
        $this->backgroundImage = $path;
    }

    public function setTempDir(string $path): void
    {
        $this->tempDir = $path;
        if (is_dir($this->tempDir) == false){
            mkdir($this->tempDir);
        }
    }

    public function setFramerate(int $rate): void
    {
        $this->framerate = $rate;
    }

    public function setTextBoxX(int $x): void
    {
        $this->textBoxX = $x;
    }

    public function setTextBoxY(int $y): void
    {
        $this->textBoxY = $y;
    }

    public function setTextBoxW(int $w): void
    {
        $this->textBoxW = $w;
    }

    public function setTextBoxH(int $h): void
    {
        $this->textBoxH = $h;
    }

    public function setTextColor(string $color): void
    {
        $this->textColor = $color;
    }

    public function setTextFontFile(string $fontFile): void
    {
        $this->textFontFile = $fontFile;
    }

    public function setTextAlign(string $textAlign): void
    {
        $this->textAlign = $textAlign;
    }

    public function setTextFontSize(int $size): void
    {
        $this->textFontSize = $size;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function setAnimationTime(float $time): void
    {
        $this->animationTime = $time;
    }

    public function setAudio(string $audioPath): void 
    {
        if (file_exists($audioPath)) {
            $this->audioFile = $audioPath;
        } else {
            throw new \Exception("The audio file does not exist: {$audioPath}");
        }
    }


    /**
     * Calculate frames count 
     */
    private function calculateFrameCounts(): void
    {
        $this->framesCount = (int)($this->framerate * $this->animationTime);
        $words = explode(' ', $this->text);
        $this->framesPerWordCount = $this->framesCount / count($words);

        print '-- framesCount: ' .  $this->framesCount . PHP_EOL;
        print '-- framesPerWordCount: ' .  $this->framesPerWordCount . PHP_EOL;
    }


    private function drawTextForFrame(int $frame): void
    {
        $draw = new ImagickDraw();
        $draw->setFont($this->textFontFile);
        $draw->setFontSize($this->textFontSize);
        $draw->setFillColor(new ImagickPixel($this->textColor));
        $draw->setTextAlignment(Imagick::ALIGN_LEFT);

        // Устанавливаем выравнивание текста в соответствии с $this->textAlign
        switch ($this->textAlign) {
            case 'left':
                $draw->setTextAlignment(Imagick::ALIGN_LEFT);
                break;
            case 'center':
                $draw->setTextAlignment(Imagick::ALIGN_CENTER);
                break;
            case 'right':
                $draw->setTextAlignment(Imagick::ALIGN_RIGHT);
                break;
            default:
                $draw->setTextAlignment(Imagick::ALIGN_LEFT); // Устанавливаем выравнивание по умолчанию, если значение неизвестно
                break;
        }

       // Создаем новый объект Imagick для кадра, если он ещё не создан
        if (!$this->currentFrame) {
            $this->currentFrame = new Imagick();
            $this->currentFrame->newImage($this->width, $this->height, new ImagickPixel('transparent'));
        }

        // Если задано фоновое изображение, добавляем его
        if ($this->backgroundImage) {
            $background = new Imagick($this->backgroundImage);
            // Убедимся, что размер фона соответствует размеру кадра
            $background->scaleImage($this->width, $this->height);
            $this->currentFrame->compositeImage($background, Imagick::COMPOSITE_OVER, 0, 0);
            $background->clear(); // Очищаем ресурсы фона
        }

        // Получаем метрики одного символа для расчета высоты строки
        $metrics = $this->currentFrame->queryFontMetrics($draw, 'W');
        $lineHeight = $metrics['textHeight'] * 1.2;

        // Определение количества слов для показа
        $wordsToShow = (int)floor($frame / $this->framesPerWordCount) + 1;
        $textToShow = implode(' ', array_slice(explode(' ', $this->text), 0, $wordsToShow));

        if ($frame == $this->framesCount - 1) {
            $textToShow = $this->text;
        }

        // Инициализация переменных для разбивки текста по строкам
        $wrappedText = '';
        $currentLine = '';
        $yOffset = $this->textBoxY;

        foreach (explode("\n", $textToShow) as $paragraph) {
            foreach (explode(' ', $paragraph) as $word) {
                // Проверяем, поместится ли слово в текущую строку
                $testLine = $currentLine ? "$currentLine $word" : $word;
                $metrics = $this->currentFrame->queryFontMetrics($draw, $testLine);

                if ($metrics['textWidth'] <= $this->textBoxW) {
                    $currentLine = $testLine;
                } else {
                    // Добавляем текущую строку и начинаем новую
                    $wrappedText .= $currentLine . "\n";
                    $currentLine = $word;
                    $yOffset += $lineHeight; // Увеличиваем yOffset только когда начинаем новую строку
                }
            }
            // Добавляем последнюю строку после обработки каждого параграфа
            $wrappedText .= $currentLine . "\n";
            $currentLine = ''; // Сбрасываем текущую строку для следующего параграфа
            $yOffset += $lineHeight; // Увеличиваем yOffset для параграфа
        }

        // Выводим текст на изображение
        $this->currentFrame->annotateImage($draw, $this->textBoxX, $this->textBoxY, 0, $wrappedText);

        // Очищаем ресурсы
        $draw->clear();
    }




    /**
     * 
     */
    private function saveFrame($frameNumber)
    {
        $frameFilename = sprintf("frame_%05d.png", $frameNumber); // форматируем имя файла с ведущими нулями
        $framePath = $this->tempDir . DIRECTORY_SEPARATOR . $frameFilename;
        $this->currentFrame->writeImage($framePath); // Предполагаем, что $this->currentFrame - это объект Imagick с текущим кадром
    }


    /**
     * Создание фрейма
     */
    public function createFrame($n)
    {
        $this->currentFrame = new Imagick();
        $this->currentFrame->newImage($this->width, $this->height, new ImagickPixel($this->backgroundColor));
        $this->drawTextForFrame($n);
        $this->saveFrame($n); // Сохраняем кадр
        $this->currentFrame->clear(); // Очищаем ресурсы изображения
    }



    public function createVideo($videoFilename): void 
    {
        $this->calculateFrameCounts();
        
        // Создаем каждый кадр и сохраняем его
        for ($frame = 0; $frame < $this->framesCount; $frame++) {
            print 'Frame #' . ($frame+1)  . '/' . $this->framesCount . PHP_EOL;
            $this->createFrame($frame);
            print "\033[1A\033[K";
        }
        
        // Путь к шаблону кадров
        $framePattern = $this->tempDir . DIRECTORY_SEPARATOR . "frame_%05d.png";

        // Строим команду для ffmpeg, сначала указываем параметры входных файлов
        $ffmpegCmd = "ffmpeg -framerate {$this->framerate} -i {$framePattern}";

        // Добавляем аудиофайл, если он был задан
        if ($this->audioFile) {
            // Используем escapeshellarg для безопасного включения пути к файлу
            $ffmpegCmd .= " -i " . escapeshellarg($this->audioFile);
        }

        // Добавляем параметры кодирования для видео и аудио
        $ffmpegCmd .= " -c:v libx264 -pix_fmt yuv420p -c:a aac";

        // Указываем выходной файл
        $ffmpegCmd .= " -y " . escapeshellarg($videoFilename);

        print $ffmpegCmd . PHP_EOL;

        // Выполняем команду
        shell_exec($ffmpegCmd);
    }





}

