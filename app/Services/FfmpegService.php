<?php

namespace App\Services;

class FfmpegService
{

	public function combine(array $videos, $outputPath)
	{
		$tempFile = '/tmp/' . uniqid() . '.ffmpeg.videos.txt';

		$content = '';
		foreach ($videos as $videoPath) {
			$content.= "file '" . $videoPath . "'" . PHP_EOL;
		}
		$content = trim($content);

		file_put_contents($tempFile, $content);

		$ffmpegCmd = 'ffmpeg -y -f concat -safe 0 -i ' . $tempFile . ' -c copy ' . $outputPath;

        print $ffmpegCmd . PHP_EOL;

        // Выполняем команду
        shell_exec($ffmpegCmd);
        
		return true;
	}



    public static function overlaySubtitles($backgroundVideoPath, $subtitlesVideoPath, $outputVideoPath)
    {
        // Команда для наложения субтитров на видео
        $cmd = 'ffmpeg -y -i ' . escapeshellarg($backgroundVideoPath) . 
               ' -i ' . escapeshellarg($subtitlesVideoPath) .
               ' -filter_complex "overlay=shortest=1" ' . // Наложение субтитров на видео
               ' -c:v libx264 -crf 23 ' . 
               escapeshellarg($outputVideoPath);

    print $cmd . PHP_EOL;

        // Выполнение команды
        exec($cmd, $output, $return_var);

        if ($return_var !== 0) {
            throw new \Exception('Ошибка при обработке видео: ' . implode("\n", $output));
        }

        return true;
    }

	public static function combineVideoWithAudioOLD($videoPath, $audioPath, $outputVideoPath, $volume = 1)
    {
        // Проверяем, есть ли аудио в видеофайле
        $videoAudioCheckCmd = 'ffprobe -i ' . escapeshellarg($videoPath) . ' -show_streams -select_streams a -loglevel error';
        exec($videoAudioCheckCmd, $audioCheckOutput, $audioCheckReturnVar);

        // Формирование команды ffmpeg в зависимости от наличия аудиодорожки в видео
        if ($audioCheckReturnVar === 0 && count($audioCheckOutput) > 0) {
            // Аудиодорожка есть в видео
            $cmd = 'ffmpeg -loglevel quiet -y -i ' . escapeshellarg($videoPath) . 
                   ' -i ' . escapeshellarg($audioPath) .
                   ' -filter_complex "[1:a]volume=' . $volume . '[adjusted]; [0:a][adjusted]amix=inputs=2:duration=longest[a]" ' . // Регулировка громкости и смешивание аудиодорожек
                   ' -map 0:v -map "[a]" ' . // Использование видеопотока и смешанного аудиопотока
                   ' -c:v copy -c:a aac -strict experimental ' . // Копирование видеопотока и кодирование аудио
                   escapeshellarg($outputVideoPath);
        } else {
            // Аудиодорожки нет в видео
            $cmd = 'ffmpeg -loglevel quiet -y -i ' . escapeshellarg($videoPath) . 
                   ' -i ' . escapeshellarg($audioPath) .
                   ' -filter_complex "[1:a]volume=' . $volume . '[a]" ' . // Регулировка громкости аудио
                   ' -map 0:v -map "[a]" ' . // Использование видеопотока и отфильтрованного аудиопотока
                   ' -c:v copy -c:a aac -strict experimental ' . // Копирование видеопотока и кодирование аудио
                   escapeshellarg($outputVideoPath);
        }

        // Выполнение команды
        exec($cmd, $output, $return_var);

        if ($return_var !== 0) {
            throw new \Exception('Ошибка при обработке видео: ' . implode("\n", $output));
        }

        return true;
    }



 public static function combineVideoWithAudio($videoPath, $audioPath, $outputVideoPath, $volume = 1)
    {
        // Проверяем, есть ли аудио в видеофайле
        $videoAudioCheckCmd = 'ffprobe -i ' . escapeshellarg($videoPath) . ' -show_streams -select_streams a -loglevel error';
        exec($videoAudioCheckCmd, $audioCheckOutput, $audioCheckReturnVar);

        // Формирование команды ffmpeg в зависимости от наличия аудиодорожки в видео
        if ($audioCheckReturnVar === 0 && count($audioCheckOutput) > 0) {
            // Аудиодорожка есть в видео
            $cmd = 'ffmpeg -loglevel quiet -y -i ' . escapeshellarg($videoPath) . 
                   ' -i ' . escapeshellarg($audioPath) .
                   ' -filter_complex "[1:a]volume=' . $volume . '[adjusted]; [0:a][adjusted]amix=inputs=2:duration=first[a]" ' . // Регулировка громкости и смешивание аудиодорожек
                   ' -map 0:v -map "[a]" ' . // Использование видеопотока и смешанного аудиопотока
                   ' -c:v copy -c:a aac -strict experimental ' .
                   ' -shortest ' . // Обрезка по самому короткому медиа-файлу
                   escapeshellarg($outputVideoPath);
        } else {
            // Аудиодорожки нет в видео
            $cmd = 'ffmpeg -loglevel quiet -y -i ' . escapeshellarg($videoPath) . 
                   ' -i ' . escapeshellarg($audioPath) .
                   ' -filter_complex "[1:a]volume=' . $volume . '[a]" ' . // Регулировка громкости аудио
                   ' -map 0:v -map "[a]" ' . // Использование видеопотока и отфильтрованного аудиопотока
                   ' -c:v copy -c:a aac -strict experimental ' .
                   escapeshellarg($outputVideoPath);
        }

        // Выполнение команды
        exec($cmd, $output, $return_var);

        if ($return_var !== 0) {
            throw new \Exception('Ошибка при обработке видео: ' . implode("\n", $output));
        }

        return true;
    }



    public static function concatenateVideos(array $videosPaths, $outputVideoPath)
    {

        // Создаем временный файл для списка видео
        $tempFile = tempnam(sys_get_temp_dir(), 'ffmpeg');
        $fileContent = '';
        foreach ($videosPaths as $videoPath) {
            $fileContent .= "file '" . addslashes($videoPath) . "'\n";
        }
        file_put_contents($tempFile, $fileContent);

        // Команда для склейки видео
        $cmd = 'ffmpeg -y -f concat -safe 0 -i ' . escapeshellarg($tempFile) . 
               ' -c copy ' . // Копирование потоков без перекодирования
               escapeshellarg($outputVideoPath);

        print $cmd . PHP_EOL;

        // Выполнение команды
        exec($cmd, $output, $return_var);

        // Удаление временного файла
        unlink($tempFile);

        if ($return_var !== 0) {
            throw new \Exception('Ошибка при склейке видео: ' . implode("\n", $output));
        }

        return true;
    }

}

