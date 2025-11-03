<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Can;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $allCategories = Category::all();

        return response()->json(['status' => 'success', 'message' => 'Categorias resgatadas com sucesso!', 'data' => $allCategories], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        Gate::authorize('isAdmin');

        $validateData = $request->validate([
            'name' => 'required|string|unique:categories,name|max:255'
        ]);

        $category = new Category($validateData);
        if(!$category->save()){
            return response()->json(['status' => 'error', 'message' => 'Erro ao salvar a categoria!'], 500);
        }
        return response()->json(['status' => 'success', 'message' => 'Categoria cadastrada com sucesso!', 'data' => $category], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::find($id);

        if(!$category || empty($category)){
            return response()->json(['status' => 'error', 'message' => 'Categoria não encontrada!'], 500);
        }

        return response()->json(['status' => 'success', 'message' => 'Categoria encontrada!', 'data' => $category], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize('isAdmin');

        $validateData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = Category::find($id);

        if ($category) {
            $category->update($validateData);

            return response()->json(['status' => 'success', 'message' => 'Categoria atualizada com sucesso!', 'data' => $category], 201);
        }
        return response()->json(['status' => 'error', 'message' => 'Categoria não encontrada!'], 500);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize('isAdmin');

        $category = Category::find($id);

        if ($category) {
            $category->delete();

            return response()->json(['status' => 'success', 'message' => 'Categoria deletada com sucesso!'], 201);
        }
        return response()->json(['status' => 'error', 'message' => 'Categoria não encontrada!'], 500);
    }
}
