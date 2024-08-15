<?php

namespace app\exceptions;

use app\traits\ApiResponse;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Log;
use think\Response;
use Throwable;

class ExceptionHandle extends Handle
{
	use ApiResponse;

	/**
	 * 不需要记录信息（日志）的异常类列表
	 * @var array
	 */
	protected $ignoreReport = [
		ApiException::class,
		HttpException::class,
		HttpResponseException::class,
		ModelNotFoundException::class,
		DataNotFoundException::class,
		ValidateException::class,
	];

	/**
	 * 记录异常信息（包括日志或者其它方式记录）
	 * @param Throwable $exception
	 * @return void
	 */
	public function report(Throwable $exception): void
	{
		if (! $this->isIgnoreReport($exception)) {
			$data = [
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
				'message' => $this->getMessage($exception),
				'code' => $this->getCode($exception),
			];

			$log = [
				request()->ip(),                                                                      //客户ip
				ceil(msec_time() - (request()->time(true) * 1000)),                               //耗时（毫秒）
				request()->method(true),                                                       //请求类型
				request()->baseUrl(),                                                                 //路由
				request()->header('token', ''),                                                     //用户token
				json_encode(request()->param(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),//请求参数
				json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),             //报错数据
			];

			$tpl = <<<str
\r\n=======================================================================
请求IP ：%s
请求耗时：%sms
请求类型：%s
请求API：%s
用户Token：%s
请求参数：%s
报错信息：%s
========================================================================
str;

			Log::write(sprintf($tpl, ...$log), "error");
		}
	}

	public function render($request, Throwable $e): Response
	{
		$errors = env('APP_DEBUG') ? [
			'code' => $e->getCode(),
			'message' => $e->getMessage(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'trace' => $e->getTrace(),
			'previous' => $e->getPrevious(),
		] : [];

		// 自定义异常处理机制
		if ($e instanceof DbException) {
			return $this->error('数据获取失败', $errors);
		} else if ($e instanceof ValidateException || $e instanceof ApiException) {
			return $this->error($e->getMessage(), $errors);
		}

		return $this->error('请求异常！！！', $errors);
	}
}