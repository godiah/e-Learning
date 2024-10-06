<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoriesResource;
use App\Models\Categories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoriesController extends Controller
{
    //Displays all categories
    public function index()
    {
        $categories = Categories::get();
        if($categories->count() > 0)
        {
            return CategoriesResource::collection($categories);
        }
        else
        {
            return response()->json(['message' => 'No categories found'], 404);
        }
    }

    //Add a new category
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name' => 'required|string|max:255|unique:App\Models\Categories,name',
            'description' => 'required|string',
        ]);
        
        if($validator->fails())
        {
            return response()->json([
                'message' => 'All Fields are mandatory',
                'error' => $validator->messages(),
            ], 422);
        }

        $categories = Categories::create([
            'name'=> $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => new CategoriesResource($categories)
        ]);
    }

    //Display a single category
    public function show(Categories $category)
    {
        return new CategoriesResource($category);
    }

    //Update a category
    public function update(Request $request, Categories $category)
    {
        $validator = Validator::make($request->all(),[
            'name' => 'string|max:255|unique:App\Models\Categories,name',
            'description' => 'string',
        ]);
        
        if($validator->fails())
        {
            return response()->json([
                'error' => $validator->messages(),
            ], 422);
        }

        $category->update([
            'name'=> $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => new CategoriesResource($category)
        ]);
    }

    //Delete a category
    public function destroy(Categories $category)
    {
        $category->delete();
        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}
