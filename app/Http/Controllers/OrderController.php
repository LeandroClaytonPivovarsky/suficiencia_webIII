<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'orderItems' => 'required|array|min:1',
            'orderItems.*.product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|gt:0'
            ]);

        try {
            DB::beginTransaction();

            $total = 0;
            $itemsToCreate = [];

            foreach ($validatedData['orderItems'] as $item) {
                $product = Product::find($item['product_id']);
                $total += $product->price * $item['quantity'];

                $itemsToCreate[] = [
                    'product_id'        => $item['product_id'],
                    'quantity'          => $item['quantity'],
                    'price_on_moment'   => $product->price,
                ];
            }

            $order = Order::create([
                'user_id' => $validatedData['user_id'],
                'total' => $total
            ]);

            $order->orderItems()->createMany($itemsToCreate);

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Pedido criado com sucesso!', 'data' => $order->load('orderItems')], 201);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Erro ao criar o pedido: '.$e->getMessage()], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
