<?php

namespace App\Http\Controllers;
use App\Models\Product;
use Illuminate\Http\Request;
class StoreController extends Controller
{
    public function show()
    {
        return view('store', [
            'products' => Product::where('stock', '>', 0)->with(['product_category'])->get()
        ]);
    }

    public function product_insert_form()
    {
        return view('products.insert-from', [
            'categories' => \App\Models\ProductCategory::all()
        ]);
    }

    public function insert_product(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'details' => 'nullable|string',
            'price' => 'required|numeric|min:1',
            'stock' => 'required|integer|min:0',
            'product_category' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ],[
            'name.required' => 'Product name is required.',
            'name.string' => 'Product name must be a string.',
            'name.max' => 'Product name cannot exceed 255 characters.',
            'details.string' => 'Product details must be a string.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a number.',
            'price.min' => 'Price must be at least 1.',
            'stock.required' => 'Stock is required.',
            'stock.integer' => 'Stock must be an integer.',
            'stock.min' => 'Stock cannot be negative.',
            'product_category.required' => 'Product category is required.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg.',
            'image.max' => 'The image may not be greater than 2048 kilobytes.',
        ]);

        $imageName = null;

        if ($request->hasFile('image')) {
            $imageName= time() . '-' . $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path('product_images'), $imageName);
        }

        $product = new Product();
        $product->name = $request->name;
        $product->price = $request->price;
        $product->details = $request->details;
        $product->category_id = $request->product_category;
        $product->stock = $request->stock;
        $product->image_path = $imageName;

        $product->save();

        return redirect()->route('store')->with('success', 'Product added successfully!');
    }

    public function product_edit_form($product_id)
    {
        $product = Product::findOrFail($product_id);
        return view('products.edit-form', [
            'product' => $product,
            'product_categories' => \App\Models\ProductCategory::all()
        ]);
    }

    public function update_product(Request $request, $product_id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'details' => 'nullable|string',
            'price' => 'required|numeric|min:1',
            'stock' => 'required|integer|min:0',
            'product_category' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $product = Product::findOrFail($product_id);

        if ($request->hasFile('image')) {
            $imageName = time() . '-' . $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path('product_images'), $imageName);
            $product->image_path = $imageName;
        }

        $product->name = $request->name;
        $product->price = $request->price;
        $product->details = $request->details;
        $product->category_id = $request->product_category;
        $product->stock = $request->stock;
        $product->save();

        return redirect()->route('store')->with('success', 'Product updated successfully!');
    }

    public function delete_product($product_id)
    {
        $product = Product::findOrFail($product_id);
        $product->delete();
        return redirect()->route('store')->with('success', 'Product deleted successfully!');
    }
}
