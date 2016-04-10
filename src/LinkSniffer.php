<?php

/*
WildPHP - a modular and easily extendable IRC bot written in PHP
Copyright (C) 2015 WildPHP

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace WildPHP\Modules\LinkSniffer;

use GuzzleHttp\Exception\GuzzleException;
use WildPHP\API\ShortenUri;
use WildPHP\BaseModule;
use WildPHP\CoreModules\Connection\IrcDataObject;
use WildPHP\Exceptions\ShortUriCreationFailedException;

class LinkSniffer extends BaseModule
{
	/**
	 * @var UriCache
	 */
	protected $uriCache = null;

	public function setup()
	{
		$this->getEventEmitter()->on('irc.data.in.privmsg', [$this, 'sniffLinks']);
		$this->uriCache = new UriCache();
		$this->uriCache->setupPruneTimer($this->getLoop());
	}

	/**
	 * @param IrcDataObject $message
	 * @return void
	 */
	public function sniffLinks(IrcDataObject $message)
	{
		$string = $message->getParams()['text'];
		$target = $message->getTargets()[0];

		try
		{
			$uri = SnifferHelper::extractUriFromString($string);
		}
		catch (NoUriFoundException $e)
		{
			return;
		}

		$cacheItem = $this->uriCache->getCacheItem($uri);

		if (!$cacheItem)
		{
			try
			{
				$shortUri = $this->createShortUri($uri);

				# We prefer is.gd for content type queries. This significantly reduces errors.
				$contentTypeUri = $shortUri;
				if ($contentTypeUri == 'No short url')
					$contentTypeUri = $uri;

				$content_type = SnifferHelper::getContentTypeFromUri($contentTypeUri);

				$title = '(not a web page, content type: ' . $content_type . ')';
				if ($content_type == 'text/html')
					$title = SnifferHelper::getTitleFromUri($uri);
			}

			catch (PageTitleDoesNotExistException $e)
			{
				$title = '(Page title not found or empty. Put that in your pipe and smoke it.)';
			}

			catch (ContentTypeNotFoundException $e)
			{
				return;
			}

			// Guzzle exceptions (such as connection timeouts) should be ignored.
			catch (GuzzleException $e)
			{
				$title = '(link was unresponsive: ' . $uri . ')';
			}

			if (!empty($title) && !empty($shortUri))
				$this->uriCache->addCacheItem($uri, $title, $shortUri);
		}
		else
		{
			$title = $cacheItem->getTitle();
			$shortUri = $cacheItem->getShortUri();
		}

		if (empty($shortUri) || empty($title))
			return;

		$connection = $this->getModule('Connection');
		$connection->write($connection->getGenerator()
			->ircNotice($target, '[' . $shortUri . '] ' . $title));
	}


	/**
	 * @param string $uri
	 * @return string
	 */
	public function createShortUri($uri)
	{
		try
		{
			$shortUri = ShortenUri::createShortLink($uri);
		}
		catch (ShortUriCreationFailedException $e)
		{
			$shortUri = 'No short url';
		}

		return $shortUri;
	}
}
