<?php

namespace app\providers\token\driver;

use app\providers\token\Driver;
use Throwable;

/**
 * @see Driver
 */
class Redis extends Driver
{
	/**
	 * 默认配置
	 * @var array
	 */
	protected array $options = [];

	/**
	 * Token 过期后缓存继续保留的时间(s)
	 */
	protected int $expiredHold = 86400;

	/**
	 * 构造函数
	 * @access public
	 * @param array $options 参数
	 * @throws Throwable
	 */
	public function __construct(array $options = [])
	{
		$this->options = array_merge($this->options, $options);
		$redis = new \app\utils\Redis($this->options);
		$this->handler = $redis->getHandler();
	}

	/**
	 * @throws Throwable
	 */
	public function set(string $token, string $type, int $userId, int $expire = null): bool
	{
		if (is_null($expire)) {
			$expire = $this->options['expire'];
		}
		$expireTime = $expire !== 0 ? time() + $expire : 0;
		$token = $this->getEncryptedToken($token);
		$tokenInfo = [
			'token' => $token,
			'type' => $type,
			'user_id' => $userId,
			'create_time' => time(),
			'expire_time' => $expireTime,
		];
		$tokenInfo = json_encode($tokenInfo, JSON_UNESCAPED_UNICODE);
		if ($expire) {
			$expire += $this->expiredHold;
			$result = $this->handler->setex($token, $expire, $tokenInfo);
		} else {
			$result = $this->handler->set($token, $tokenInfo);
		}
		$this->handler->sAdd($this->getUserKey($type, $userId), $token);
		return $result;
	}

	/**
	 * @throws Throwable
	 */
	public function get(string $token): array
	{
		$key = $this->getEncryptedToken($token);
		$data = $this->handler->get($key);
		if (is_null($data) || false === $data) {
			return [];
		}
		$data = json_decode($data, true);

		$data['token'] = $token; // 返回未加密的token给客户端使用
		$data['expires_in'] = $this->getExpiredIn($data['expire_time'] ?? 0); // 过期时间
		return $data;
	}

	/**
	 * @throws Throwable
	 */
	public function check(string $token, string $type, int $userId): bool
	{
		$data = $this->get($token);
		if (! $data || ($data['expire_time'] && $data['expire_time'] <= time())) return false;
		return $data['type'] == $type && $data['user_id'] == $userId;
	}

	/**
	 * @throws Throwable
	 */
	public function delete(string $token): bool
	{
		$data = $this->get($token);
		if ($data) {
			$key = $this->getEncryptedToken($token);
			$this->handler->del($key);
			$this->handler->sRem($this->getUserKey($data['type'], $data['user_id']), $key);
		}
		return true;
	}

	/**
	 * @throws Throwable
	 */
	public function clear(string $type, int $userId): bool
	{
		$userKey = $this->getUserKey($type, $userId);
		$keys = $this->handler->sMembers($userKey);
		$this->handler->del($userKey);
		$this->handler->del($keys);
		return true;
	}

	/**
	 * 获取会员的key
	 * @param $type
	 * @param $userId
	 * @return string
	 */
	protected function getUserKey($type, $userId): string
	{
		return $this->options['prefix'] . $type . '-' . $userId;
	}
}