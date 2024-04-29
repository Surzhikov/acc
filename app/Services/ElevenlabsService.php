<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;

class ElevenlabsService
{

	private string $apiKey;
    private string $voiceId;
    private string $filePath;
    private float $tempo = 1;
    private float $finalSilence = 0;

    private float $stability = 1;
    private float $similarityBoost = 0.85;
    private float $style = 0.40;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->apiKey = env('ELEVENLABS_API_KEY');
    }

    /**
     * Set VoiceId 
     */
    public function setVoiceId(string $voiceId)
    {
        $this->voiceId = $voiceId;
    }

    /**
     * Final file path 
     */
    public function setFilePath(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Tempo 
     */
    public function setTempo(float $tempo)
    {
        $this->tempo = $tempo;
    }

    /**
     * Final silence 
     */
    public function setFinalSilence(float $finalSilence)
    {
        $this->finalSilence = $finalSilence;
    }


    /**
     * Set stability
     */
    public function setStability(float $stability)
    {
        $this->stability = $stability;
    }


    /**
     * Set similarityBoost
     */
    public function setSimilarityBoost(float $similarityBoost)
    {
        $this->similarityBoost = $similarityBoost;
    }


    /**
     * Set style
     */
    public function setStyle(float $style)
    {
        $this->style = $style;
    }



    /**
     * Send requset to API
     */
    public function request(string $text): string
    {
        $headers = [
            'Content-Type' => 'application/json',
            'accept' => 'audio/mpeg',
            'xi-api-key' => $this->apiKey,
        ];

        $params = [
            'optimize_streaming_latency' => 2,
            'output_format' => 'mp3_44100_128'
        ];

        $json = [
            "text" => $text,
            "model_id" => 'eleven_multilingual_v2',
            "voice_settings" => [
                "stability" => $this->stability,
                "similarity_boost" => $this->similarityBoost,
                "style" => $this->style,
                "use_speaker_boost" => true
            ]
        ];

        $endpoint = "https://api.elevenlabs.io/v1/text-to-speech/" . $this->voiceId;
        $response = Http::withHeaders($headers)
            ->withUrlParameters($params)
            ->timeout(600)
            ->post($endpoint, $json);


        if ($response->successful()) {

            file_put_contents($this->filePath, $response->body());

            if ($this->tempo != 1) {
                $this->changeTempo();
            }

            if ($this->finalSilence != 0) {
                $this->addFinalSilence();
            }


            return true;
        }

        throw new \Exception('Error request: ' . $response->body());
    }



    /**
     * Make audio faster 
     */
    private function changeTempo()
    {
        $filePath = $this->filePath;
        $tempFilePath = $this->filePath . '.tmp.mp3';

        $ffmpegCmd = 'ffmpeg -y -loglevel quiet -i ' . $filePath . ' -filter:a "atempo=' . $this->tempo . '" -vn ' . $tempFilePath;
        print $ffmpegCmd . PHP_EOL;
        shell_exec($ffmpegCmd);

        unlink($filePath);
        rename($tempFilePath, $filePath);

        return true;
    }


    /**
     * Add final silence
     */
    private function addFinalSilence()
    {
        $filePath = $this->filePath;
        $tempFilePath = $this->filePath . '.tmp.mp3';

        $ffmpegCmd = 'ffmpeg -y -loglevel quiet -i ' . $filePath . ' -af "apad=pad_dur=' . $this->finalSilence . '" -c:v copy ' . $tempFilePath;
        print $ffmpegCmd . PHP_EOL;
        shell_exec($ffmpegCmd);

        unlink($filePath);
        rename($tempFilePath, $filePath);

        return true;
    }



    /**
     * Get price in tokens 
     */
    public function getTotalTokens()
    {
        return $this->totalTokens;
    }

    /**
     * Get price in $ 
     */
    public function getTotalCosts()
    {
        return ($this->inputTokens * $this->inputPrice) + ($this->outputPrice * $this->outputTokens);
    }

}
	
