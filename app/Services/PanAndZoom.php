<?php

namespace App\Services;

class PanAndZoom
{
    private string $photoPath;
    private string $outputVideoPath;
    private int $fps;
    private float $time;
    private int $fromX;
    private int $fromY;
    private int $fromW;
    private int $fromH;
    private int $toX;
    private int $toY;
    private int $toW;
    private int $toH;
    private int $videoWidth;
    private int $videoHeight;

    public function __construct() {}

    /**
     * Set photo path
     */
    public function setPhotoPath(string $photoPath): void {
        if (!file_exists($photoPath)) {
            throw new \Exception("Photo file does not exist.");
        }
        $this->photoPath = $photoPath;
    }


    /**
     * Set output video path
     */
    public function setOutputVideoPath(string $outputVideoPath): void {
        // Здесь может быть дополнительная валидация, например, проверка расширения файла
        $this->outputVideoPath = $outputVideoPath;
    }


    /**
     * Set FPS
     */
    public function setFps(int $fps): void {
        if ($fps <= 0) {
            throw new \Exception("FPS must be greater than 0.");
        }
        $this->fps = $fps;
    }


    /**
     * Set final video time
     */
    public function setTime(float $time): void {
        if ($time <= 0) {
            throw new \Exception("Time must be greater than 0.");
        }
        $this->time = $time;
    }


    /**
     * Set start box
     */
    public function setFromBox(int $fromX, int $fromY, int $fromW, int $fromH): void {
        $this->setCoordinate('fromX', $fromX);
        $this->setCoordinate('fromY', $fromY);
        $this->setDimension('fromW', $fromW);
        $this->setDimension('fromH', $fromH);
    }


    /**
     * Set finish box
     */
    public function setToBox(int $toX, int $toY, int $toW, int $toH): void {
        $this->setCoordinate('toX', $toX);
        $this->setCoordinate('toY', $toY);
        $this->setDimension('toW', $toW);
        $this->setDimension('toH', $toH);
    }


    /**
     * Set coordinate
     */
    private function setCoordinate(string $property, int $value): void {
        if ($value < 0) {
            throw new \Exception("Coordinate $property must be non-negative.");
        }
        $this->$property = $value;
    }


    /**
     * Set dimension
     */
    private function setDimension(string $property, int $value): void {
        if ($value <= 0) {
            throw new \Exception("Dimension $property must be greater than 0.");
        }
        $this->$property = $value;
    }

    /**
     * Set video dimensions
     */
    public function setVideoDimensions(int $width, int $height): void {
        $this->videoWidth = $width;
        $this->videoHeight = $height;
    }


    public function generateFfmpegCommandOld(): string {
        // Рассчитываем коэффициенты изменения координат обрезки
        $dx = ($this->toX - $this->fromX) / $this->time;

        $ffmpegCmd = "ffmpeg -loglevel quiet -y -loop 1 -i {$this->photoPath} -vf \"";

        // Масштабирование изображения до высоты видео с сохранением пропорций
        $ffmpegCmd .= "scale=-2:{$this->videoHeight}, ";

        // Анимация панорамирования с учетом начальной и конечной области обрезки
        $ffmpegCmd .= "crop={$this->fromW}:{$this->fromH}:";
        $ffmpegCmd .= "x='if(gte(t,{$this->time}),{$this->toX},{$this->fromX}+t*{$dx})':y=0, ";
        $ffmpegCmd .= "setpts=PTS-STARTPTS\" ";  // Установка начальной точки времени
        $ffmpegCmd .= "-t {$this->time} -r {$this->fps} -vcodec prores_ks -profile:v 3 -pix_fmt yuv422p10le {$this->outputVideoPath}";

        return $ffmpegCmd;
    }


public function generateFfmpegCommand(): string
{
    $imageInfo = getimagesize($this->photoPath);
    $imageW = $imageInfo[0]; // 3360
    $imageH = $imageInfo[1]; // 1920

    $imageRatio = $imageW / $imageH;
    $videoRatio = $this->videoWidth / $this->videoHeight;

    if ($imageRatio > $videoRatio) {
        $scaleH = $this->videoHeight;
        $scaleW = $imageW * ($scaleH / $imageH);
    } else {
        $scaleW = $this->videoWidth;
        $scaleH = $imageH * ($scaleW / $imageW);
    }

    if ($imageRatio > $videoRatio) {
        $padH = $scaleW / $videoRatio; 
        $padW = $scaleW;
    } else {
        $padW = $scaleH * $videoRatio;
        $padH = $scaleH;
    }

    $this->fromX = $this->fromX * ($scaleW / $imageW);
    $this->fromY = $this->fromY * ($scaleH / $imageH);
    $this->fromW = $this->fromW * ($scaleW / $imageW);
    $this->fromH = $this->fromH * ($scaleH / $imageH);

    $this->toX = $this->toX * ($scaleW / $imageW);
    $this->toY = $this->toY * ($scaleH / $imageH);
    $this->toW = $this->toW * ($scaleW / $imageW);
    $this->toH = $this->toH * ($scaleH / $imageH);


    // Рассчитываем коэффициент зума
    $zoomFactor = $this->fromW / $this->toW;
    $totalFrames = round($this->fps * $this->time);

    if ($this->fromW > $this->toW) {
        // Увеличение
        $zoomStart = ($padH / $this->fromH);
        $zoomFinish = ($padH / $this->toH);
        $zoomChange = $zoomFinish - $zoomStart;
        $zoomStep = $zoomChange / $totalFrames;
        $z = "if(lte(on,1)," . $zoomStart . ",min(zoom+" . $zoomStep . "," . $zoomFinish . "))";
    } else {
        // Уменьшение
        $zoomStart = ($padH / $this->fromH);
        $zoomFinish = ($padH / $this->toH);
        $zoomChange = $zoomStart - $zoomFinish;
        $zoomStep = $zoomChange / $totalFrames;
        $z = "if(lte(on,1)," . $zoomStart . ",max(zoom-" . $zoomStep . "," . $zoomFinish . "))";
    }


    // Рассчитываем изменения координат x на каждый кадр
    if ($this->fromX < $this->toX) {
        // Движение вправо
        $dx = ($this->toX - $this->fromX) / $totalFrames;
        $x = "if(gte(on," . $totalFrames . "), " . $this->toX .", " . $this->fromX . "+on*" . $dx . ")";
    } else {
        // Движение влево
        $dx = ($this->fromX - $this->toX) / $totalFrames;
        $x = "if(gte(on," . $totalFrames . "), " . $this->toX .", " . $this->fromX . "-on*" . $dx . ")";
    }


    // Рассчитываем изменения координат x на каждый кадр
    if ($this->fromY < $this->toY) {
        // Движение вниз
        $dy = ($this->toY - $this->fromY) / $totalFrames;
        $y = "if(gte(on," . $totalFrames . "), " . $this->toY . ", " . $this->fromY . "+on*" . $dy . ")";
    } else {
        // Движение вверх
        $dy = ($this->fromY - $this->toY) / $totalFrames;
        $y = "if(gte(on," . $totalFrames . "), " . $this->toY . ", " . $this->fromY . "-on*" . $dy . ")";
    }


    if ($this->fromW > $this->toW) {
        $cropW = $this->fromW;
        $cropH = $this->fromH;
    } else {
        $cropW = $this->toW;
        $cropH = $this->toH;
    }

    // Формируем команду FFmpeg
    $ffmpegCmd = "ffmpeg -y -loop 1 -i {$this->photoPath} -vf";
    $ffmpegCmd .= " \"";
    
    $ffmpegCmd .= "scale=" . $scaleW . ":" . $scaleH . ", ";
    $ffmpegCmd .= "pad=" . $padW  . ":" . $padH . ", ";
    //$ffmpegCmd .= "crop=" . $cropW . ":" . $cropH . ", ";
    $ffmpegCmd .= "zoompan=";
    $ffmpegCmd .= "z='" . $z. "'";
    $ffmpegCmd .= ":x='" . $x . "'";
    $ffmpegCmd .= ":y='" . $y . "'";
    $ffmpegCmd .= ":d=" . $totalFrames;
    $ffmpegCmd .= ":s=" . $this->videoWidth . "x" . $this->videoHeight . "";
    $ffmpegCmd .= "\" ";
    $ffmpegCmd .= "-t " . $this->time . " -r " . $this->fps . " -vcodec prores_ks -profile:v 3 -pix_fmt yuv422p10le " . $this->outputVideoPath;

    return $ffmpegCmd;
}




    /**
     * Create Pan and Zoom video via ffmpeg 
     */
    public function createVideo()
    {
 
        $command = $this->generateFfmpegCommand();

        print $command . PHP_EOL;

        exec($command);
    }
}
