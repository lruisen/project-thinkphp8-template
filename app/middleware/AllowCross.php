<?php

namespace app\middleware;

use app\Request;
use Closure;
use think\Response;

class AllowCross
{
	public function handle(Request $request, Closure $next)
	{
		$domain = config('cookie.cross.allowOrigin');
		$header = config('cookie.cross.allowHeaders');

		$origin = $request->header('origin');

		if (! empty($origin)) {
			if ($domain === '*' || str_contains($domain, $origin)) {
				$header['Access-Control-Allow-Origin'] = $origin;
			}
		}

		if ($request->method(true) == 'OPTIONS') {
			$response = Response::create('ok')->code(200)->header($header);
		} else {
			$response = $next($request)->header($header);
		}

		$request->filter(['strip_tags', 'addslashes', 'trim']);
		return $response;
	}
}