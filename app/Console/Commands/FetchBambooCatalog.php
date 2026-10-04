<?php

namespace App\Console\Commands;

use App\Models\BambooBrand;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use vahidkaargar\BambooCardPortal\Exceptions\ConfigurationException;

class FetchBambooCatalog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bamboo:fetch-catalog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch bamboo catalog version one';

    /**
     * Bamboo products version two
     */
    private array $products = [];

    private array $stats = [
        'brands' => 0,
        'products' => 0,
        'requested_version_two' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Fetch catalog from bamboo
        $catalog = $this->fetchCatalog();

        if ($catalog['success']) {

            // Create progress bar
            $bar = $this->output->createProgressBar(count($catalog['body']['brands']));
            $bar->start();

            // Handle brands
            foreach ($catalog['body']['brands'] as $brand) {
                $bar->advance();

                $brandId = intval($brand['internalId']);
                $brandCurrency = trim($brand['currencyCode']);
                $brandCountry = trim($brand['countryCode']);

                // Skip brand if it doesn't have any product
                if (count($brand['products']) === 0) {
                    Log::driver('bamboo')->error("Brand doesnt have any product: $brandId");

                    continue;
                }

                // Empty to avoid remember data
                $create = [];
                $create['brand_id'] = $brandId;
                $create['name'] = trim($brand['name']);
                $create['currency'] = $brandCurrency;
                $create['country'] = $brandCountry;
                $create['image'] = $brand['logoUrl'];

                foreach ($brand['products'] as $product) {
                    $create['products'][] = $this->normalizeProduct($product, $brandId, $brandCountry, $brandCurrency);
                }

                $bambooBrand = BambooBrand::query()->firstOrCreate(['brand_id' => $brandId]);
                $bambooBrand->fill($create);
                $bambooBrand->save();

                $this->stats['brands']++;
            }

            $bar->finish();
            $this->newLine(2);

            $this->info('Brands: '.$this->stats['brands']);
            $this->info('Products: '.$this->stats['products']);
            $this->info('Version two requests: '.$this->stats['requested_version_two']);
        } else {
            $this->error($catalog['message']);
        }
    }

    /**
     * @throws ConfigurationException
     * @throws ConnectionException
     */
    private function normalizeProduct(array $product, int $brandId, string $brandCountry, string $brandCurrency): array
    {
        $this->stats['products']++;

        $fixed = $product['minFaceValue'] === $product['maxFaceValue'];

        $product['country'] = $brandCountry;
        $product['inventory'] = $product['count'];

        $product['purchase']['fixed'] = $fixed;
        $product['purchase']['min'] = $product['minFaceValue'];
        $product['purchase']['max'] = $product['maxFaceValue'];
        $product['purchase']['currency'] = $brandCurrency;

        $product['sale']['fixed'] = $fixed;
        $product['sale']['min'] = $product['price']['min'];
        $product['sale']['max'] = $product['price']['max'];
        $product['sale']['unit'] = $fixed ? $product['price']['min'] : $product['price']['min'] / $product['minFaceValue'];
        $product['sale']['currency'] = $product['price']['currencyCode'];

        // Calculate discount percentage
        if ($product['purchase']['currency'] === $product['sale']['currency']) {
            $discount = ($product['sale']['min'] / $product['purchase']['min']) * 100;
        } else {
            if (empty($this->products[$product['id']])) {
                sleep(5);
                $this->extractProductVersionTwo($brandCurrency, $brandId);
            }
            if (isset($this->products[$product['id']])) {
                $discount = ($this->products[$product['id']]['price']['min'] / $this->products[$product['id']]['minFaceValue']) * 100;
            } else {
                $discount = 0;
                Log::driver('bamboo')->error("Product not found: {$product['id']} - Brand ID: $brandId");
            }
        }
        $product['discount'] = round(100 - $discount, 2);

        unset($product['minFaceValue'], $product['maxFaceValue'], $product['price'], $product['count'], $product['modifiedDate']);

        return $product;
    }

    /**
     * @throws ConfigurationException
     * @throws ConnectionException
     */
    private function extractProductVersionTwo(string $brandCurrency, int $brandId): void
    {
        Log::driver('bamboo')->info("Version two Brand ID: $brandId");

        $this->stats['requested_version_two']++;

        $brandVersionTwo = bamboo()
            ->catalogs()
            ->setVersion(2)
            ->setTargetCurrency($brandCurrency)
            ->setBrandId($brandId)
            ->get();

        $products = [];
        if ($brandVersionTwo['success']) {
            $products = $brandVersionTwo['body']['items'][0]['products'] ?? [];
        } else {
            Log::driver('bamboo')->error("Version two request error: {$brandVersionTwo['message']} - Brand ID: $brandId");
        }

        foreach ($products as $product) {
            $this->products[$product['id']] = $product;
        }
    }

    /**
     * @throws ConfigurationException
     * @throws ConnectionException
     */
    private function fetchCatalog(): array
    {
        $catalog = null;

        $catalogFileName = 'catalog.json';
        if (Storage::exists($catalogFileName)) {
            $catalogLastModified = Storage::lastModified($catalogFileName);
            $catalogLastModifiedDiffInMinutes = Carbon::parse($catalogLastModified)->diffInMinutes();
            if ($catalogLastModifiedDiffInMinutes < 60) {
                $catalog = Storage::json($catalogFileName);
            } else {
                Storage::delete($catalogFileName);
            }
        }

        if (blank($catalog)) {
            $catalog = bamboo()->catalogs()->get()->toArray();
            Storage::put($catalogFileName, json_encode($catalog, JSON_PRETTY_PRINT));
        }

        return $catalog ?? ['success' => false, 'message' => 'Something went wrong'];
    }
}
