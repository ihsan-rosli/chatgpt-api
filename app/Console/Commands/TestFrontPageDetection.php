<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenAIService;

class TestFrontPageDetection extends Command
{
    protected $signature = 'test:front-page {file}';
    protected $description = 'Test front page detection on a specific PDF file';

    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        parent::__construct();
        $this->openAIService = $openAIService;
    }

    public function handle()
    {
        $fileName = $this->argument('file');
        $filePath = storage_path('app/newspapers/sinar-harian/pdf/20100531/' . $fileName);
        
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("Testing front page detection on: {$fileName}");
        $this->info("File path: {$filePath}");
        
        try {
            $isFrontPage = $this->openAIService->isFrontPage($filePath);
            
            if ($isFrontPage) {
                $this->info("✅ Result: YES - This is a front page!");
            } else {
                $this->warn("❌ Result: NO - This is not a front page");
            }
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
