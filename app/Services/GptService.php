<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;

class GptService
{
	private string $apiKey;

    //private string $model = 'gpt-4';
	//private float $inputPrice = 0.00003;
	//private float $outputPrice = 0.00006;

    private string $model = 'gpt-4-1106-preview';
    private float $inputPrice = 0.00001;
    private float $outputPrice = 0.00003;

	private int $inputTokens = 0;
	private int $outputTokens = 0;
	private int $totalTokens = 0;
    
    public bool $asJson = false;


    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
    }

    /**
     * Отправить запрос к GPT-4 API.
     */
    public function request(string $text): string
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        print 'GptService will use model: ' . $this->model . PHP_EOL;

        $payload = [
            'model' => $this->model,
            'messages' => [
                ["role" => "user", "content" => $text]
            ]
        ];

       if ($this->model == 'gpt-4-1106-preview' && $this->asJson == true) {
           $payload['response_format'] = [
              'type' => 'json_object'
           ];
       }

        $endpoint = "https://api.openai.com/v1/chat/completions";
        $response = Http::withHeaders($headers)->timeout(60*10)->post($endpoint, $payload);

        if ($response->successful()) {
            $this->totalTokens = $response->json()['usage']['total_tokens'];
            $this->inputTokens = $response->json()['usage']['prompt_tokens'];
            $this->outputTokens = $response->json()['usage']['completion_tokens'];
            return $response->json()['choices'][0]['message']['content'] ?? 'Error request';
        }

        throw new \Exception('Error request: ' . $response->body());
    }


    public function setModel($model)
    {
        $availableModels = [
            'gpt-4' => [
                'inputPrice' => 0.00003,
                'outputPrice' => 0.00006,
            ],
            'gpt-4-1106-preview' => [
                'inputPrice' => 0.00001,
                'outputPrice' => 0.00003,
            ]
        ];

        if (array_key_exists($model, $availableModels) == false) {
            throw new \Exception('Bad Model name: ' . $model);
        }

        $this->model = $model;
        $this->inputPrice = $availableModels[$model]['inputPrice'];
        $this->outputPrice = $availableModels[$model]['outputPrice'];
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
	
