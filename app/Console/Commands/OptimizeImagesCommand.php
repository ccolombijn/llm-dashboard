<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Image\Image;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class OptimizeImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'image:optimize {path? : The path to optimize (defaults to public/build/assets and runs vite build)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build Vite assets and then optimize images, creating WebP versions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = $this->argument('path') ?? 'public/build/assets';
        
        if ($path === 'public/build/assets') {
            $this->info('Default path detected. Starting Vite build process...');
            $process = new Process(['npm', 'run', 'build']);
            $process->setWorkingDirectory(base_path());
            $process->setTimeout(300);
            $process->run(fn ($type, $buffer) => $this->output->write($buffer));

            if (!$process->isSuccessful()) {
                $this->error('Vite build failed. Aborting optimization.');
                return Command::FAILURE;
            }
            $this->info('Vite build completed successfully.');
            $this->newLine();
        } else {
            $this->info("Custom path '{$path}' detected. Skipping Vite build process.");
        }
        
        $this->info("Optimizing images and creating WebP versions in: {$path}");

        if (!is_dir($path)) {
            $this->error("Directory not found: {$path}");
            return Command::FAILURE;
        }

        $optimizerChain = OptimizerChainFactory::create();
        
        $finder = (new Finder())
            ->files()
            ->in($path)
            ->name(['*.jpg', '*.jpeg', '*.png']);

        if (!$finder->hasResults()) {
            $this->info('No JPG or PNG images found to process.');
            return Command::SUCCESS;
        }

        // --- NEW: Array to hold the results ---
        $createdWebpFiles = [];

        $progressBar = $this->output->createProgressBar($finder->count());
        $progressBar->start();

        foreach ($finder as $file) {
            $originalPath = $file->getRealPath();
            $optimizerChain->optimize($originalPath);
            $webpPath = pathinfo($originalPath, PATHINFO_DIRNAME) . '/' . pathinfo($originalPath, PATHINFO_FILENAME) . '.webp';
            Image::load($originalPath)->save($webpPath);
            
            // --- NEW: Add the file paths to our results array ---
            // We make the paths relative to the project for cleaner output
            $createdWebpFiles[] = [
                'original' => str_replace(base_path() . '/', '', $originalPath),
                'webp' => str_replace(base_path() . '/', '', $webpPath),
            ];

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2); // Add some space after the progress bar

        // --- NEW: Display the results in a table ---
        if (!empty($createdWebpFiles)) {
            // $this->info('The following WebP files were created:');
            // $this->table(
            //     ['Original File', 'WebP Version Created'],
            //     $createdWebpFiles
            // );
        }

        $this->info("Processing complete!");

        return Command::SUCCESS;
    }
}