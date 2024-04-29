<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;

class DalleService
{
	private string $size = '1024x1024';
    private string $apiKey;
	private float $costs = 0.04;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
    }

    public function setSize(string $size)
    {
        $this->size = $size;
    }

    /**
     * Отправить запрос к GPT-4 API.
     *
     * @param string $text
     * @return string
     */
    public function request(string $text, $pathToSave = null): string
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $payload = [
            'model' => 'dall-e-3',
            'size' => $this->size,
            'quality' => 'standard',
            //'quality' => 'hd',
            'n' => 1,
            'prompt' => $text,
        ];

        $endpoint = "https://api.openai.com/v1/images/generations";
        $response = Http::withHeaders($headers)->timeout(3600)->post($endpoint, $payload);

        //dump($response->json());

        if ($response->successful()) {
            $url = $response->json()['data'][0]['url'];
            print 'Image generated!' . PHP_EOL;
            if ($pathToSave != null) {
                print 'Downloading...' . PHP_EOL;
                print $url . PHP_EOL;
                $content = Http::timeout(3600)->get($url)->body();
                file_put_contents($pathToSave, $content);
                return $pathToSave;
            }
            return $url;
        }

        throw new \Exception('Error request: ' . $response->body());
    }


    /**
     * Get price in $ 
     */
    public function getTotalCosts()
    {
        return $this->costs;
    }

}
	
