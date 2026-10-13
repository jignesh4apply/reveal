<?php

namespace App\Console\Commands;

use App\Models\NutritionIngredient;
use App\Models\NutritionIngredientBarcode;
use App\Models\NutritionIngredientUnit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use JsonMachine\Items;

class ImportFoodProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:food-products {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import food products from a JSON file into the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('memory_limit', '-1'); // Unlimited memory
        ini_set('max_execution_time', 0); // No time limit
        // DB::table('nutrition_ingredients')
        //     ->whereNotNull('barcode')
        //     ->chunkById(5000, function ($records) {
        //         $barcodes = [];
        
        //         foreach ($records as $record) {
        //             $barcodes[] = [
        //                 'nutrition_ingredient_id' => $record->id,
        //                 'barcode' => $record->barcode,
        //                 'created_at' => now(),
        //                 'updated_at' => now(),
        //             ];
        
        //             if (count($barcodes) >= 500) { // Insert in small batches
        //                 DB::table('nutrition_ingredient_barcodes')->insertOrIgnore($barcodes);
        //                 $barcodes = [];
        //             }
        //         }
        
        //         if (!empty($barcodes)) {
        //             DB::table('nutrition_ingredient_barcodes')->insertOrIgnore($barcodes);
        //         }
        //     });
        
        // dd('Import Completed!');
        $filePath = $this->argument('file');

        if (!File::exists($filePath)) {
            $this->error("The file {$filePath} does not exist.");
            return 1;
        }

        $products = Items::fromFile($filePath, ['pointer' => '/products']);
        $chunkSize = 1000;
        $barcodesData = [];
        $unitOptionsData = [];
        foreach ($products as $product) {
            $product = (array) $product; // Convert object to array
            // 🛠 Process Barcodes
            $barcodes = collect($product['barcodes'] ?? [])
                ->map(fn($b) => preg_replace('/[^0-9]/', '', $b)) // Keep only numbers
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (empty($barcodes)) continue; 

            // Convert nested objects to arrays safely
            $product['nutritional_info'] = isset($product['nutritional_info']) ? (array) $product['nutritional_info'] : [];
            $product['images'] = isset($product['images']) ? (array) $product['images'] : [];

            // 🛠 Get or Create Nutrition Ingredient
            $name = $product['product_name'];
            $existingProduct = NutritionIngredient::where('name', $name)->first();

            $nutritionData = [
                'name' => $name,
                'kcal' => $product['nutritional_info']['calories'] ?? null,
                'protein' => $product['nutritional_info']['proteins'] ?? null,
                'carbs' => $product['nutritional_info']['carbohydrates'] ?? null,
                'fats' => $product['nutritional_info']['fats'] ?? null,
                'sugar' => $product['nutritional_info']['sugars'] ?? null,
                'salt' => $product['nutritional_info']['salt'] ?? null,
                'image_url' => $product['images']['full'] ?? null,
                'small_image_url' => $product['images']['thumbnail'] ?? null,
                'category' => $product['category'] ?? null,
                'subcategory' => $product['subcategory'] ?? null,
                'slug' => Str::slug($name),
                'status' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ];

            if ($existingProduct) {
                $existingProduct->update($nutritionData);
                $productId = $existingProduct->id;
            } else {
                $productId = DB::table('nutrition_ingredients')->insertGetId($nutritionData);
            }

            // 🛠 Insert Unit Options
            if (!empty($product['unit_options'])) {
                $product['unit_options'] = (array) $product['unit_options']; // Convert to array

                foreach ($product['unit_options'] as $unit) {
                    $unit = (array) $unit; // Convert each unit to array

                    $unitOptionsData[] = [
                        'nutrition_ingredient_id' => $productId,
                        'size_key' => $unit['size_key'] ?? null,
                        'value' => $unit['value'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($unitOptionsData) >= $chunkSize) {
                        NutritionIngredientUnit::insert($unitOptionsData);
                        $unitOptionsData = [];
                    }
                }
            }

            // 🛠 Insert Barcodes
            foreach ($barcodes as $barcode) {
                $barcodesData[] = [
                    'nutrition_ingredient_id' => $productId,
                    'barcode' => $barcode,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (count($barcodesData) >= $chunkSize) {
                DB::table('nutrition_ingredient_barcodes')->upsert($barcodesData, ['barcode'], ['nutrition_ingredient_id', 'updated_at']);
                $barcodesData = [];
            }

            $this->info("Ingredient '$name' added or updated.");
        }

        // Insert remaining batch
        if (!empty($barcodesData)) {
            DB::table('nutrition_ingredient_barcodes')->upsert($barcodesData, ['barcode'], ['nutrition_ingredient_id', 'updated_at']);
        }

        if (!empty($unitOptionsData)) {
            NutritionIngredientUnit::insert($unitOptionsData);
        }

        $this->info('Import completed successfully.');
        return 0;
    }

}
