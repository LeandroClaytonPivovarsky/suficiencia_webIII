<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $allProducts = Product::with('category')->paginate(15);

        return $this->message('success', 'Produtos listados com sucesso!',200, $allProducts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        Gate::authorize('isAdmin');

        $validatedData = $request->validate([
            'name' => 'required|unique:products,name|string|max:255',
            'quantity' => 'sometimes|integer|min:0',
            'price' => 'required|numeric|gt:0',
            'category_id' => 'required|integer|exists:categories,id'
        ]);

        $product = Product::create($validatedData);

        $product->load('category');

        return $this->message('success', 'Produto criado com sucesso!',201, $product);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with('category')->find($id);

        if(empty($product)){
            return $this->message('error', 'Não foi encontrado nenhum produto!', 404);
        }

        return $this->message('success','Produto encontrado com sucesso!', $product);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize('isAdmin');

        if (empty($request)) {
            return $this->message('error', 'Não foi recebido nenhum dado', 404);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'quantity' => 'sometimes|integer|min:0',
            'price' => 'sometimes|numeric|gt:0',
            'category_id' => 'sometimes|integer|exists:categories,id'
        ]);

        $product = Product::with('category')->find($id);

        if ($product) {
            if($product->update($validatedData)) return $this->message('success','Produto alterado com sucesso', $product);
            else return $this->message('error', 'Algo deu errado na alteração do produto', 404) ;
        } else{
            return $this->message('error', 'Não foi encontrado nenhum produto!', 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        Gate::authorize('isAdmin');

        $product = Product::find($id);

        if ($product) {
            $product->delete();

            return $this->message('success', 'Produto removido com sucesso!', 204);
        }

        return $this->message('error', 'Não foi encontrado nenhum produto!', 404);
    }

    private function message($status, $message, $code, $data = []){

        if (!empty($data)) {
            return response()->json([
                'status'    => $status,
                'message'   => $message,
                'data'      => $data
            ], $code);
        }

        return response()->json([
            'status'    => $status,
            'message'   => $message,
        ], $code);

    }
}
