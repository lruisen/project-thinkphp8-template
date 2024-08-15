<?php

namespace app\services;

use app\facade\Redis;
use Closure;
use RedisException;

/**
 * Class LockService
 * @package app\services
 * @description 加锁排队一个一个执行任务, 防止并发
 */
class LockService
{
	protected ?\Redis $redis;

	protected static ?self $instance = null;

	protected function __construct()
	{
		$this->redis = Redis::getHandler();
	}

	/**
	 * 获取类实例。
	 * @param bool $force
	 * @return static
	 */
	public static function instance(bool $force = false): static
	{
		if (! (self::$instance instanceof static) || $force) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * @description 执行加锁逻辑
	 * @param string $key
	 * @param Closure $fn
	 * @param int $ex
	 * @return mixed
	 * @throws RedisException
	 */
	public function exec(string $key, Closure $fn, int $ex = 6): mixed
	{
		try {
			$isLock = false;
			while (! $isLock) {
				$isLock = $this->tryLock($key, 1, $ex);
				! $isLock && usleep(200000);
			}

			return call_user_func($fn);
		} finally {
			$this->unlock($key);
		}
	}

	/**
	 * @description 加锁
	 * @param        $key
	 * @param string $value
	 * @param int $ex
	 * @return bool
	 */
	public function tryLock($key, string $value = '1', int $ex = 6): bool
	{
		$lockKey = sprintf('lock:%s', $key);
		return $this->redis->set($lockKey, $value, ['NX', 'EX' => $ex]);
	}

	/**
	 * @description 解锁
	 * @param string $key
	 * @param string $value
	 * @return bool
	 */
	public function unlock(string $key, string $value = '1'): bool
	{
		$script = <<< EOF
if (redis.call("get", "lock:" .. KEYS[1]) == ARGV[1]) then
    return redis.call("del", "lock:" .. KEYS[1])
else
    return 0
end
EOF;
		return $this->redis->eval($script, [$key, $value], 1) > 0;
	}
}