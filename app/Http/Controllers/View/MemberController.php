<?php

namespace App\Http\Controllers\View;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MemberController extends Controller
{
  /*
  *  登录页面
  */
  public function toLogin(Request $request)
  {
	//获取传进来的上一级的网址
    $return_url = $request->input('return_url', '');
    return view('login')->with('return_url', urldecode($return_url));
  }
  
  /*
  *  注册页面
  */
  public function toRegister($value='')
  {
    return view('register');
  }
}
