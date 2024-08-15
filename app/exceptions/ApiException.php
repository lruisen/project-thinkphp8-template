<?php

namespace app\exceptions;

use RuntimeException;
use Throwable;

class ApiException extends RuntimeException
{
	public function __construct($message = "", int $code = 0, ?Throwable $previous = null)
	{
		if (is_array($message)) {
			$errInfo = $message;
			$message = $errInfo[1] ?? '未知错误';
			if ($code === 0) {
				$code = $errInfo[0] ?? 400;
			}
		}
		
		parent::__construct($message, $code, $previous);
	}
}