<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;  // PostgreSQL product model
use App\Models\Log;      // MongoDB log model
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    
    public function index()
    {
        $products = Product::all();
        Log::create([
        'action' => 'create_product',
        'details' => "viewed all products",
        'type' => 'product',
        'metadata' => "no meta"
        ]);
  
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Store a new product
     */
public function store(Request $request)
{
    $user = $request->user(); // Authenticated user

    // Only storekeeper or admin can create products
    if (!in_array($user->role_id, [1, 2])) { // 1 = admin, 2 = storekeeper
        return response()->json([
            'message' => 'Forbidden: You do not have permission to create products.'
        ], 403);
    }

    // Validation rules
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
        'description' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    // Create product
    $product = Product::create($request->all());

    // Log creation in MongoDB
    Log::create([
        'user_id' => $user->id,
        'action' => 'create_product',
        'details' => "Created product: {$product->name}",
        'type' => 'product',
        'metadata' => $product->toArray()
    ]);

    return response()->json([
        'success' => true,
        'data' => $product
    ], 201);
}


    /**
     * Show a single product
     */
     public function show($id)
    {
        $product = Product::findOrFail($id);

        // Calculate current stock without including the full stock history
        $currentStock = $product->stocks->sum(function ($stock) {
            return $stock->type === 'in' ? $stock->quantity : -$stock->quantity;
        });

        // Hide the stocks relationship from the response
        $product->makeHidden('stocks');

        return response()->json([
            'product' => $product,
            'current_stock' => $currentStock
        ]);
    }

    /**
     * Update a product
     */
    public function update(Request $request, $id)
{
    $user = auth()->user();

    // Check if user is admin (1) or storekeeper (2)
    if (!in_array($user->role_id, [1, 2])) {
        return response()->json(['message' => 'Forbidden, User does not have enough rights'], 403);
    }

    $product = Product::find($id);

    if (!$product) {
        return response()->json([
            'success' => false,
            'message' => 'Product not found'
        ], 404);
    }

    // Validation
    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|required|string|max:255',
        'price' => 'sometimes|required|numeric|min:0',
        'description' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    $product->update($request->all());

    // Log update in MongoDB
    Log::create([
        'user_id' => $user->id,
        'action' => 'update_product',
        'details' => "Updated product: {$product->name}",
        'type' => 'product',
        'metadata' => $product->toArray()
    ]);

    return response()->json([
        'success' => true,
        'data' => $product
    ]);
}

    /**
     * Delete a product
     */
    public function destroy($id)
    {
        $user = auth()->user();

        // Check if user is admin (1) or storekeeper (2)
        if (!in_array($user->role_id, [1, 2])) {
            return response()->json(['message' => 'Forbidden, User does not have enough rights'], 403);
        }

        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $productName = $product->name;
        $product->delete();

        // Log deletion
        Log::create([
            'action' => 'delete_product',
            'details' => "Deleted product: {$productName}",
            'type' => 'product',
            'metadata' => ['id' => $id]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted'
        ]);
    }
    public function stockCount($id)
    {
        $product = Product::with('stocks')->findOrFail($id);

        return response()->json([
            'product_id' => $product->id,
            'name' => $product->name,
            'current_stock' => $product->currentStock()
        ]);
    }

}
