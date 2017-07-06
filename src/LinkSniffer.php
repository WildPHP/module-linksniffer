<?php

/**
 * Copyright 2017 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Modules\LinkSniffer;

use WildPHP\Core\ComponentContainer;
use WildPHP\Core\Configuration\Configuration;
use WildPHP\Core\Connection\IRCMessages\PRIVMSG;
use WildPHP\Core\Connection\Queue;
use WildPHP\Core\ContainerTrait;
use WildPHP\Core\EventEmitter;
use WildPHP\Core\Modules\BaseModule;
use WildPHP\Modules\LinkSniffer\Backends\LinkTitle;

class LinkSniffer extends BaseModule
{
	use ContainerTrait;

	/**
	 * @var BackendCollection
	 */
	protected $backendCollection;

	public function __construct(ComponentContainer $container)
	{
		EventEmitter::fromContainer($container)
			->on('irc.line.in.privmsg', [$this, 'sniffLinks']);

		$this->setContainer($container);

		$backends = [
			new LinkTitle($container->getLoop())
		];

		$this->backendCollection = new BackendCollection($backends);
	}

	/**
	 * @param PRIVMSG $incomingIrcMessage
	 * @param Queue $queue
	 */
	public function sniffLinks(PRIVMSG $incomingIrcMessage, Queue $queue)
	{
		$channel = $incomingIrcMessage->getChannel();

		if (Configuration::fromContainer($this->getContainer())->offsetExists('disablelinksniffer'))
			$blockedChannels = Configuration::fromContainer($this->getContainer())['disablelinksniffer'];

		if (!empty($blockedChannels) && is_array($blockedChannels) && in_array($channel, $blockedChannels))
			return;

		$message = $incomingIrcMessage->getMessage();

		$uri = self::extractUriFromString($message);

		if ($uri == false)
			return;

		$backend = $this->backendCollection->findBackendForUrl($uri);
		$promise = $backend->request($uri);

		$promise->then(function (BackendResult $result) use ($incomingIrcMessage, $queue, $channel)
		{
			$msg = '[' . $incomingIrcMessage->getNickname() . '] ' . $result->getResult();
			$privmsg = new PRIVMSG($channel, $msg);
			$privmsg->setMessageParameters(['relay_ignore']);
			$queue->insertMessage($privmsg);
		});
	}

	/**
	 * @param $string
	 *
	 * @return false|string
	 */
	public static function extractUriFromString($string)
	{
		if (empty($string))
			return false;

		elseif (strpos($string, '!nosniff') !== false)
			return false;

		$hasMatches = preg_match('/https?\:\/\/[A-Za-z0-9\-\/._~:?#@!$&\'()*+,;=%]+/i', $string, $matches);

		if (!$hasMatches || empty($matches))
			return false;

		$possibleUri = $matches[0];

		if (filter_var($possibleUri, FILTER_VALIDATE_URL) === false)
			return false;

		return $possibleUri;
	}
}