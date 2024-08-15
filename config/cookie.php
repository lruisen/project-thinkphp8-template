<?php
// +----------------------------------------------------------------------
// | Cookie设置
// +----------------------------------------------------------------------
use think\facade\Env;

return [
	// cookie 保存时间
	'expire' => 0,
	// cookie 保存路径
	'path' => '/',
	// cookie 有效域名
	'domain' => '',
	//  cookie 启用安全传输
	'secure' => false,
	// httponly设置
	'httponly' => false,
	// 是否使用 setcookie
	'setcookie' => true,
	// samesite 设置，支持 'strict' 'lax'
	'samesite' => '',
	// token配置
	'token' => [
		// 多端登录
		'multi_login' => false,
		// 缓存前缀
		'prefix' => '',
		// 加密方式
		'hashalgo' => 'ripemd160',
		// 缓存有效期 0表示永久缓存
		'admin_expire' => 86400 * 3,
		// 缓存有效期 0表示永久缓存
		'user_expire' => 86400 * 3,
		// Redis 缓存数据库位置
		'redis_db' => Env::get('REDIS_DB', 0),
	],
	// 跨域设置
	'cross' => [
		// 允许访问的域名
		'allowOrigin' => '*',
		// 允许访问的请求头
		'allowHeaders' => [
			'Access-Control-Allow-Headers' => 'Authorization, X-Platform, X-CSRF-TOKEN, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With',
			'Access-Control-Allow-Methods' => 'GET,POST,PATCH,PUT,DELETE,OPTIONS,DELETE',
			'Access-Control-Max-Age' => '1728000',
			'Access-Control-Allow-Credentials' => 'true'
		],
	]
];
