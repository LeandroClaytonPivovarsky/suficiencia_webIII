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

    public function viewAny(){
        $this->authorize('viewAny', Order::class);

        $allOrders = Order::with(['user', 'orderItems.product.category'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(15);

        return $this->message('success', 'Pedidos resgatados com sucesso!', $allOrders);

    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $userId = Auth::user()->id;

        $allOrders = Order::where('user_id', $userId)
                    ->with(['user', 'orderItems.product.category'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(15);

        return $this->message('success', 'Pedidos resgatados com sucesso!', $allOrders);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'orderItems' => 'required|array|min:1',
            'orderItems.*.product_id' => 'required|integer|exists:products,id',
            'orderItems.*.quantity' => 'required|integer|gt:0'
            ]);

        try {
            DB::beginTransaction();

            $total = 0;
            $itemsToCreate = [];

            foreach ($validatedData['orderItems'] as $item) {
                $product = Product::find($item['product_id']);

                if ($item['quantity'] > $product['quantity']) {
                    return $this->message('error', 'Não há produtos suficientes!');
                }

                $total += $product->price * $item['quantity'];

                $itemsToCreate[] = [
                    'product_id'        => $item['product_id'],
                    'quantity'          => $item['quantity'],
                    'price_on_moment'   => $product->price,
                ];
            }

            $order = Order::create([
                'user_id' => Auth::user()->id,
                'final_price' => $total
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
        $order = Order::with(['user', 'orderItems.product.category'])->find($id);

        if (empty($order)) {
            return $this->message('error', 'Não foi encontrado nenhum pedido!');
        }

        $this->authorize('view', $order);

        return $this->message('success', 'Pedido encontrado', $order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validatedData = $request->validate(
            [
                'user_id' => 'prohibited',
                'orderItems' => 'sometimes|array|min:1',
                'orderItems.*.product_id' => 'sometimes|integer|exists:products,id',
                'orderItems.*.quantity' => 'sometimes|integer|gt:0'
            ]);

        $order = Order::with(['user', 'orderItems.product.category'])->find($id);

        if (empty($order)) {
            return $this->message('error', 'Não foi encontrado nenhum pedido!');
        }

        $this->authorize('update', $order);

        if ($order->status == -1 || $order->status == 1) {
            return $this->message('error', 'Não é possível alterar este pedido.'); // 403 Forbidden
        }

        try {
            DB::beginTransaction();

            $order->orderItems()->delete();

            $total = 0;
            $itemsToCreate = [];

            foreach ($validatedData['orderItems'] as $item) {
                $product = Product::find($item['product_id']);

                // Verificação de estoque (boa prática)
                if ($item['quantity'] > $product->quantity) {
                    throw new Exception('Produto ' . $product->name . ' não tem estoque suficiente.');
                }

                $total += $product->price * $item['quantity'];

                $itemsToCreate[] = [
                    'product_id'      => $item['product_id'],
                    'quantity'        => $item['quantity'],
                    'price_on_moment' => $product->price,
                ];
            }

            $order->orderItems()->createMany($itemsToCreate);
            $order->update([
                'final_price' => $total
            ]);

            DB::commit();

            $data = $order->load(['user', 'orderItems.product.category']);
            return $this->message('success', 'Pedido atualizado com sucesso!', $data);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->message('error', 'Erro ao atualizar o pedido: '.$e->getMessage());
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = Order::find($id);

        if (empty($order)) {
            return $this->message('error', 'Não foi encontrado nenhum pedido!');
        }

        $this->authorize('delete', $order);

        if ($order) {
            $order->delete();

            return $this->message('success', 'Pedido apagado com sucesso!');
        }

        return $this->message('error', 'Não foi encontrado nenhum pedido!');

    }

    private function message($status, $message, $data = []){

        if (!empty($data)) {
            return response()->json([
                'status'    => $status,
                'message'   => $message,
                'data'      => $data
            ]);
        }

        return response()->json([
            'status'    => $status,
            'message'   => $message,
        ]);

    }
}
