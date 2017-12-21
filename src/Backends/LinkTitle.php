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
use React\HttpClient\Request;
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
	public function request(string $url, int $depth = 0): PromiseInterface
	{
		$deferred = new Deferred();

		$request = $this->httpClient->request('GET', $url);
		
		$request->on('response', $this->handleResponseClosure($deferred, $url, $request, $depth));
		
		$request->on('error', [$deferred, 'reject']);
		$request->end();

		return $deferred->promise();
	}

	/**
	 * @param Deferred $deferred
	 * @param string $url
	 * @param Request $request
	 * @param int $depth
	 *
	 * @return \Closure
	 */
	public function handleResponseClosure(Deferred $deferred, string $url, Request $request, int $depth = 0): \Closure
	{
		return function (Response $response) use ($deferred, $url, $request, $depth)
		{
			if ($response->getCode() == 302)
			{
				$this->redirect($response, $deferred, $depth);

				return;
			}

			if ($response->getCode() != 200)
			{
				$deferred->reject(new BackendException('Response was not successful (status code != 200)'));
				return;
			}

			$contentType = $response->getHeaders()['Content-Type'] ?? '';
			if (empty($contentType) || explode(';', $contentType)[0] != 'text/html')
			{
				$deferred->reject(new BackendException('Response is not an HTML file; cannot parse'));
				return;
			}

			$buffer = '';
			$response->on('data', $this->handleIncomingDataChunkClosure($buffer, $deferred, $response, $url, $request));

			$response->on('end', function () use ($deferred)
			{
				$deferred->reject(new BackendException('No title parsed before end of page; no title found'));
			});
		};
	}

	/**
	 * @param Response $response
	 * @param Deferred $deferred
	 * @param int $depth
	 */
	public function redirect(Response $response, Deferred $deferred, int $depth = 0)
	{
		$location = $response->getHeaders()['Location'] ?? '';

		if (empty($location) || $depth > 3)
		{
			$deferred->reject(new BackendException('Too many redirects'));

			return;
		}

		$promise = $this->request($location, $depth + 1);

		$promise->then([$deferred, 'resolve'], [$deferred, 'reject']);
	}

	/**
	 * @param string $buffer
	 * @param Deferred $deferred
	 * @param Response $response
	 * @param string $url
	 * @param Request $request
	 *
	 * @return \Closure
	 */
	public function handleIncomingDataChunkClosure(string &$buffer, Deferred $deferred, Response $response, string $url, Request $request): \Closure
	{
		return function ($chunk) use (&$buffer, $deferred, $response, $url, $request)
		{
			$buffer .= $chunk;
			$title = $this->tryParseTitleFromBuffer($buffer);

			if ($title)
			{
				$deferred->resolve(new BackendResult($url, $title));
				$response->removeAllListeners('data');
			}
		};
	}

	/**
	 * @param string $buffer
	 *
	 * @return false|string
	 */
	public function tryParseTitleFromBuffer(string $buffer)
	{
		$buffer = trim(preg_replace('/\s+/', ' ', $buffer));

		if (preg_match("/\<title\>(.*?)\<\/title\>/i", $buffer, $matches) == false)
			return false;

		return html_entity_decode(trim($matches[1]), ENT_QUOTES);
	}

	/**
	 * @return string
	 */
	public static function getValidationRegex(): string
	{
		return static::$validationRegex;
	}
}