<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class OpenAIService
{
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->baseUrl = config('services.openai.base_url');
    }

    public function extractDateFromQuery(string $query): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a date extraction expert. Extract dates from user queries and return them in JSON format. If multiple dates are mentioned, return all of them. Format: {"dates": [{"day": 7, "month": 4, "year": null}]}. If no year is specified, set year to null. If no specific date is found, return {"dates": []}'
                    ],
                    [
                        'role' => 'user',
                        'content' => $query
                    ]
                ],
                'max_tokens' => 100,
                'temperature' => 0.1
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                
                // Parse the JSON response
                $dateData = json_decode($content, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($dateData['dates'])) {
                    return $dateData['dates'];
                }
            }
            
            return null;
        } catch (Exception $e) {
            throw new Exception('Failed to extract date from query: ' . $e->getMessage());
        }
    }

    public function extractTextFromImage(string $imagePath): ?string
    {
        try {
            // Convert image to base64
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-4-vision-preview',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Extract all text content from this Sinar Harian newspaper front page. Focus on headlines, articles, and important text. Return the text in a structured format.'
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:{$mimeType};base64,{$imageData}"
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => 1000
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? null;
            }
            
            return null;
        } catch (Exception $e) {
            throw new Exception('Failed to extract text from image: ' . $e->getMessage());
        }
    }

    public function isFrontPage(string $imagePath): bool
    {
        try {
            // Convert image to base64
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-4-vision-preview',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Look at this document page. Is this a Sinar Harian newspaper front page? Look for these specific features: 1) The word "Sinar" in large white letters inside a red banner/header at the top of the page, 2) The word "harian" should appear next to or below "Sinar", 3) It should look like a newspaper front page with headlines and news articles, 4) There should be a date visible. Answer only "YES" if you see these features indicating a front page, or "NO" if this is not a front page.'
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:{$mimeType};base64,{$imageData}"
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => 10,
                'temperature' => 0.1
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                return stripos($content, 'YES') !== false;
            }
            
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function createEmbeddings(string $text): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/embeddings', [
                'model' => 'text-embedding-ada-002',
                'input' => $text
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'][0]['embedding'] ?? null;
            }
            
            return null;
        } catch (Exception $e) {
            throw new Exception('Failed to create embeddings: ' . $e->getMessage());
        }
    }
}