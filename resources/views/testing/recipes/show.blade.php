@if ($errors->any())
    <div>
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<h1>{{ $product['product']['product_name'] }}</h1>

@if (isset($product['product']['image_url']))
    <div>
        <img src="{{ $product['product']['image_url'] }}" alt="{{ $product['product']['product_name'] }}" style="max-width: 300px;">
    </div>
@endif

@if (isset($product['product']['ingredients_text']))
    <h2>Ingredients</h2>
    <ul>
        @foreach(explode(',', $product['product']['ingredients_text']) as $ingredient)
            <li>{{ $ingredient }}</li>
        @endforeach
    </ul>
    {{-- <p>{{ $product['product']['ingredients_text'] }}</p> --}}
@else
    <p>No ingredients listed for this product.</p>
@endif

<h2>Nutritional Information</h2>
<ul>
    <li>Carbs: {{ $nutritionalInfo['carbs'] }} g</li>
    <li>Fats: {{ $nutritionalInfo['fats'] }} g</li>
    <li>Protein: {{ $nutritionalInfo['protein'] }} g</li>
    <li>Calories: {{ $nutritionalInfo['kcal'] }} kcal</li>
</ul>
