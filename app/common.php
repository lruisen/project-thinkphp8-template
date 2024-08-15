<?php
// 应用公共文件

use think\Collection;
use think\db\BaseQuery;
use think\facade\Db;

if (! function_exists("db")) {
	/***
	 * 实例化数据库类
	 * @param string $name 操作的数据表名称（不含前缀）
	 * @param string|null $flag 数据库连接配置标识
	 * @param bool $force 是否强制重新连接
	 * @return BaseQuery
	 */
	function db(string $name, string $flag = null, bool $force = false): BaseQuery
	{
		return Db::connect($flag, $force)->name($name);
	}
}

if (! function_exists('throw_exception')) {
	/**
	 * 抛出异常
	 * @param        $msg
	 * @param int $code 错误码
	 * @param string $exception 异常类
	 * @return mixed
	 */
	function throw_exception($msg, int $code = 400, string $exception = ''): mixed
	{
		$exception = $exception ?: '\app\exceptions\ApiException';
		throw new $exception($msg, $code);
	}
}

if (! function_exists('msec_time')) {
	/**
	 * 获取当前毫秒数
	 * @return float
	 */
	function msec_time(): float
	{
		list($msec, $sec) = explode(' ', microtime());
		return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
	}
}

if (! function_exists('get_paginate')) {
	/**
	 * 获取分页配置
	 * @param bool $isPage 是否分页
	 * @param bool $isRelieve 是否分页限制
	 * @return int[] [$page, $limit, $cursor, $defaultLimit]
	 */
	function get_paginate(bool $isPage = true, bool $isRelieve = true): array
	{
		$page = $limit = 0;

		if ($isPage) {
			$page = app()->request->param(config('database.page.pageKey', 'current_page') . '/d', 0);
			$limit = app()->request->param(config('database.page.limitKey', 'page_size') . '/d', 0);
		}

		$cursor = app()->request->param(config('database.page.cursorKey', 'cursor'));
		$limitMax = config('database.page.limitMax', 50);
		$defaultLimit = config('database.page.defaultLimit', 10);

		if ($limit > $limitMax && $isRelieve) {
			$limit = $limitMax;
		}

		return [$page, $limit, $cursor, $defaultLimit];
	}
}


if (! function_exists('write_log')) {
	/**
	 * 日志记录
	 * @param mixed $e 异常类
	 * @param string $remark 备注
	 * @param string $type 日志类型
	 * @return void
	 */
	function write_log(mixed $e, string $remark, string $type = 'error'): void
	{
		if (! $e instanceof Exception) {
			$e = new Exception($e . " " . $remark);
		}

		$format = <<<EOT
----------------------------------------
$remark ：%s
文件：%s
行数：%s
----------------------------------------
EOT;

		try {
			app()->log->record(
				sprintf($format,
					$e->getMessage(),
					$e->getFile(),
					$e->getLine()
				),
				$type
			);
		} catch (Exception) {
		}
	}
}


if (! function_exists('get_file_relative_path')) {
	/**
	 *  获取文件相对路径
	 * @param string $path 文件路径
	 * @param string $dir 文件夹
	 * @return array|string
	 */
	function get_file_relative_path(string $path, string $dir = ''): array|string
	{
		if (str_starts_with($path, 'http')) {
			$parseUrl = parse_url($path);
			$pathInfo = pathinfo($parseUrl['path']);
			return sprintf('%s/%s', rtrim($pathInfo['dirname'], '/'), $pathInfo['basename']);
		}

		empty($dir) && $dir = public_path();

		return str_replace($dir, '', $path);
	}
}

if (! function_exists('create_unique_id')) {
	/**
	 *  通过毫秒时间生成唯一ID
	 * @param string $prefix
	 * @return string
	 */
	function create_unique_id(string $prefix = ''): string
	{
		return $prefix . str_pad(msec_time(), 13, '0') . mt_rand(1000, 9999);
	}
}

if (! function_exists('create_uuid')) {
	/**
	 *  生成唯一UUID
	 * @param bool $lowercase 是否小写
	 * @return string
	 * @throws Exception
	 */
	function create_uuid(bool $lowercase = true): string
	{
		return app\utils\Ulid::generate($lowercase)->getRandomness();
	}
}

if (! function_exists('create_snowflake_id')) {
	/**
	 * 通过雪花算法生成唯一ID
	 * @param string $prefix 前缀
	 * @return string
	 * @throws Exception
	 */
	function create_snowflake_id(string $prefix = ''): string
	{
		$snowflake = new Godruoyi\Snowflake\Snowflake();

		// 自定义序列号解析器
		$callable = function ($currentTime) {
			$swooleSequenceResolver = new Godruoyi\Snowflake\RedisSequenceResolver(\app\facade\Redis::getHandler());
			return $swooleSequenceResolver->sequence($currentTime);
		};

		// 32位
		if (PHP_INT_SIZE == 4) {
			$id = abs($snowflake->setSequenceResolver($callable)->id());
		} else {
			$id = $snowflake->setStartTimeStamp(strtotime('2015-01-01') * 1000)->setSequenceResolver($callable)->id();
		}

		return sprintf('%s%s', $prefix, $id);
	}
}

if (! function_exists('filter_emoji')) {
	/**
	 * 过滤emoji表情
	 * @param string $str
	 * @return string
	 */
	function filter_emoji(string $str): string
	{
		return preg_replace_callback('/./u', fn($match) => strlen($match[0]) >= 4 ? '' : $match[0], $str);
	}
}

if (function_exists('set_paginate')) {
	/**
	 * 包装分页样式的数据结构
	 * @param array|Collection $data
	 * @return int[]
	 */
	function set_paginate(array|Collection $data): array
	{
		if (! $data instanceof Collection) {
			$data = collect($data);
		}

		return [
			'data' => $data ?? [],
			'current_page' => $data->current_page ?? 1,
			'page_size' => $data->per_page ?? 99999,
			'last_page' => $data->last_page ?? 0,
			'total' => $data->total ?? $data->count(),
		];
	}
}