<?php

namespace App\Http\Controllers\Service;

use App\Tool\Validate\ValidateCode;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Tool\SMS\SendTemplateSMS;
use App\Entity\TempPhone;
use App\Models\M3Result;
use App\Entity\TempEmail;
use App\Entity\Member;

class ValidateController extends Controller
{
  //生成验证码
  public function create(Request $request)
  {
    $validateCode = new ValidateCode;
    $request->session()->put('validate_code', $validateCode->getCode());
    return $validateCode->doimg();
  }
	
  //发送短信
  public function sendSMS(Request $request)
  {
    $m3_result = new M3Result;

    $phone = $request->input('phone', '');
    if($phone == '') {
      $m3_result->status = 1;
      $m3_result->message = '手机号不能为空';
      return $m3_result->toJson();
    }
    if(strlen($phone) != 11 || $phone[0] != '1') {
      $m3_result->status = 2;
      $m3_result->message = '手机格式不正确';
      return $m3_result->toJson();
    }
	
	/**
	    调用发送短信的接口
		new SendTemplateSMS->sendTemplateSMS
		(电话号码，（验证码，验证码持续的分钟数），模板参数);
	**/

    $sendTemplateSMS = new SendTemplateSMS;

	//随机生成6个数字的验证码
    $code = '';
    $charset = '1234567890';
    $_len = strlen($charset) - 1;
    for ($i = 0;$i < 6;++$i) {
        $code .= $charset[mt_rand(0, $_len)];
    }

    $m3_result = $sendTemplateSMS->sendTemplateSMS($phone, array($code, 60), 1);

    if($m3_result->status == 0) {
	  //先查询这个号码有没被注册
      $tempPhone = TempPhone::where('phone', $phone)->first();
      if($tempPhone == null) {
		//把验证码保存到临时表
        $tempPhone = new TempPhone;
      }
      $tempPhone->phone = $phone;
      $tempPhone->code = $code;
      $tempPhone->deadline = date('Y-m-d H-i-s', time() + 60*60);
      $tempPhone->save();
    }

    return $m3_result->toJson();
  }

  public function validateEmail(Request $request)
  {
    $member_id = $request->input('member_id', '');
    $code = $request->input('code', '');
    if($member_id == '' || $code == '') {
      return '验证异常';
    }

    $tempEmail = TempEmail::where('member_id', $member_id)->first();
    if($tempEmail == null) {
      return '验证异常';
    }

    if($tempEmail->code == $code) {
      if(time() > strtotime($tempEmail->deadline)) {
        return '该链接已失效';
      }

      $member = Member::find($member_id);
      $member->active = 1;
      $member->save();

      return redirect('/login');
    } else {
      return '该链接已失效';
    }
  }
}
