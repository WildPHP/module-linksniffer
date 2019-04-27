<?php

/**
 * Copyright 2019 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Modules\LinkSniffer;

use Evenement\EventEmitterInterface;
use Exception;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use RuntimeException;
use WildPHP\Core\Configuration\Configuration;
use WildPHP\Core\Events\IncomingIrcMessageEvent;
use WildPHP\Core\Queue\IrcMessageQueue;
use WildPHP\Core\Queue\IrcMessageQueueItem;
use WildPHP\Messages\Privmsg;
use WildPHP\Modules\LinkSniffer\Backends\LinkTitle;
use WildPHP\Modules\LinkSniffer\Backends\Wikipedia;

class LinkSniffer
{
    /**
     * @var BackendCollection
     */
    protected $backendCollection;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var IrcMessageQueue
     */
    private $queue;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * LinkSniffer constructor.
     *
     * @param EventEmitterInterface $eventEmitter
     * @param LoopInterface $loop
     * @param Configuration $configuration
     * @param IrcMessageQueue $queue
     * @param LoggerInterface $logger
     */
    public function __construct(
        EventEmitterInterface $eventEmitter,
        LoopInterface $loop,
        Configuration $configuration,
        IrcMessageQueue $queue,
        LoggerInterface $logger
    ) {
        $eventEmitter->on('irc.msg.in.privmsg', [$this, 'sniffLinks']);

        $backends = [
            new Wikipedia($loop),
            new LinkTitle($loop)
        ];

        $this->backendCollection = new BackendCollection($backends);
        $this->configuration = $configuration;
        $this->queue = $queue;
        $this->logger = $logger;
    }

    /**
     * @param IncomingIrcMessageEvent $event
     * @throws Exception
     */
    public function sniffLinks(IncomingIrcMessageEvent $event): void
    {
        /** @var Privmsg $incomingIrcMessage */
        $incomingIrcMessage = $event->getIncomingMessage();
        $channel = $incomingIrcMessage->getChannel();

        if ($this->configuration->offsetExists('disablelinksniffer')) {
            $blockedChannels = $this->configuration['disablelinksniffer'];
        }

        if (!empty($blockedChannels) && is_array($blockedChannels) && in_array($channel, $blockedChannels, true)) {
            return;
        }

        $message = $incomingIrcMessage->getMessage();

        $uri = self::extractUriFromString($message);

        if ($uri === false) {
            return;
        }

        $backend = $this->backendCollection->findBackendForUrl($uri);
        $promise = $backend->request($uri);

        $promise->then(function (BackendResult $result) use ($incomingIrcMessage, $channel) {
            $msg = '[' . $incomingIrcMessage->getNickname() . '] ' . $result->getResult();
            $privmsg = new Privmsg($channel, $msg);
            $privmsg->setTags(['relay_ignore']);
            $this->queue->enqueue(new IrcMessageQueueItem($privmsg));
        }, function (RuntimeException $exception) use ($uri) {
            $this->logger->debug('Unsuccessful backend request', [
                'uri' => $uri,
                'exception' => $exception->getMessage()
            ]);
        });
    }

    /**
     * @param $string
     *
     * @return false|string
     */
    public static function extractUriFromString($string)
    {
        if (empty($string)) {
            return false;
        }

        if (strpos($string, '!nosniff') !== false) {
            return false;
        }

        $hasMatches = preg_match('/https?\:\/\/[A-Za-z0-9\-\/._~:?#@!$&\'()*+,;=%]+/i', $string, $matches);

        if (!$hasMatches || empty($matches)) {
            return false;
        }

        $possibleUri = $matches[0];

        if (filter_var($possibleUri, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return $possibleUri;
    }
}
