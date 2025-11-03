<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use Exception;
use Illuminate\Console\Command;

final class RegenerateProductMediaConversions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:regenerate-product-conversions
                            {--force : Force regeneration even if conversions exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate media conversions (thumbnails) for all products';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting product media conversion regeneration...');

        $products = Product::has('media')->get();

        if ($products->isEmpty()) {
            $this->warn('No products with media found.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %s products with media.', $products->count()));

        $progressBar = $this->output->createProgressBar($products->count());
        $progressBar->start();

        $regeneratedCount = 0;
        $errorCount = 0;

        foreach ($products as $product) {
            try {
                $media = $product->getMedia('images');

                foreach ($media as $mediaItem) {
                    // Check if thumb conversion already exists
                    $hasThumb = $mediaItem->hasGeneratedConversion('thumb');

                    if (! $hasThumb || $this->option('force')) {
                        $mediaItem->refresh();
                        ++$regeneratedCount;
                    }
                }
            } catch (Exception $e) {
                ++$errorCount;
                $this->newLine();
                $this->error(
                    sprintf('Error processing product %s: %s', $product->id, $e->getMessage()),
                );
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('✓ Regeneration complete!');
        $this->info('  - Products processed: '.$products->count());
        $this->info('  - Media conversions regenerated: '.$regeneratedCount);

        if ($errorCount > 0) {
            $this->warn('  - Errors encountered: '.$errorCount);
        }

        return self::SUCCESS;
    }
}
