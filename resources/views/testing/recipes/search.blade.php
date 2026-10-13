@if ($errors->any())
    <div>
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form action="{{ route('recipes.search') }}" method="GET">
    <input type="text" name="query" placeholder="Search for recipes..." value="{{ request('query') }}">
    <button type="submit">Search</button>
</form>

@if(isset($recipes))
    <ul>
        @foreach($recipes as $product)
            <li>
                <a href="{{ route('recipes.show', $product['code']) }}">{{ $product['product_name'] }}</a>
            </li>
        @endforeach
    </ul>

    @if($total_pages > 1)
        <div>
            @for ($i = 1; $i <= $total_pages; $i++)
                <a href="{{ route('recipes.search', ['query' => $query, 'page' => $i]) }}">{{ $i }}</a>
            @endfor
        </div>
    @endif
@else
    <p>No recipes found.</p>
@endif
