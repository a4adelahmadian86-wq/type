<?php

namespace App\Http\Controllers;

use App\Models\StoreProduct;
use App\Services\StoreCartService;
use Illuminate\Http\Request;

class StoreCartController extends Controller
{
    public function index(Request $request, StoreCartService $cart)
    {
        $current = $cart->current($request);
        return view('store.cart', ['cart'=>$cart->payload($current)]);
    }

    public function add(Request $request, StoreCartService $cart, StoreProduct $product)
    {
        $data = $request->validate(['quantity'=>'nullable|integer|min:1|max:99']);
        $current = $cart->add($request, $product, (int)($data['quantity'] ?? 1));
        return response()->json(['ok'=>true]+$cart->payload($current));
    }

    public function update(Request $request, StoreCartService $cart, StoreProduct $product)
    {
        $data = $request->validate(['quantity'=>'required|integer|min:0|max:99']);
        $current = $cart->update($request, $product, (int)$data['quantity']);
        return response()->json(['ok'=>true]+$cart->payload($current));
    }

    public function remove(Request $request, StoreCartService $cart, StoreProduct $product)
    {
        $current = $cart->remove($request, $product);
        return response()->json(['ok'=>true]+$cart->payload($current));
    }
}
