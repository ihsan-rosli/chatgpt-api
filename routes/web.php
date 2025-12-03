<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-openai', function () {
    $apiKey = config('services.openai.api_key');
    
    if (!$apiKey) {
        return response()->json(['error' => 'OpenAI API key not configured']);
    }

    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello! This is a test from Laravel. Please respond with just "API connection successful!"']
            ],
            'max_tokens' => 20
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return response()->json([
                'status' => 'success',
                'message' => $data['choices'][0]['message']['content'] ?? 'No response content',
                'usage' => $data['usage'] ?? 'No usage data'
            ]);
        } else {
            return response()->json([
                'error' => 'API request failed',
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        }
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

// Newspaper search routes
Route::get('/newspaper-search', [App\Http\Controllers\NewspaperSearchController::class, 'index'])->name('newspaper.search');
Route::post('/api/newspaper/search', [App\Http\Controllers\NewspaperSearchController::class, 'search'])->name('api.newspaper.search');
Route::post('/api/newspaper/upload', [App\Http\Controllers\NewspaperSearchController::class, 'upload'])->name('api.newspaper.upload');
