<?php

namespace App\Jobs\AiTalks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\AiTalk;
use App\Services\GptService;
use App\Services\DalleService;
use App\Services\AiTalksImageService;
use App\Services\ElevenlabsService;
use App\Services\PrintingTextVideoService;
use App\Services\FfmpegService;

class CreateVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private float $microtime = 0;
    private AiTalk $aiTalk;

    /**
     * Create a new job instance.
     */
    public function __construct(AiTalk $aiTalk)
    {
        $this->aiTalk = $aiTalk;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->microtime = microtime(true);

        print PHP_EOL;
        print '-----------------------------------------------' . PHP_EOL;
        print '[' . date('Y-m-d H:i:s') . '] ' . 'Creating video for AiTalk «' . $this->aiTalk->name . '»' . PHP_EOL;
        

        $tempDir = storage_path('app/' . $this->aiTalk->id);
        if (is_dir($tempDir) == false) {
            mkdir($tempDir);
            print '✓ TempDir created' . PHP_EOL;
        }

        
        if ($this->aiTalk->final_video == null) {


            if ($this->aiTalk->cover_video == null) {
                
                if ($this->aiTalk->cover_art == null) {
                    print $this->timestamp() . PHP_EOL;
                    print '→ Creating cover Art (Dall-e)' . PHP_EOL;
                    print '- Propmpt:' . PHP_EOL;
                    $prompt = $this->aiTalk->name . ' (научно-популярное, упрощенный стиль основной фоновый цвет #18212D)' . PHP_EOL;
                    //$prompt.= 'Тема: ' . $this->aiTalk->question_text;
                    print $prompt . PHP_EOL;
                    $coverArtPath = storage_path('app/' . $this->aiTalk->id . '/cover_art.png');

                    print '- Requesting Dall・e...' . PHP_EOL;
                    $dalleService = new DalleService;
                    $dalleService->request($prompt, $coverArtPath);
                    print '- Cover art drawn and stored ' . $coverArtPath . PHP_EOL;

                    $this->aiTalk->cover_art = $coverArtPath;
                    $this->aiTalk->cover_art_costs+= $dalleService->getTotalCosts();
                    $this->aiTalk->save();

                    print '- Costs: ' . $this->aiTalk->cover_art_costs . PHP_EOL;
                    print '✓ Saved!' . PHP_EOL;

                } else {
                    print '✓ Cover art already created' . PHP_EOL;
                }



                if ($this->aiTalk->cover_image == null) {
                    print $this->timestamp() . PHP_EOL;
                    print '→ Creating cover images (Imagick)' . PHP_EOL;
                    $coverImagePath = storage_path('app/' . $this->aiTalk->id . '/cover_image.png');
                    $aiTalksImageService = new AiTalksImageService;
                    $aiTalksImageService->drawCover($this->aiTalk->cover_art, $coverImagePath);
                    $this->aiTalk->cover_image = $coverImagePath;
                    $this->aiTalk->save();
                    print '- Cover image generated and stored ' . $coverImagePath . PHP_EOL;
                    print '✓ Saved!' . PHP_EOL;
                } else {
                    print '✓ Cover image already created' . PHP_EOL;
                }



                if ($this->aiTalk->cover_audio == null) {
                    print $this->timestamp() . PHP_EOL;
                    print '→ Creating cover audio (ElevenLabs)' . PHP_EOL;

                    $coverAudioPath = storage_path('app/' . $this->aiTalk->id . '/cover_audio.mp3');
                    $elevenlabsService = new ElevenlabsService;
                    $elevenlabsService->setFilePath($coverAudioPath);
                    $elevenlabsService->setVoiceId('ErXwobaYiN019PkySvjV'); // Antoni
                    $elevenlabsService->setTempo(1);
                    $elevenlabsService->setFinalSilence(1);
                    $elevenlabsService->request($this->aiTalk->name . '.');

                    $this->aiTalk->cover_audio = $coverAudioPath;
                    $this->aiTalk->save();
                    print '- Costs: ' . $this->aiTalk->cover_audio_costs . PHP_EOL;
                    print '✓ Saved!' . PHP_EOL;

                } else {
                    print '✓ Cover audio already created' . PHP_EOL;
                }



                if ($this->aiTalk->cover_image != null && $this->aiTalk->cover_audio != null) {
                    print $this->timestamp() . PHP_EOL;
                    print '→ Creating cover video (ffmpeg)' . PHP_EOL;

                    $getID3 = new \getID3;
                    $file = $getID3->analyze($this->aiTalk->cover_audio);
                    $duration = $file['playtime_seconds'];
                    print 'cover_audio duration = ' . $duration . PHP_EOL;

                    $coverVideoPath = storage_path('app/' . $this->aiTalk->id . '/cover_video.mp4');

                    $videoGenerator = new PrintingTextVideoService();
                    $videoGenerator->setWidth(1080);
                    $videoGenerator->setHeight(1920);
                    $videoGenerator->setBackgroundImage($this->aiTalk->cover_image);
                    $videoGenerator->setTempDir($tempDir . '/cover_video/');
                    $videoGenerator->setAnimationTime($duration-1);
                    $videoGenerator->setFramerate(25);
                    $videoGenerator->setAudio($this->aiTalk->cover_audio);
                    $videoGenerator->setTextColor('#ffffff');
                    $videoGenerator->setTextBoxX(540);
                    $videoGenerator->setTextBoxY(1245);
                    $videoGenerator->setTextBoxW(920);
                    $videoGenerator->setTextBoxH(400);
                    $videoGenerator->setTextFontSize(50);
                    $videoGenerator->setTextAlign('center');
                    $videoGenerator->setTextFontFile(resource_path('fonts/OpenSans-Bold.ttf'));
                    $videoGenerator->setText($this->aiTalk->name);
                    $videoGenerator->createVideo($coverVideoPath);

                    $this->aiTalk->cover_video = $coverVideoPath;
                    $this->aiTalk->save();
                    print 'Done: ' . $coverVideoPath . PHP_EOL;
                    print '✓ Saved!' . PHP_EOL;

                } else {
                    print '× Cant create cover video! Cover Image or Cover Audio is not created!' . PHP_EOL;
                }

            }








            if ($this->aiTalk->question_video == null) {

                if ($this->aiTalk->question_audio == null && $this->aiTalk->question_text != null) {
                    print $this->timestamp() . PHP_EOL;
                    print '→ Creating question audio (ElevenLabs)' . PHP_EOL;


                    $voicesIds = [
                        //'21m00Tcm4TlvDq8ikWAM', // Rachel
                        '2EiwWnXFnvU5JabPnv8n', // Clyde
                        'AZnzlk1XvdvUeBnXmlld', // Domi
                        'CYw3kZ02Hs0563khs1Fj', // Dave
                        'D38z5RcWu1voky8WS1ja', // Fin
                        'EXAVITQu4vr4xnSDxMaL', // Bella
                        'ErXwobaYiN019PkySvjV', // Antoni
                        //'GBv7mTt0atIp3Br8iCZE', // Thomas - олежка
                        'IKne3meq5aSn9XLyUdCD', // Charlie
                        'LcfcDJNUP1GQjkzn1xUU', // Emily
                        'MF3mGyEYCl7XYWbV9V6O', // Elli
                        'N2lVS1w4EtoT3dr4eOWO', // Callum
                        'ODq5zmih8GrVes37Dizd', // Patrick
                        'SOYHLrjzK2X1ezoPC6cr', // Harry
                        'TX3LPaxmHKxFdv7VOQHJ', // Liam
                        // 'ThT5KcBeYPX3keUQqHPh', // Dorothy стремный
                        'TxGEqnHWrfWFTfGW9XjX', // Josh
                        //'VR6AewLTigWG4xSOukaG', // Arnold - гпт
                        'XB0fDUnXU5powFXDhCwa', // Charlotte
                        'XrExE9yKIg1WjnnlVkGX', // Matilda
                        'Yko7PKHZNXotIFUBG7I9', // Matthew
                        'ZQe5CZNOzWyzPSCn5a3c', // James
                        'Zlb1dXrM653N07WRdFW3', // Joseph
                        'bVMeCyTHy58xNoL34h3p', // Jeremy
                        'flq6f7yk4E4fJM5XTYuZ', // Michael
                        'g5CIjZEefAph4nQFvHAz', // Ethan
                        'jBpfuIE2acCO8z3wKNLl', // Gigi
                        //'jsCqWAovK2LkecY7zXl4', // Freya - стремный
                        'oWAxZDx7w5VEj9dCyTzz', // Grace
                        'onwK4e9ZLuTAKqWW03F9', // Daniel
                        'pMsXgVXv3BLzUgSXRplE', // Serena
                        'pNInz6obpgDQGcFmaJgB', // Adam
                        'piTKgcLEGmPE4e6mEKli', // Nicole
                        't0jbNlBVZ17f02VDIeMI', // Jessie - классный
                        'wViXBPUzp2ZZixB1xQuM', // Ryan
                    ];


                    $randomVoiceId = $voicesIds[array_rand($voicesIds)];

                    file_put_contents(storage_path('app/' . $this->aiTalk->id . '/voice.txt'), $randomVoiceId);

                    $questionAudioPath = storage_path('app/' . $this->aiTalk->id . '/question_audio.mp3');
                    $elevenlabsService = new ElevenlabsService;
                    $elevenlabsService->setFilePath($questionAudioPath);
                    $elevenlabsService->setVoiceId($randomVoiceId);
                    $elevenlabsService->setTempo(1.25);
                    $elevenlabsService->setFinalSilence(2);
                    $elevenlabsService->request($this->aiTalk->question_text . '.');

                    $this->aiTalk->question_audio = $questionAudioPath;
                    $this->aiTalk->save();
                    print '- Costs: ' . $this->aiTalk->question_audio_costs . PHP_EOL;
                    print '✓ Saved!' . PHP_EOL;

                }

                if ($this->aiTalk->question_audio != null && $this->aiTalk->question_text != null) {

                    print $this->timestamp() . PHP_EOL;
                    print '→ Creating question video (ffmpeg)' . PHP_EOL;


                    $getID3 = new \getID3;
                    $file = $getID3->analyze($this->aiTalk->question_audio);
                    $duration = $file['playtime_seconds'];
                    print 'question_audio duration = ' . $duration . PHP_EOL;

                    $questionVideoPath = storage_path('app/' . $this->aiTalk->id . '/question_video.mp4');

                    $videoGenerator = new PrintingTextVideoService();
                    $videoGenerator->setWidth(1080);
                    $videoGenerator->setHeight(1920);
                    $videoGenerator->setBackgroundImage(resource_path('aitalks/userbg.png'));
                    $videoGenerator->setTempDir($tempDir . '/question_video/');
                    $videoGenerator->setAnimationTime($duration-2);
                    $videoGenerator->setFramerate(25);
                    $videoGenerator->setAudio($this->aiTalk->question_audio);
                    $videoGenerator->setTextColor('#ffffff');
                    $videoGenerator->setTextBoxX(151);
                    $videoGenerator->setTextBoxY(390);
                    $videoGenerator->setTextBoxW(777);
                    $videoGenerator->setTextBoxH(1226);
                    $videoGenerator->setTextFontSize(46);
                    $videoGenerator->setTextAlign('left');
                    $videoGenerator->setTextFontFile(resource_path('fonts/OpenSans-Regular.ttf'));
                    $videoGenerator->setText($this->aiTalk->question_text);
                    $videoGenerator->createVideo($questionVideoPath);

                    $this->aiTalk->question_video = $questionVideoPath;
                    $this->aiTalk->save();
                    print 'Done: ' . $questionVideoPath . PHP_EOL;
                    print '✓ Saved!' . PHP_EOL;


                }
            }


            if ($this->aiTalk->answer_video == null) {

                if ($this->aiTalk->answer_text == null) {
                    
                    print $this->timestamp() . PHP_EOL;
                    print '→ Creating answer text (GPT-4)' . PHP_EOL;

                    print '- Propmpt:' . PHP_EOL;
                    $prompt = $this->aiTalk->question_text . PHP_EOL . PHP_EOL;
                    $prompt.= 'Ответ до 650 символов, без списков, качественная орфография и стилистика текста, детали и подробности в ответе' . PHP_EOL;
                    print $prompt . PHP_EOL;

                    print '- Requesting GPT-4...' . PHP_EOL;
                    $gptService = new GptService;
                    $this->aiTalk->answer_text = $gptService->request($prompt);

                    print '- Answer: ' . PHP_EOL;
                    print $this->aiTalk->answer_text;
                    $this->aiTalk->answer_text_costs = $gptService->getTotalCosts();
                    print '- Costs: ' . $this->aiTalk->answer_text_costs;
                    $this->aiTalk->save();
                    print '- Saved!' . PHP_EOL;
                }

                
                if ($this->aiTalk->answer_audio == null && $this->aiTalk->answer_text != null) {
                    print $this->timestamp() . PHP_EOL;
                    print '→ Creating answer audio (ElevenLabs)' . PHP_EOL;
                    $answerAudioPath = storage_path('app/' . $this->aiTalk->id . '/answer_audio.mp3');
                    $elevenlabsService = new ElevenlabsService;
                    $elevenlabsService->setFilePath($answerAudioPath);
                    $elevenlabsService->setVoiceId('VR6AewLTigWG4xSOukaG'); // Arnold
                    $elevenlabsService->setTempo(1.2);
                    $elevenlabsService->setFinalSilence(2);
                    $elevenlabsService->request($this->aiTalk->answer_text . '.');

                    $this->aiTalk->answer_audio = $answerAudioPath;
                    $this->aiTalk->save();
                    print '- Costs: ' . $this->aiTalk->answer_audio_costs . PHP_EOL;
                    print '✓ Saved!' . PHP_EOL;
                }


                if ($this->aiTalk->answer_audio != null && $this->aiTalk->answer_text != null) {
                    print $this->timestamp() . PHP_EOL;
                    print '→ Creating answer video (ffmpeg)' . PHP_EOL;


                    $getID3 = new \getID3;
                    $file = $getID3->analyze($this->aiTalk->answer_audio);
                    $duration = $file['playtime_seconds'];
                    print 'answer_audio duration = ' . $duration . PHP_EOL;

                    $answerVideoPath = storage_path('app/' . $this->aiTalk->id . '/answer_video.mp4');
                    $videoGenerator = new PrintingTextVideoService();
                    $videoGenerator->setWidth(1080);
                    $videoGenerator->setHeight(1920);
                    $videoGenerator->setBackgroundImage(resource_path('aitalks/aibg.png'));
                    $videoGenerator->setTempDir($tempDir . '/answer_video/');
                    $videoGenerator->setAnimationTime($duration-2);
                    $videoGenerator->setFramerate(25);
                    $videoGenerator->setAudio($this->aiTalk->answer_audio);
                    $videoGenerator->setTextColor('#ffffff');
                    $videoGenerator->setTextBoxX(151);
                    $videoGenerator->setTextBoxY(390);
                    $videoGenerator->setTextBoxW(777);
                    $videoGenerator->setTextBoxH(1226);
                    $videoGenerator->setTextFontSize(35);
                    $videoGenerator->setTextAlign('left');
                    $videoGenerator->setTextFontFile(resource_path('fonts/OpenSans-Regular.ttf'));
                    $videoGenerator->setText($this->aiTalk->answer_text);
                    $videoGenerator->createVideo($answerVideoPath);

                    $this->aiTalk->answer_video = $answerVideoPath;
                    $this->aiTalk->save();
                    print 'Done: ' . $answerVideoPath . PHP_EOL;
                    print '✓ Saved!' . PHP_EOL;
                }

            }

            if ($this->aiTalk->cover_video != null && $this->aiTalk->question_video != null && $this->aiTalk->answer_video) {
                print $this->timestamp() . PHP_EOL;
                print '→ Creating Final video (ffmpeg)' . PHP_EOL;

                $videos = [
                    $this->aiTalk->cover_video,
                    $this->aiTalk->question_video,
                    $this->aiTalk->answer_video,
                    resource_path('aitalks/outro.mp4'),
                ];

                $finalVideo = storage_path('app/' . $this->aiTalk->id . '/000' . '.mp4');

                $ffmpegService = new FfmpegService;
                $ffmpegService->combine($videos, $finalVideo) ;

                $this->aiTalk->final_video = $finalVideo;
                $this->aiTalk->save();
                print 'Done: ' . $finalVideo . PHP_EOL;
                print '✓ Saved!' . PHP_EOL;
            }


            print $this->timestamp() . PHP_EOL;
        }

        $this->aiTalk->status = 'done';
        $this->aiTalk->save();
        print "Done!" . PHP_EOL;


    }


    private function timestamp()
    {
        return '[' . date('Y-m-d H:i:s') . '], ' . round(microtime(true) - $this->microtime, 4) . ' sec from strat';
    }

}
    