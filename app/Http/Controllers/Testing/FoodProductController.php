<?php

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OpenFoodFactsService;

class FoodProductController extends Controller
{
    protected $openFoodFactsService;

    public function __construct(OpenFoodFactsService $openFoodFactsService)
    {
        $this->openFoodFactsService = $openFoodFactsService;
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $page = $request->input('page', 1);
        $pageSize = 20;

        $recipes = $this->openFoodFactsService->searchRecipes($query, $page, $pageSize);

        if (!$recipes) {
            return back()->withErrors(['message' => 'Unable to fetch recipes at this time. Please try again later.']);
        }

        return view('testing.recipes.search', [
            'recipes' => $recipes['products'] ?? [],
            'page' => $page,
            'total_pages' => ceil(($recipes['count'] ?? 0) / $pageSize),
            'query' => $query
        ]);
    }

    public function show($barcode)
    {
        $product = $this->openFoodFactsService->getProductDetails($barcode);

        if (!$product) {
            return back()->withErrors(['message' => 'Unable to fetch product details at this time. Please try again later.']);
        }

        $nutritionalInfo = [
            'carbs' => $product['product']['nutriments']['carbohydrates_100g'] ?? 'N/A',
            'fats' => $product['product']['nutriments']['fat_100g'] ?? 'N/A',
            'protein' => $product['product']['nutriments']['proteins_100g'] ?? 'N/A',
            'kcal' => $product['product']['nutriments']['energy-kcal_100g'] ?? 'N/A',
        ];

        return view('testing.recipes.show', compact('product', 'nutritionalInfo'));
    }
}
