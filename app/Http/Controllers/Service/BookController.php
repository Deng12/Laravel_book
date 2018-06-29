<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Entity\Category;
use App\Models\M3Result;

class BookController extends Controller
{
  public function getCategoryByParentId($parent_id)
  {
    //根据一级类别获取二级类别的数据
    $categorys = Category::where('parent_id', $parent_id)->get();
   
    $m3_result = new M3Result;
    $m3_result->status = 0;
    $m3_result->message = '返回成功';
    $m3_result->categorys = $categorys;
	
    return $m3_result->toJson();
  }
}
