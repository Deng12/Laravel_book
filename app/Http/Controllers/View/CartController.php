<?php

namespace App\Http\Controllers\View;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Entity\CartItem;
use App\Entity\Product;

use Log;

class CartController extends Controller
{
  //查看购物车
  public function toCart(Request $request)
  {
    $cart_items  = array();

    $bk_cart     = $request->cookie('bk_cart');
    $bk_cart_arr = ($bk_cart!=null ? explode(',', $bk_cart) : array());
	
	//判断用户是否登录
    $member = $request->session()->get('member', '');
    if($member != '') {
      $cart_items = $this->syncCart($member->id, $bk_cart_arr);

	  //同步购物车的数据，还需要把购物车的数据清空
      return response()->view('cart', ['cart_items' => $cart_items])->withCookie('bk_cart', null);
    }
	
	//当用户没有登录的时候，把session中的商品以及对应的商品数量写进购物车表里面
    foreach ($bk_cart_arr as $key => $value) {
      $index = strpos($value, ':');
      $cart_item = new CartItem;
      $cart_item->id = $key;
      $cart_item->product_id = substr($value, 0, $index);
      $cart_item->count = (int) substr($value, $index+1);
      $cart_item->product = Product::find($cart_item->product_id);
      if($cart_item->product != null) {
        array_push($cart_items, $cart_item);
      }
    }

    return view('cart')->with('cart_items', $cart_items);
  }
  
  //同步购物车的方法
  private function syncCart($member_id, $bk_cart_arr)
  {
	//获取购物车中的数据
    $cart_items = CartItem::where('member_id', $member_id)->get();

    $cart_items_arr = array();
    foreach ($bk_cart_arr as $value) {
      $index = strpos($value, ':');
      $product_id = substr($value, 0, $index);
      $count = (int) substr($value, $index+1);

      /**
		判断购物车表中 product_id 与 cookie中product_id 的对比 
		存在    则往数据库的数量项更改加1；
		不存在  则把新的商品以及对应数量加到购物车表中；
	  **/
      $exist = false;
      foreach ($cart_items as $temp) {
        if($temp->product_id == $product_id) {
          if($temp->count < $count) {
            $temp->count = $count;
            $temp->save();
          }
          $exist = true;
          break;
        }
      }

      // 不存在则存储进来
      if($exist == false) {
        $cart_item              = new CartItem;
        $cart_item->member_id   = $member_id;
        $cart_item->product_id  = $product_id;
        $cart_item->count       = $count;
        $cart_item->save();
		//附加产品对象，以便于显示
        $cart_item->product = Product::find($cart_item->product_id);
        array_push($cart_items_arr, $cart_item);
      }
    }

    // 为每个对象附加产品对象便于显示
    foreach ($cart_items as $cart_item) {
      $cart_item->product = Product::find($cart_item->product_id);
      array_push($cart_items_arr, $cart_item);
    }

    return $cart_items_arr;
  }
}
