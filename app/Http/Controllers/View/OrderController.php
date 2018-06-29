<?php

namespace App\Http\Controllers\View;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Models\BKWXJsConfig;
use App\Tool\WXpay\WXTool;
use Log;

class OrderController extends Controller
{
  //结算
  public function toOrderCommit(Request $request)
  {
    // 获取微信重定向返回的code
    $code = $request->input('code', '');
    if($code != '') {
      //获取code码，以获取openid
      $openid = WXTool::getOpenid($code);
      // 将openid保存到session
      $request->session()->put('openid', $openid);
    }
	
	//获取提交过来的产品id
    $product_ids = $request->input('product_ids', '');

    $product_ids_arr = ($product_ids!='' ? explode(',', $product_ids) : array());
	
    $member = $request->session()->get('member', '');
	
	//先根据memberid去查询，然后再根据产品id组去查询，获取购物车列表
    $cart_items = CartItem::where('member_id', $member->id)->whereIn('product_id', $product_ids_arr)->get();

    $order = new Order;
    $order->member_id = $member->id;
    $order->save();

    $cart_items_arr = array();
    $cart_items_ids_arr = array();
    $total_price = 0;
    $name = '';

	//遍历循环把“购物车列表”中的数据 写入到 “订单列表”中
    foreach ($cart_items as $cart_item) {
      $cart_item->product = Product::find($cart_item->product_id);
	  //判断产品是否存在
      if($cart_item->product != null) {
        $total_price += $cart_item->product->price * $cart_item->count;
        $name .= ('《'.$cart_item->product->name.'》');
        
		array_push($cart_items_arr, $cart_item);
        array_push($cart_items_ids_arr, $cart_item->id);

        $order_item               = new OrderItem;
        $order_item->order_id     = $order->id;
        $order_item->product_id   = $cart_item->product_id;
        $order_item->count        = $cart_item->count;
        $order_item->pdt_snapshot = json_encode($cart_item->product);
        $order_item->save();
      }
    }

	//然后把“购物车列表”中的数据清空
    CartItem::whereIn('id', $cart_items_ids_arr)->delete();
	
	//更新到订单表中去
    $order->name = $name;
    $order->total_price = $total_price;
    $order->order_no = 'E'.time().''.$order->id;
    $order->save();

    // JSSDK 相关
    $access_token = WXTool::getAccessToken();
    $jsapi_ticket = WXTool::getJsApiTicket($access_token);
    $noncestr = WXTool::createNonceStr();
    $timestamp = time();
    $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    // 签名
    $signature = WXTool::signature($jsapi_ticket, $noncestr, $timestamp, $url);
    // 返回微信参数
    $bk_wx_js_config = new BKWXJsConfig;
    $bk_wx_js_config->appId = config('wx_config.APPID');
    $bk_wx_js_config->timestamp = $timestamp;
    $bk_wx_js_config->nonceStr = $noncestr;
    $bk_wx_js_config->signature = $signature;

    return view('order_commit')->with('cart_items', $cart_items_arr)
                               ->with('total_price', $total_price)
                               ->with('name', $name)
                               ->with('order_no', $order->order_no)
                               ->with('bk_wx_js_config', $bk_wx_js_config);
  }
  
  //显示订单详情列表
  public function toOrderList(Request $request)
  {
    $member = $request->session()->get('member', '');

	//根据该登录用户查询吃购买过的订单
    $orders = Order::where('member_id', $member->id)->get();
    foreach ($orders as $order) {
	  //根据订单id获取到各自订单中的详细信息
      $order_items = OrderItem::where('order_id', $order->id)->get();
      $order->order_items = $order_items;
      foreach ($order_items as $order_item) {
        $order_item->product = json_decode($order_item->pdt_snapshot);
      }
    }

    return view('order_list')->with('orders', $orders);
  }
}
