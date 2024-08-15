<?php

namespace app\facade;

use think\Facade;

/**
 * @see \app\utils\Redis
 * @method static \Redis getHandler() 获取redis操作类实例
 */
class Redis extends Facade
{
	/**
	 * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
	 * @return string
	 */
	protected static function getFacadeClass(): string
	{
		return \app\utils\Redis::class;
	}
}