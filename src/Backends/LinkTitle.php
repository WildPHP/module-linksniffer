<?php
/**
 * Copyright 2017 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Modules\LinkSniffer\Backends;


use React\EventLoop\LoopInterface;
use React\HttpClient\Client;
use React\HttpClient\Response;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use WildPHP\Modules\LinkSniffer\BackendException;
use WildPHP\Modules\LinkSniffer\BackendInterface;
use WildPHP\Modules\LinkSniffer\BackendResult;

class LinkTitle implements BackendInterface
{
	/**
	 * @var string
	 */
	public static $validationRegex = '/^\S+$/i';

	/**
	 * @var Client
	 */
	protected $httpClient;

	/**
	 * LinkTitle constructor.
	 *
	 * @param LoopInterface $loop
	 */
	public function __construct(LoopInterface $loop)
	{
		$this->httpClient = new Client($loop);
	}

	/**
	 * @inheritdoc
	 */
	public function request(string $url): PromiseInterface
	{
		$deferred = new Deferred();

		$request = $this->httpClient->request('GET', $url);
		$request->on('response', function (Response $response) use ($deferred, $url, $request)
		{
			if ($response->getCode() == 302)
			{
				$location = $response->getHeaders()['Location'] ?? '';
				$deferred->resolve(new BackendResult($location, 'Redirect (new location: ' . $location . ')'));
				return;
			}

			if ($response->getCode() != 200)
			{
				$deferred->reject(new BackendException('Response was not successful (status code != 200 or too many redirects)'));
				return;
			}

			$contentType = $response->getHeaders()['Content-Type'] ?? '';
			if (empty($contentType) || explode(';', $contentType)[0] != 'text/html')
			{
				$deferred->reject(new BackendException('Response is not an HTML file; cannot parse'));
				return;
			}

			$buffer = '';
			$response->on('data', function ($chunk) use (&$buffer, $deferred, $response, $url, $request)
			{
				$buffer .= $chunk;
				$title = $this->tryParseTitleFromBuffer($buffer);

				if ($title)
				{
					$deferred->resolve(new BackendResult($url, $title));
					$response->removeAllListeners('data');
				}
			});

			$response->on('end', function () use ($deferred)
			{
				$deferred->reject(new BackendException('No link parsed before end of page; no link found'));
			});
		});
		$request->on('error', function (\Exception $e) use ($deferred)
		{
			$deferred->reject($e);
		});
		$request->end();

		return $deferred->promise();
	}

	/**
	 * @param string $buffer
	 *
	 * @return false|string
	 */
	public function tryParseTitleFromBuffer(string $buffer)
	{
		$buffer = trim(preg_replace('/\s+/', ' ', $buffer));

		if (preg_match("/\<title\>(.*)\<\/title\>/i", $buffer, $matches) == false)
			return false;

		return trim($matches[1]);
	}

	/**
	 * @return string
	 */
	public static function getValidationRegex(): string
	{
		return static::$validationRegex;
	}
}