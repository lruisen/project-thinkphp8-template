<?php

namespace app\utils;

use BadFunctionCallException;
use Throwable;

class Redis
{
	protected ?\Redis $handler = null;

	protected array $options = [];

	public function __construct(array $options = [])
	{
		if (! extension_loaded('redis')) {
			throw new BadFunctionCallException('not support: redis');
		}

		$this->options = [
			'host' => $options['host'] ?? config('cache.stores.redis.host', '127.0.0.1'),
			'port' => $options['port'] ?? config('cache.stores.redis.port', 6379),
			'password' => $options['password'] ?? config('cache.stores.redis.password', ''),
			'select' => $options['select'] ?? config('cache.stores.redis.select', 0),
			'timeout' => $options['timeout'] ?? config('cache.stores.redis.timeout', 0),
			'expire' => $options['expire'] ?? config('cache.stores.redis.expire', 0),
			'persistent' => $options['persistent'] ?? config('cache.stores.redis.persistent', false),
		];

		try {
			$this->handler = new \Redis;
			if ($this->options['persistent']) {
				$this->handler->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);
			} else {
				$this->handler->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
			}

			if ('' != $this->options['password']) {
				$this->handler->auth($this->options['password']);
			}

			$this->handler->select($this->options['select']);
		} catch (Throwable $e) {
			throw_exception($e->getMessage());
		}
	}

	/**
	 * 获取redis实例
	 * @return \Redis|null
	 */
	public function getHandler(): ?\Redis
	{
		return $this->handler;
	}

	public function __call($name, $arguments)
	{
		return call_user_func_array([$this->handler, $name], $arguments);
	}
}