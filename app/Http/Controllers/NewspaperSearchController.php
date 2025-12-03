<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenAIService;
use App\Models\Newspaper;
use Exception;

class NewspaperSearchController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function index()
    {
        return view('newspaper-search.index');
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        
        if (empty($query)) {
            return response()->json(['error' => 'Query is required'], 400);
        }

        try {
            // Extract dates from the user's query using OpenAI
            $extractedDates = $this->openAIService->extractDateFromQuery($query);
            
            if (empty($extractedDates)) {
                return response()->json([
                    'message' => 'No specific date found in your query. Please specify a date (e.g., "April 7th" or "7 April").',
                    'newspapers' => []
                ]);
            }

            $allNewspapers = collect();

            // Search for newspapers matching the extracted dates
            foreach ($extractedDates as $dateInfo) {
                $day = $dateInfo['day'] ?? null;
                $month = $dateInfo['month'] ?? null;
                $year = $dateInfo['year'] ?? null;

                if ($day && $month) {
                    $newspapers = Newspaper::byDate($day, $month, $year)->get();
                    $allNewspapers = $allNewspapers->merge($newspapers);
                }
            }

            // Remove duplicates and sort by date
            $uniqueNewspapers = $allNewspapers->unique('id')->sortByDesc('published_date');

            return response()->json([
                'message' => "Found {$uniqueNewspapers->count()} newspaper(s) for your search.",
                'extracted_dates' => $extractedDates,
                'newspapers' => $uniqueNewspapers->map(function ($newspaper) {
                    return [
                        'id' => $newspaper->id,
                        'published_date' => $newspaper->published_date->format('d M Y'),
                        'file_name' => $newspaper->file_name,
                        'public_url' => $newspaper->public_url,
                        'extracted_content' => $newspaper->extracted_content ? substr($newspaper->extracted_content, 0, 200) . '...' : null
                    ];
                })->values()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function upload(Request $request)
    {
        $request->validate([
            'newspaper_image' => 'required|image|max:10240',
            'published_date' => 'required|date'
        ]);

        try {
            $file = $request->file('newspaper_image');
            $publishedDate = $request->input('published_date');
            
            // Generate filename based on date
            $filename = 'sinar-harian-' . date('Y-m-d', strtotime($publishedDate)) . '-' . time() . '.' . $file->getClientOriginalExtension();
            
            // Store in public folder for easy access
            $publicPath = public_path('newspapers/sinar-harian');
            $file->move($publicPath, $filename);
            
            $fullPath = $publicPath . '/' . $filename;
            
            // Extract text using OpenAI Vision API
            $extractedContent = $this->openAIService->extractTextFromImage($fullPath);
            
            // Create embeddings for search
            $embeddings = null;
            if ($extractedContent) {
                $embeddings = $this->openAIService->createEmbeddings($extractedContent);
            }
            
            // Save to database
            $newspaper = Newspaper::create([
                'published_date' => $publishedDate,
                'file_path' => $fullPath,
                'file_name' => $filename,
                'extracted_content' => $extractedContent,
                'content_embeddings' => $embeddings,
                'file_size' => filesize($fullPath),
                'mime_type' => $file->getClientMimeType()
            ]);

            return response()->json([
                'message' => 'Newspaper uploaded and processed successfully!',
                'newspaper' => [
                    'id' => $newspaper->id,
                    'published_date' => $newspaper->published_date->format('d M Y'),
                    'file_name' => $newspaper->file_name,
                    'public_url' => $newspaper->public_url,
                    'content_preview' => $extractedContent ? substr($extractedContent, 0, 200) . '...' : null
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
