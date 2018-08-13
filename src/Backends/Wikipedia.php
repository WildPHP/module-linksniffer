<?php
/**
 * Copyright 2018 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Modules\LinkSniffer\Backends;

use React\HttpClient\Client;
use React\HttpClient\Request;
use React\HttpClient\Response;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use React\Promise\Deferred;
use WildPHP\Modules\LinkSniffer\BackendException;
use WildPHP\Modules\LinkSniffer\BackendInterface;
use WildPHP\Modules\LinkSniffer\BackendResult;

class Wikipedia implements BackendInterface
{
    /**
     * @var string
     */
    public static $validationRegex = '/^http(?:s)?\:\/\/([a-z]{2})\.(?:m\.)?wikipedia.org\/wiki\/(\S+)$/i';

    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * BackendInterface constructor.
     *
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        $this->httpClient = new Client($loop);
    }

    /**
     * @param string $url
     *
     * @return PromiseInterface
     */
    public function request(string $url): PromiseInterface
    {
        preg_match(self::$validationRegex, $url, $matches);

        $language = $matches[1];
        $article = $matches[2];

        $apiUrl = 'https://' . $language . '.wikipedia.org/w/api.php?' .
            'action=opensearch' .
            '&namespace=0' .
            '&format=json' .
            '&redirect=resolve' .
            '&search=' . $article;

        $deferred = new Deferred();

        $request = $this->httpClient->request('GET', $apiUrl);

        $request->on('response', $this->handleResponseClosure($deferred, $url, $request));

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
    public function handleResponseClosure(Deferred $deferred, string $url, Request $request): \Closure
    {
        return function (Response $response) use ($deferred, $url, $request)
        {
            if ($response->getCode() != 200)
            {
                $deferred->reject(new BackendException('Response was not successful (status code != 200)'));
                return;
            }

            $contentType = $response->getHeaders()['Content-Type'] ?? '';
            if (empty($contentType) || explode(';', $contentType)[0] != 'application/json')
            {
                $deferred->reject(new BackendException('Response is not JSON; cannot parse'));
                return;
            }

            $buffer = '';
            $response->on('data', function ($chunk) use (&$buffer)
            {
                $buffer .= $chunk;
            });

            $response->on('end', function () use ($deferred, $url, &$buffer)
            {
                $result = json_decode($buffer);
                if (!$result) {
                    $deferred->resolve(new BackendResult($url, '(could not get article information)'));
                    return;
                }

                $results = [];
                // Because MediaWiki returns results in this awkward way,
                // we first process them into 'sane' results.
                foreach ($result[1] as $key => $title)
                {
                    $results[$key]['title'] = $title;
                }
                foreach ($result[2] as $key => $description)
                {
                    $results[$key]['description'] = $description;
                }
                foreach ($result[3] as $key => $uri)
                {
                    $results[$key]['uri'] = $uri;
                }

                if (empty($results)) {
                    $deferred->resolve(new BackendResult($url, '(could not get article information)'));
                    return;
                }

                $result = array_shift($results);
                $deferred->resolve(new BackendResult($result['uri'], (!empty($result['description']) ? $result['description'] : 'No description given.')));
            });
        };
    }

    /**
     * @return string
     */
    public static function getValidationRegex(): string
    {
        return self::$validationRegex;
    }
}