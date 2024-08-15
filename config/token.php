<?php

use think\facade\Env;

return [
	// 默认驱动方式
	'default' => 'redis',
	// 加密key
	'key' => 'jpRHkVOTdb7EWNBcgwtY8Ao3X2sq0m4f',
	// 加密方式
	'algo' => 'ripemd160',
	// 默认 token 有效时间
	'admin_token_keep_time' => 86400 * 3,
	// 驱动
	'stores' => [
		'mysql' => [
			'type' => 'Mysql',
			// 留空表示使用默认的 Mysql 数据库，也可以填写其他数据库连接配置的`name`
			'name' => '',
			// 存储token的表名
			'table' => 'token',
			// 默认 token 有效时间
			'expire' => 86400 * 7,
		],
		'redis' => [
			'type' => 'Redis',
			'host' => Env::get('REDIS_HOST', '127.0.0.1'),
			'port' => Env::get('REDIS_PORT', '6379'),
			'password' => Env::get('REDIS_PASSWORD', ''),
			// Db索引，非 0 以避免数据被意外清理
			'select' => intval(Env::get('REDIS_DB', 0)),
			'timeout' => 0,
			// 默认 token 有效时间
			'expire' => 86400 * 7,
			'persistent' => false,
			'prefix' => 'tk:',
		],
	]
];