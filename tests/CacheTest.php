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

namespace WPHPTests;


use WildPHP\Modules\LinkSniffer\CacheItem;
use WildPHP\Modules\LinkSniffer\UriCache;

class CacheTest extends \PHPUnit_Framework_TestCase
{
	public function testAddEntry()
	{
		$uriCache = new UriCache();

		$uriCache->addCacheItem('googleUri', 'Google');

		$checkObject = new CacheItem();
		$checkObject->setTitle('Google');

		$retrieved = $uriCache->getCacheItem('googleUri');

		$this->assertSame($checkObject, $retrieved);
	}

	public function testRemoveItem()
	{
		$uriCache = new UriCache();

		$uriCache->addCacheItem('googleUri', 'Google');
		$count = $uriCache->getItemCount();

		$this->assertEquals(1, $count);

		$uriCache->removeCacheItem('googleUri');
		$count = $uriCache->getItemCount();

		$this->assertEquals(0, $count);
	}
}
