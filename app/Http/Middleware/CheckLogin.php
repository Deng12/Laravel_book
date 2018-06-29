<?php

namespace App\Http\Middleware;

use Closure;

class CheckLogin
{
    /**

	   检查用户是否登录

     * Run the request filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $member = $request->session()->get('member', '');
        if($member == '') {
		  //获取上次登录的网址
          $return_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
          return redirect('/login?return_url=' . urlencode($return_url));
        }

        return $next($request);
    }

}
