<?php

namespace app;

use app\traits\Macroable;

// 应用请求对象类
class Request extends \think\Request
{
	use Macroable;
}
