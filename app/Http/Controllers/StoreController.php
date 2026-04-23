<?php

namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        if(!Gate::allows('insert_product')){
                abort(403, 'Unauthorized');
        }
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
        if(!Gate::allows('update_product')){
            abort(403, 'Unauthorized');
        }
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
        if(!Gate::allows('delete_product')){
            abort(403, 'Unauthorized');
        }
        $product = Product::findOrFail($product_id);
        $product->delete();
        return redirect()->route('store')->with('success', 'Product deleted successfully!');
    }

    public function add_to_cart(Request $request, $product_id){
        $product = Product::findOrFail($product_id);
        $quantity = $request->input('quantity', 1);

        if ($quantity < 1) {
            return redirect()->route('store')->with('error', 'Quantity must be at least 1.');
        }

        $cart = session()->get('cart', []);
        
        $existingQuantity = isset($cart[$product_id]) ? $cart[$product_id]['quantity'] : 0;
        $totalQuantity = $existingQuantity + $quantity;

        if ($totalQuantity > $product->stock) {
            return redirect()->route('store')->with('error', 'Requested total quantity exceeds available stock.');
        }

        if (isset($cart[$product_id])) {
            $cart[$product_id]['quantity'] = $totalQuantity;
        } else {
            $cart[$product_id] = [
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $quantity,
            ];
        }
        
        session()->put('cart', $cart);

        return redirect()->route('store')->with('success', 'Product added to cart successfully!');
    }

    public function view_cart()
    {
        $cart = session()->get('cart', []);
        return view('store.cart', ['cart' => $cart]);
    }
    public function remove_from_cart($product_id){
        $cart = session()->get('cart', []);
        
        if (isset($cart[$product_id])) {
            unset($cart[$product_id]);
            session()->put('cart', $cart);
        }

        return redirect()->back()->with('success', 'Item removed from cart.');
    }

    public function update_cart(Request $request, $product_id){
        $quantity = (int) $request->input('quantity');

        if ($quantity < 1) {
            return $this->remove_from_cart($product_id);
        }

        $product = Product::findOrFail($product_id);

        if ($quantity > $product->stock) {
            return redirect()->back()->with('error', 'Requested quantity exceeds available stock.');
        }

        $cart = session()->get('cart', []);
        if (isset($cart[$product_id])) {
            $cart[$product_id]['quantity'] = $quantity;
            session()->put('cart', $cart);
        }

        return redirect()->back()->with('success', 'Cart updated successfully.');
    }
    public function checkout(Request $request){
        $cart = session('cart', []);
 
        if (empty($cart)) {
            return redirect()->back()->with('error', 'Your cart is empty!');
        }
 
        DB::beginTransaction();
        try {
            $totalPrice = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
 
            $order = Order::create([
                'invoice_number' => 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'user_id' => Auth::id(),
                'customer_name' => Auth::user()->name,
                'total_price' => $totalPrice,
                'status' => 'pending',
                'payment_url' => null,
                'paid_at' => null,
            ]);
 
            foreach ($cart as $product_id => $item) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $product_id,
                    'product_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity'],
                ]);
            }
            
            DB::commit();
 
            session()->forget('cart'); // Clear cart
 
            return redirect()->route('store')->with('success', 'Checkout successful! Thank you for your purchase.');
		} catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Checkout failed: ' . $e->getMessage());
        }
    }
}
