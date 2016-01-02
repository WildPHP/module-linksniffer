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


class CacheItem
{
	/**
	 * @var string
	 */
	protected $shortUri = '';

	/**
	 * @var string
	 */
	protected $title = '';

	/**
	 * @var int
	 */
	protected $expires;

	/**
	 * @return mixed
	 */
	public function isExpired()
	{
		return $this->expires < time();
	}

	/**
	 * @param int $seconds
	 */
	public function setExpireTime($seconds)
	{
		$this->expires = $seconds;
	}

	/**
	 * @return string
	 */
	public function getShortUri()
	{
		return $this->shortUri;
	}

	/**
	 * @param string $shortUri
	 */
	public function setShortUri($shortUri)
	{
		$this->shortUri = $shortUri;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}
}