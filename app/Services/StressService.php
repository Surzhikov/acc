<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
class StressService
{


	public static function getStress($text)
	{
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $payload = [
            'text' => $text,
        ];

        $endpoint = env('RUSSTRESS_API_URL');

        $response = Http::withHeaders($headers)->timeout(60*10)->post($endpoint, $payload);

        if ($response->successful() == false) {
        	throw new \Exception("ERROR GETTING STRESS FOR " . $text);
        }

        return $response->json()['stressed_text'];
	}



}