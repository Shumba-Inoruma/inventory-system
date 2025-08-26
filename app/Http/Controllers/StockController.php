<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Log;

class StockController extends Controller
{
    // Add stock
    public function addStock(Request $request)
    {
        $user = $request->user(); // Authenticated user via token

        // Only admin (1) or storekeeper (2) can add stock
        if (!in_array($user->role_id, [1, 2])) {
            return response()->json(['message' => 'Forbidden, User does not have enough rights'], 403);
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);

        $stock = Stock::create([
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => $request->quantity
        ]);
        $product->load('stocks');

        // Log to MongoDB
        Log::create([
            'user_id' => $user->id,
            'action' => 'stock_in',
            'details' => "Added {$request->quantity} units to {$product->name}",
            'type' => 'success',
            'metadata' => ['product_id' => $product->id, 'quantity' => $request->quantity]
        ]);

        return response()->json([
            'message' => 'Stock added successfully',
            'current_stock' => $product->currentStock()
        ]);
    }

    // Remove stock
    public function removeStock(Request $request)
    {
        $user = $request->user(); // Authenticated user via token

        // Only admin (1) or storekeeper (2) can remove stock
        if (!in_array($user->role_id, [1, 2])) {
            return response()->json(['message' => 'Forbidden, User does not have enough rights'], 403);
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);
        $currentStock = $product->currentStock();

        if ($request->quantity > $currentStock) {
            // Log failure
            Log::create([
                'user_id' => $user->id,
                'action' => 'stock_out',
                'details' => "Attempted to remove {$request->quantity} units from {$product->name} but only {$currentStock} available",
                'type' => 'failed',
                'metadata' => ['product_id' => $product->id, 'requested' => $request->quantity, 'available' => $currentStock]
            ]);

            return response()->json([
                'message' => 'Insufficient stock',
                'current_stock' => $currentStock
            ], 400);
        }

        // Create stock out record
        $stock = Stock::create([
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => $request->quantity
        ]);
        $product->load('stocks');

        // Log success
        Log::create([
            'user_id' => $user->id,
            'action' => 'stock_out',
            'details' => "Removed {$request->quantity} units from {$product->name}",
            'type' => 'success',
            'metadata' => ['product_id' => $product->id, 'quantity' => $request->quantity]
        ]);

        return response()->json([
            'message' => 'Stock removed successfully',
            'current_stock' => $product->currentStock()
        ]);
    }
}
