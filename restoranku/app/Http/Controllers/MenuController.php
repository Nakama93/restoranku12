<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\Item;

class MenuController extends Controller
{
    public function index(Request $request)
    {
            $tableNumber = $request->query('meja');
            if ($tableNumber) {
                Session::put('tableNumber', $tableNumber);
            }
            $items = Item::where('is_active', 1)->orderBy('name','asc')->get();
        
        
            return view('customer.menu', ['items' => $items, 'tableNumber' => $tableNumber]);
    }

    public function cart()
    {
        $cart = Session::get('cart');
        return view('customer.cart', ['cart' => $cart]);
    }


    public function addToCart(Request $request)
    {
        $menuId = $request->input('menu_id');
        $menu = Item::find($menuId);

        if(!$menu) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Menu tidak ditemukan'
            ]) ;
        }

        $cart = Session::get('cart', []);

        if(isset($cart[$menuId])) {
            $cart[$menuId]['qty'] += 1;
        } else {
            $cart[$menuId] = [
                'id' => $menu->id,
                'name' => $menu->name,
                'price' => $menu->price,
                'img' => $menu->img,
                'qty' => 1
            ];
        }

        Session::put('cart', $cart);

        return response()->json([
            'status' => 'Success',
            'message' => 'Menu berhasil ditambahkan ke keranjang',
            'cart' => $cart
        ]);
    }
}
