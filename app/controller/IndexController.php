<?php

namespace app\controller;

use app\BaseController;

class IndexController extends BaseController
{
	public function index()
	{
		// 求1-100的和
		$sum = 0;
		for ($i = 1; $i <= 100; $i++) {
			$sum += $i;
		}

		return $this->success($sum);
	}
}
