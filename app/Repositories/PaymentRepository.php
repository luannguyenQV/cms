<?php

namespace App\Repositories;

use App\Models\Country;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\Tax;
use Carbon\Carbon;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request, DB, Auth;

class PaymentRepository
{

    /**
     *
     * Payments Class for shopping cart,payments,taxes.
     * Just move logic outside from Eloquent model.
     *
     * @package ecommerce-cms
     * @category Base Class
     * @author Tihomir Blazhev <raylight75@gmail.com>
     * @link https://raylight75@bitbucket.org/raylight75/ecommerce-cms.git
     */

    /**
     * Check for discount code.
     * @param Request $request
     * @return bool
     */
    public static function checkDiscount(Request $request)
    {
        $codes = Tax::all();
        foreach ($codes as $code) {
            if ($request->has('discount') && $request->input('discount') !== $code->code) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Prepare order and customer data for database.
     * @param Request $request
     */
    public static function createOrder(Request $request)
    {
        $cart = Cart::content();
        $customer = $request->session()->all();
        $customer['user_id'] = Auth::user()->id;
        Customer::create($customer);
        foreach ($cart as $item) {
            Order::create([
                'user_id' => Auth::user()->id,
                'order_date' => Carbon::now(),
                'product_id' => $item->id,
                'quantity' => $item->qty,
                'amount' => $item->subtotal,
                'size' => $item->options->size,
                'img' => $item->options->img,
                'color' => $item->options->color,
            ]);
        }
    }

    /**
     * Pass data to checkout view.
     * @return mixed
     */
    public static function checkoutData()
    {
        $data['countries'] = Country::all();
        $data['payments'] = Payment::all();
        $data['shippings'] = Shipping::all();
        return $data;
    }

    /**
     * Show order information from session.
     * @param Request $request
     * @return mixed
     */
    public static function prepareShow(Request $request)
    {
        $vat = Country::where('name', $request->session()->get('country'))->first();
        $data['vat'] = $vat->vat;
        $data['payments'] = Payment::findOrFail($request->session()->get('payment'));
        $data['shippings'] = Shipping::findOrFail($request->session()->get('delivery'));
        $data['customer'] = $request->session()->all();
        return $data;
    }

    /**
     * Add a row to the cart
     * @param Request $request
     * @return array
     */
    public static function prepareStore(Request $request)
    {
        $code = Tax::where('code', $request->input('discount'))->first();
        isset($code) ? $discount = $code->discount : $discount = 0;
        $productPrice = $request->input('price');
        $price = ((100 - $discount) / 100) * $productPrice;
        $data = $request->except(['_token', 'discount', 'price', 'color', 'size', 'img']);
        $data['price'] = $price;
        $data['options'] = $request->except(['_token', 'id', 'name', 'qty', 'price']);
        return $data;
    }
}