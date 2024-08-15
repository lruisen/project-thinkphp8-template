<?php

namespace app\traits;

use think\contract\Arrayable;
use think\exception\HttpResponseException;
use think\Paginator;
use think\Response;

trait ApiResponse
{
	/**
	 * 抛出异常形式返回数据
	 *
	 * @param string $message 错误信息
	 * @param int $code 业务状态码
	 * @param mixed $data 返回数据
	 * @param int $status Http状态码
	 * @return mixed
	 */
	protected function throw(string $message = 'error', int $code = 400, mixed $data = [], int $status = 200): mixed
	{
		$response = Response::create($this->formatData($data, $message, $code), 'json', $status);
		throw new HttpResponseException($response);
	}

	/**
	 * 返回成功响应。
	 *
	 * @param mixed|null $data
	 * @param string $message
	 * @param array $headers
	 * @param array $options
	 * @return Response
	 */
	protected function success(mixed $data = null, string $message = 'success', array $headers = [], array $options = []): Response
	{
		if ($data instanceof Arrayable || $data instanceof Paginator) {
			$data = $data->toArray();
		}

		isset($data['current_page']) && $data = array_merge(['page' => $data['current_page']], $data);

		return $this->formatArrayResponse(
			$data,
			$message,
			200,
			$headers,
			$options
		);
	}

	/**
	 * 返回错误响应。
	 * @param string $message
	 * @param mixed|array $errors
	 * @param int $code
	 * @return Response
	 */
	protected function error(string $message = 'error', array $errors = [], int $code = 400): Response
	{
		return $this->fail($message, $code, $errors);
	}

	/**
	 * 返回401未授权错误。
	 *
	 * @param string $message
	 * @param        $errors
	 * @return Response
	 */
	protected function errorAuth(string $message = '请登录', $errors = null): Response
	{
		return $this->fail($message, 401, $errors);
	}

	/**
	 * 返回失败响应。
	 *
	 * @param string $message
	 * @param int $code
	 * @param array|null $errors
	 * @param array $headers
	 * @param array $options
	 * @param int $status
	 * @return Response
	 */
	protected function fail(string $message = '', int $code = 400, ?array $errors = null, array $headers = [], array $options = [], int $status = 200): Response
	{
		return $this->response(
			$this->formatData($errors, $message, $code),
			$status,
			$headers,
			$options
		);
	}

	/**
	 * 格式化数组数据。
	 *
	 * @param mixed $data
	 * @param string $message
	 * @param int $code
	 * @param array $headers
	 * @param array $options
	 * @return Response
	 */
	protected function formatArrayResponse(mixed $data, string $message = '', int $code = 200, array $headers = [], array $options = []): Response
	{
		return $this->response(
			$this->formatData($data, $message, $code),
			200,
			$headers,
			$options
		);
	}

	/**
	 * 格式化返回数据结构。
	 *
	 * @param $data
	 * @param $message
	 * @param $code
	 * @return array
	 */
	protected function formatData($data, $message, $code): array
	{
		return compact('code', 'message', 'data');
	}

	/**
	 * 返回json响应。
	 *
	 * @param       $data
	 * @param int $code http status code
	 * @param array $hearers
	 * @param array $options
	 * @return Response
	 */
	protected function response($data, int $code = 200, array $hearers = [], array $options = []): Response
	{
		return json(...func_get_args());
	}
}