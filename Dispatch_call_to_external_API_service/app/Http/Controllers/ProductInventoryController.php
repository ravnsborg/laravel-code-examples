<?php

namespace App\Http\Controllers;

use App\Http\Controllers;
use App\Models\Product;
use App\Services\DispatchAPI;

class ProductInventoryController extends Controller
{
    public function __invoke(Product $product): object
    {
        // DispatchJob to add product to inventory api service
        $returnedData = DispatchAPI::run(
            'v1/inventory',
            [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'quantity' => $product->qty_on_hand,
            ]
        );

        return response()->json(
            $returnedData,
            200
        );
    }
}
