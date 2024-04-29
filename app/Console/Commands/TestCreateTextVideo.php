<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\GptService;
use App\Services\DalleService;
use App\Services\AiTalksImageService;
use App\Services\ElevenlabsService;
use App\Services\PrintingTextVideoService;
use App\Services\FfmpegService;


class TestCreateTextVideo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-create-text-video';

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
        $coverAudioPath = storage_path('app/outro_audio.mp3');
        $elevenlabsService = new ElevenlabsService;
        $elevenlabsService->setFilePath($coverAudioPath);
        $elevenlabsService->setVoiceId('VR6AewLTigWG4xSOukaG'); // Arnold
        $elevenlabsService->setTempo(1);
        $elevenlabsService->setFinalSilence(1);
        $elevenlabsService->request('Ваши вопросы к GPT пишите в комментариях. И подписывайтесь на канал, чтобы не пропустить новые видео.');


        $getID3 = new \getID3;
        $file = $getID3->analyze($coverAudioPath);
        $duration = $file['playtime_seconds'];

        $coverVideoPath = resource_path('aitalks/outro.mp4');

        $videoGenerator = new PrintingTextVideoService();
        $videoGenerator->setWidth(1080);
        $videoGenerator->setHeight(1920);
        $videoGenerator->setBackgroundImage(resource_path('aitalks/outro.png'));
        $videoGenerator->setTempDir(storage_path('app/temp'));
        $videoGenerator->setAnimationTime($duration-1);
        $videoGenerator->setFramerate(25);
        $videoGenerator->setAudio($coverAudioPath);
        $videoGenerator->setTextColor('#ffffff');
        $videoGenerator->setTextBoxX(540);
        $videoGenerator->setTextBoxY(1245);
        $videoGenerator->setTextBoxW(920);
        $videoGenerator->setTextBoxH(400);
        $videoGenerator->setTextFontSize(50);
        $videoGenerator->setTextAlign('center');
        $videoGenerator->setTextFontFile(resource_path('fonts/OpenSans-Bold.ttf'));
        $videoGenerator->setText('Ваши вопросы к GPT пишите в комментариях' . "\n" . 'Подписывайтесь на канал, чтобы не пропустить новые видео');
        $videoGenerator->createVideo($coverVideoPath);



    }
}
