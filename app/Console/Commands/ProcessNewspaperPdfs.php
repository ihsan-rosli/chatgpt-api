<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenAIService;
use App\Models\Newspaper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Exception;

class ProcessNewspaperPdfs extends Command
{
    protected $signature = 'newspaper:process-pdfs {--force : Force reprocessing of existing newspapers}';
    protected $description = 'Process uploaded PDF newspapers using OpenAI and save to database';

    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        parent::__construct();
        $this->openAIService = $openAIService;
    }

    public function handle()
    {
        $this->info('🚀 Starting newspaper PDF processing...');

        $basePath = storage_path('app/newspapers/sinar-harian/pdf');
        
        if (!File::exists($basePath)) {
            $this->error('PDF directory does not exist: ' . $basePath);
            return 1;
        }

        $dateFolders = File::directories($basePath);
        
        if (empty($dateFolders)) {
            $this->error('No date folders found in: ' . $basePath);
            return 1;
        }

        $this->info('Found ' . count($dateFolders) . ' date folders to process');

        $totalProcessed = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($dateFolders as $dateFolder) {
            $folderName = basename($dateFolder);
            
            // Parse date from folder name (YYYYMMDD format)
            try {
                $publishedDate = Carbon::createFromFormat('Ymd', $folderName);
            } catch (Exception $e) {
                $this->warn("⚠️  Skipping folder '{$folderName}' - invalid date format");
                continue;
            }

            $this->info("\n📅 Processing folder: {$folderName} ({$publishedDate->format('d M Y')})");

            $pdfFiles = File::glob($dateFolder . '/*.pdf');
            
            if (empty($pdfFiles)) {
                $this->warn("   No PDF files found in {$folderName}");
                continue;
            }

            $this->info("   Found " . count($pdfFiles) . " PDF files");

            // Check if we already have a front page for this date
            if (!$this->option('force')) {
                $existingFrontPage = Newspaper::where('published_date', $publishedDate->format('Y-m-d'))->first();
                if ($existingFrontPage) {
                    $this->line("   ⏭️  Already have front page for {$folderName}");
                    $totalSkipped++;
                    continue;
                }
            }

            $frontPageFound = false;

            foreach ($pdfFiles as $pdfPath) {
                $fileName = basename($pdfPath);
                
                $this->line("   🔍 Checking {$fileName}...");
                
                try {
                    // Check if this is a front page using AI first, then fallback to filename pattern
                    $isFrontPage = $this->openAIService->isFrontPage($pdfPath);
                    
                    // If AI doesn't detect it, check filename patterns for front page indicators
                    if (!$isFrontPage) {
                        // Look for patterns like "_001_" or "Harian_YYYYMMDD_001" 
                        $isFrontPage = (
                            strpos($fileName, '_001_') !== false || 
                            strpos($fileName, 'Harian_' . $folderName . '_001') !== false ||
                            (strpos($fileName, 'Sinar Harian_') !== false && strpos($fileName, '_001_') !== false)
                        );
                        
                        if ($isFrontPage) {
                            $this->line("   📋 Detected as front page by filename pattern");
                        }
                    }
                    
                    if ($isFrontPage) {
                        $this->line("   🎯 Found front page: {$fileName}");
                        
                        // Extract text content from front page
                        $extractedContent = $this->openAIService->extractTextFromImage($pdfPath);
                        
                        // Create embeddings for search
                        $embeddings = null;
                        if ($extractedContent) {
                            $embeddings = $this->openAIService->createEmbeddings($extractedContent);
                        }

                        // Copy front page to public directory with date-based naming
                        $publicFileName = "sinar-harian-{$folderName}-front.pdf";
                        $publicPath = public_path('newspapers/sinar-harian/' . $publicFileName);
                        File::copy($pdfPath, $publicPath);

                        // Save to database
                        Newspaper::updateOrCreate(
                            [
                                'published_date' => $publishedDate->format('Y-m-d')
                            ],
                            [
                                'file_name' => $publicFileName,
                                'file_path' => $pdfPath,
                                'extracted_content' => $extractedContent,
                                'content_embeddings' => $embeddings,
                                'file_size' => File::size($pdfPath),
                                'mime_type' => 'application/pdf'
                            ]
                        );

                        $this->line("   ✅ Successfully processed front page for {$folderName}");
                        $totalProcessed++;
                        $frontPageFound = true;
                        
                        // Break to next folder once front page is found
                        break;
                        
                    } else {
                        $this->line("   ⏭️  Not front page, continuing...");
                    }

                } catch (Exception $e) {
                    $this->error("   ❌ Error checking {$fileName}: " . $e->getMessage());
                    $totalErrors++;
                }

                // Add small delay to respect API rate limits
                usleep(250000); // 0.25 seconds
            }

            if (!$frontPageFound) {
                $this->warn("   ⚠️  No front page found in {$folderName}");
            }
        }

        // Summary
        $this->info("\n🎉 Processing complete!");
        $this->info("✅ Processed: {$totalProcessed}");
        $this->info("⏭️  Skipped: {$totalSkipped}");
        $this->info("❌ Errors: {$totalErrors}");

        return 0;
    }
}
