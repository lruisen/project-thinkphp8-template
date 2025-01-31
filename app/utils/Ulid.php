<?php

namespace app\utils;

use Exception;
use think\exception\ValidateException;

class Ulid
{
	public const ENCODING_CHARS = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
	public const ENCODING_LENGTH = 32;

	public const TIME_MAX = 281474976710655;
	public const TIME_LENGTH = 10;

	public const RANDOM_LENGTH = 32;

	/**
	 * @var int
	 */
	private static $lastGenTime = 0;

	/**
	 * @var array
	 */
	private static $lastRandChars = [];

	/**
	 * @var string
	 */
	private $time;

	/**
	 * @var string
	 */
	private $randomness;

	/**
	 * @var bool
	 */
	private $lowercase;

	private function __construct(string $time, string $randomness, bool $lowercase = false)
	{
		$this->time = $time;
		$this->randomness = $randomness;
		$this->lowercase = $lowercase;
	}

	public static function fromString(string $value, bool $lowercase = false): self
	{
		if (strlen($value) !== static::TIME_LENGTH + static::RANDOM_LENGTH) {
			throw new ValidateException('Invalid ULID string (wrong length): ' . $value);
		}

		// Convert to uppercase for regex. Doesn't matter for output later, that is determined by $lowercase.
		$value = strtoupper($value);

		if (! preg_match(sprintf('!^[%s]{%d}$!', static::ENCODING_CHARS, static::TIME_LENGTH + static::RANDOM_LENGTH), $value)) {
			throw new ValidateException('Invalid ULID string (wrong characters): ' . $value);
		}

		return new static(substr($value, 0, static::TIME_LENGTH), substr($value, static::TIME_LENGTH, static::RANDOM_LENGTH), $lowercase);
	}

	/**
	 * Create a ULID using the given timestamp.
	 * @param int $milliseconds Number of milliseconds since the UNIX epoch for which to generate this ULID.
	 * @param bool $lowercase True to output lowercase ULIDs.
	 * @return $this Returns a ULID object for the given microsecond time.
	 * @throws Exception
	 */
	public static function fromTimestamp(int $milliseconds, bool $lowercase = false): self
	{
		$duplicateTime = $milliseconds === static::$lastGenTime;

		static::$lastGenTime = $milliseconds;

		$timeChars = '';
		$randChars = '';

		$encodingChars = static::ENCODING_CHARS;

		for ($i = static::TIME_LENGTH - 1; $i >= 0; $i--) {
			$mod = $milliseconds % static::ENCODING_LENGTH;
			$timeChars = $encodingChars[$mod] . $timeChars;
			$milliseconds = ($milliseconds - $mod) / static::ENCODING_LENGTH;
		}

		if (! $duplicateTime) {
			for ($i = 0; $i < static::RANDOM_LENGTH; $i++) {
				static::$lastRandChars[$i] = random_int(0, 31);
			}
		} else {
			// If the timestamp hasn't changed since last push,
			// use the same random number, except incremented by 1.
			for ($i = static::RANDOM_LENGTH - 1; $i >= 0 && static::$lastRandChars[$i] === 31; $i--) {
				static::$lastRandChars[$i] = 0;
			}

			static::$lastRandChars[$i]++;
		}

		for ($i = 0; $i < static::RANDOM_LENGTH; $i++) {
			$randChars .= $encodingChars[static::$lastRandChars[$i]];
		}

		// 将$randChars 转换为uuid格式
		$randChars = substr($randChars, 0, 8) . '-' .
			substr($randChars, 8, 4) . '-' .
			substr($randChars, 12, 4) . '-' .
			substr($randChars, 16, 4) . '-' .
			substr($randChars, 20, 12);

		return new static($timeChars, $randChars, $lowercase);
	}

	/**
	 * @throws Exception
	 */
	public static function generate(bool $lowercase = false): self
	{
		$now = (int)(microtime(true) * 1000);

		return static::fromTimestamp($now, $lowercase);
	}

	public function getTime(): string
	{
		return $this->time;
	}

	public function getRandomness(): string
	{
		return $this->randomness;
	}

	public function isLowercase(): bool
	{
		return $this->lowercase;
	}

	public function toTimestamp(): int
	{
		return $this->decodeTime($this->time);
	}

	public function __toString(): string
	{
		return ($value = $this->time . $this->randomness) && $this->lowercase ? strtolower($value) : strtoupper($value);
	}

	private function decodeTime(string $time): int
	{
		$timeChars = str_split(strrev($time));
		$carry = 0;

		foreach ($timeChars as $index => $char) {
			if (($encodingIndex = strripos(static::ENCODING_CHARS, $char)) === false) {
				throw new ValidateException('Invalid ULID character: ' . $char);
			}

			$carry += ($encodingIndex * pow(static::ENCODING_LENGTH, $index));
		}

		if ($carry > static::TIME_MAX) {
			throw new ValidateException('Invalid ULID string: timestamp too large');
		}

		return $carry;
	}
}