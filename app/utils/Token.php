<?php

namespace app\utils;

use DateTime;
use Exception;

class Token
{
	/**
	 * @var array|mixed
	 */
	protected array $config = [];

	/**
	 * @var string
	 */
	protected string $scene = 'api';

	/**
	 * @var Token|null
	 */
	protected static ?self $instance = null;

	protected ?\Redis $redis;

	/**
	 * Token constructor.
	 * @param array $options
	 */
	public function __construct(array $options = [])
	{
		$this->config = config('cookie.token', []);
		if (! empty($options)) {
			$this->config = array_merge($this->config, $options);
		}

		$this->redis = (new Redis([
			'select' => $this->config['redis_db']
		]))->getHandler();
	}

	/**
	 * @description 单例模式
	 * @param array $options
	 * @return static
	 */
	public static function getInstance(array $options = []): static
	{
		if (is_null(self::$instance)) {
			self::$instance = new static($options);
		}

		return self::$instance;
	}

	/**
	 * @description 设置场景
	 * @param string $scene
	 * @return Token
	 */
	public function setScene(string $scene): static
	{
		$this->scene = $scene;
		return $this;
	}


	/**
	 * 生成 Token
	 * @param array $data 存储数据
	 * @param int $expire 过期时间
	 * @return array
	 * @throws Exception
	 */
	public function create(array $data = [], int $expire = 0): array
	{
		$token = Ulid::generate(true)->getRandomness();

		$expire = empty($expire) ? 86400 * 3 : $expire;
		$this->set($token, $data, $expire);
		return compact('token', 'expire');
	}

	/**
	 * 获取加密后的Token
	 * @param string $token Token标识
	 * @return string
	 */
	public function getEncryptedToken(string $token): string
	{
		return ($this->config['prefix'] ?? '') . hash_hmac($this->config['hashalgo'] ?? 'ripemd160', $token, $this->config['key'] ?? 'bares');
	}

	/**
	 * 获取会员的key
	 * @param        $pk
	 * @return string
	 */
	public function getUserKey($pk): string
	{
		return sprintf(
			'token:%s_%s_%s',
			$this->config['prefix'] ?? '',
			$this->scene,
			$pk
		);
	}

	/**
	 * 获取Token内的信息
	 * @param string $token
	 * @return array|false
	 */
	public function get(string $token): bool|array
	{
		$key = sprintf(
			'token:%s_%s',
			$this->getEncryptedToken($token),
			$this->scene
		);
		$data = $this->redis->get($key);
		if (empty($data)) {
			return false;
		}

		$expire = $this->redis->ttl($key);
		$expire = $expire < 0 ? 7 * 86400 : $expire;
		$expire_time = time() + $expire;

		return [
			'token' => $token,
			'data' => unserialize($data),
			'expire_time' => $expire_time,
		];
	}

	/**
	 * 存储Token
	 * @param string $token
	 * @param mixed $data
	 * @param mixed $expire
	 * @param string $pk
	 * @return bool|\Redis
	 */
	public function set(string $token, mixed $data, mixed $expire = 0, string $pk = 'id'): bool|\Redis
	{
		if ($expire instanceof DateTime) {
			$expire = $expire->getTimestamp() - time();
		}

		$key = sprintf(
			'token:%s_%s',
			$this->getEncryptedToken($token),
			$this->scene
		);

		// 是否允许同一账号多端登录
		if (empty($this->config['multi_login'])) {
			$this->clear($data[$pk]);
		}

		if ($expire) {
			$result = $this->redis->setex($key, $expire, serialize($data));
		} else {
			$result = $this->redis->set($key, serialize($data));
		}

		if ($result) {
			$ck = $this->getUserKey($data[$pk]);
			$this->redis->sAdd($ck, $key);
			$this->redis->expire($ck, $expire);
		}

		return $result;
	}

	/**
	 * 判断Token是否可用
	 * @param string $token
	 * @param        $user_id
	 * @param string $pk
	 * @return bool
	 */
	public function check(string $token, $user_id, string $pk = 'id'): bool
	{
		$data = $this->get($token);
		return $data && $data[$pk] == $user_id;
	}

	/**
	 * 删除Token
	 * @param string $token
	 * @param string $pk
	 * @return bool
	 */
	public function delete(string $token, string $pk = 'id'): bool
	{
		$data = $this->get($token);
		if (! empty($data)) {
			$data = $data['data'];
			$key = sprintf(
				'token:%s_%s',
				$this->getEncryptedToken($token),
				$this->scene
			);

			$this->redis->del($key);
			$this->redis->sRem($this->getUserKey($data[$pk]), $key);
		}

		return true;
	}

	/**
	 * 删除指定用户的所有Token
	 * @param  $user_id
	 * @return  boolean
	 */
	public function clear($user_id): bool
	{
		$key = $this->getUserKey($user_id);
		$keys = $this->redis->zRange($key, 0, -1);

		$this->redis->del($key);
		$this->redis->del($keys);
		return true;
	}
}