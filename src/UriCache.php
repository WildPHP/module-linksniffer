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


use React\EventLoop\LoopInterface;

class UriCache
{
	/**
	 * @var array<string,CacheItem>
	 */
	protected $items = [];

	public function setupPruneTimer(LoopInterface $loopInterface)
	{
		// Prune old cache items every 30 seconds.
		$loopInterface->addPeriodicTimer(30, array($this, 'pruneOldItems'));
	}

	/**
	 * @param string $uri
	 * @param string $title
	 * @param string $shortUri
	 * @return CacheItem
	 */
	public function addCacheItem($uri, $title, $shortUri = '')
	{
		if ($this->cacheItemExists($uri))
			return $this->getCacheItem($uri);

		$cacheItem = new CacheItem();

		// Expire in 1 hour.
		$cacheItem->setExpireTime(time() + 60*60);
		$cacheItem->setTitle($title);
		$cacheItem->setShortUri($shortUri);

		$this->items[$uri] = $cacheItem;
		return $cacheItem;
	}

	/**
	 * @param string $uri
	 * @return CacheItem|false
	 */
	public function getCacheItem($uri)
	{
		if (!$this->cacheItemExists($uri))
			return false;

		$cacheItem = $this->items[$uri];

		if ($cacheItem->isExpired())
			return false;

		return $cacheItem;
	}

	public function pruneOldItems()
	{
		foreach ($this->items as $uri => $cacheItem)
		{
			if ($cacheItem->isExpired())
				$this->removeCacheItem($uri);
		}
	}

	/**
	 * @param string $uri
	 * @return bool
	 */
	public function removeCacheItem($uri)
	{
		if (!$this->cacheItemExists($uri))
			return false;

		unset($this->items[$uri]);
		return true;
	}

	/**
	 * @param string $uri
	 * @return bool
	 */
	public function cacheItemExists($uri)
	{
		return array_key_exists($uri, $this->items);
	}

	/**
	 * @return int
	 */
	public function getItemCount()
	{
		return count($this->items);
	}
}