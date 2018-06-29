<?php

namespace App\Http\Controllers\View;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\PdtContent;
use App\Entity\PdtImages;
use App\Entity\CartItem;
use Log;

class BookController extends Controller
{
  //书籍类别的展示
  public function toCategory($value='')
  {
    Log::info("进入书籍类别");
    $categorys = Category::whereNull('parent_id')->get();
	
	//var_dump(Category::whereNull('parent_id')->toSql());exit;

    return view('category')->with('categorys', $categorys);
  }
  
  //书籍列表
  public function toProduct($category_id)
  {
    $products = Product::where('category_id', $category_id)->get();
    return view('product')->with('products', $products);
  }
  
  //产品详情
  public function toPdtContent(Request $request, $product_id)
  {
	//根据产品id查询出产品相关信息
    $product     = Product::find($product_id);

	//根据产品id查询出产品详细介绍
    $pdt_content = PdtContent::where('product_id', $product_id)->first();

	//根据产品id查询出产品图片
    $pdt_images  = PdtImages::where('product_id', $product_id)->get();
	
	$count = 0;
	
	/**
		判断用户有没登录
		如果已经登录，则获取购物车表里面的数据；
		如果没有登录，则获取保存在session里面的数量
	**/
    $member = $request->session()->get('member', '');
    if($member != '') {
      $cart_items = CartItem::where('member_id', $member->id)->get();
      foreach ($cart_items as $cart_item) {
        if($cart_item->product_id == $product_id) {
          $count = $cart_item->count;
          break;
        }
      }
    } else {
	  //判断产品id有没在购物车里面
      $bk_cart     = $request->cookie('bk_cart');
      $bk_cart_arr = ($bk_cart!=null ? explode(',', $bk_cart) : array());

      foreach ($bk_cart_arr as $value) {   // 不需要传引用
        $index = strpos($value, ':');
        if(substr($value, 0, $index) == $product_id) {
          $count = (int) substr($value, $index+1);
          break;
        }
      }
    }

	return view('pdt_content')->with('product', $product)
                              ->with('pdt_content', $pdt_content)
                              ->with('pdt_images', $pdt_images)
                              ->with('count', $count);
  }
}
