<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Validator;

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


    // Cart
    public function cart()
    {
        $cart = Session::get('cart');
        return view('customer.cart', ['cart' => $cart]);
    }


    public function addToCart(Request $request)
    {
        $menuId = $request->input('id');
        $menu = Item::find($menuId);

        if (!$menu) {
            return response()->json([
                'status' => false,
                'message' => 'Menu tidak ditemukan'
            ]);
        }

        $cart = Session::get('cart', []);

        if (isset($cart[$menuId])) {
            $cart[$menuId]['qty'] += 1;
        } else {
            $cart[$menuId] = [
                'id' => $menu->id,
                'name' => $menu->name,
                'price' => $menu->price,
                'image' => $menu->img,
                'qty' => 1
            ];
        }

        Session::put('cart', $cart);
        Session::save(); // <-- ini yang bikin cart tersimpan

        return response()->json([
            'status' => true,
            'message' => 'Menu berhasil ditambahkan ke keranjang',
            'cart' => $cart
        ]);
    }

   public function updateCart(Request $request)
    {
        $itemId = $request->input('id');
        $newQty = $request->input('qty');

        if ($newQty <= 0) {
            return response()->json(['success' => false]);
        }

        $cart = Session::get('cart');
        if (isset($cart[$itemId])) {
            $cart[$itemId]['qty'] = $newQty;
            Session::put('cart', $cart);
            Session::flash('success', 'Jumlah item berhasil diperbarui');

            return response()->json([ 'success' => true]);
        }

        return response()->json(['success' => false]);
    }

    public function removeCart(Request $request)
    {
        $itemId = $request->input('id');

        $cart = Session::get('cart');
        if (isset($cart[$itemId])) {
            unset($cart[$itemId]);
            Session::put('cart', $cart);
            Session::flash('success', 'Item berhasil dihapus dari keranjang');

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }

    public function clearCart()
    {
        Session::forget('cart');
        Session::flash('success', 'Keranjang berhasil dikosongkan');

        return redirect()->route('cart')->with('success', 'Keranjang berhasil dikosongkan');
    }

    // Checkout
    public function checkout()
    {
        $cart = Session::get('cart');
        if(empty($cart)){
            return redirect()->route('cart')->with('error', 'Keranjang kosong, silakan tambahkan item terlebih dahulu.');
        }
        $tableNumber = Session::get('tableNumber');
        return view('customer.checkout', ['cart' => $cart, 'tableNumber' => $tableNumber]);
    }

    public function storeOrder(Request $request)
    {
        $cart = Session::get('cart');
        $tableNumber = Session::get('tableNumber');
        if (empty($cart)) {
            return redirect()->route('cart')->with('error', 'Keranjang kosong, silakan tambahkan item terlebih dahulu.');
        }

        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
        
        ]);

        if ($validator->fails()) {
            return redirect()->route('checkout')
                ->withErrors($validator);
        }

        $total=0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['qty'];
        }

        $totalAmount = $total;
        foreach ($cart as $item) {
            $totalAmount += $item['qty'] * $item['price'];

            $itemDetail = [
                'id' => $item['id'],                
                'price' => (int) ($item['price']+($item['price']*0.1)),
                'quantity' => $item['qty'],
                'name' => substr($item['name'], 0, 50),
            ];
        }

        $user=User::firstOrCreate([
                'fullname' => $request->input('fullname'),
                'phone' => $request->input('phone'),
                'role_id' => 4
        ]);
        
        $order = Order::create([
            'order_code' => 'ORD-'.$tableNumber.'-'. time(),
            'user_id' => $user->id,
            'subtotal' => $totalAmount,
            'tax' => 0.1 * $totalAmount,
            'grand_total' => $totalAmount + (0.1 * $totalAmount),
            'status' => 'pending',
            'table_number' => $tableNumber,
            'payment_method' => $request->payment_method,
            'note' => $request->note,
        ]);

        foreach ($cart as $itemId=>$item) {
            OrderItem::create([
                'order_id' => $order->id,
                'item_id' => $item['id'],
                'quantity' => $item['qty'],
                'price' => $item['price'] * $item['qty'],
                'tax' => 0.1 * ($item['price'] * $item['qty']),
                'total_price' => 1.1 * ($item['price'] * $item['qty']),
            ]);
        }

        // Setelah pesanan disimpan, kosongkan keranjang
        Session::forget('cart');

        // if ($request->payment_method == 'tunai') {
        //     return redirect()->route('checkout.succes',['order_id'=>$order->order_code])->with('success', 'Pesanan berhasil dibuat. Silakan bayar di kasir.');
        // }

        return redirect()->route('checkout.success',['orderId'=>$order->order_code])->with('success', 'Pesanan berhasil dibuat. Terima kasih!');
    }

    public function checkoutSuccess($orderId)
    {
        $order = Order::where('order_code', $orderId)->first();

        if (!$order) {
            return redirect()->route('menu')->with('error', 'Pesanan tidak ditemukan.');
        }

        $orderItems = OrderItem::where('order_id', $order->id)->get();

        if ($order->payment_method == 'qris') {
            $order->status = 'settlement';
            $order->save();
        }
        return view('customer.success', ['order' => $order,'orderItems' => $orderItems]);
    
    }

}
