<?php
/**
 * Copyright 2017 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

use PHPUnit\Framework\TestCase;
use WildPHP\Modules\LinkSniffer\Backends\LinkTitle;

class LinkTitleBackendTest extends TestCase
{
	public function initializeLinkTitleBackend()
	{
		$loop = \React\EventLoop\Factory::create();
		return new LinkTitle($loop);
	}

	public function testLinkTitle()
	{
		$expected = new \WildPHP\Modules\LinkSniffer\BackendResult('https://www.google.nl/', 'Google');

		$linkTitleBackend = $this->initializeLinkTitleBackend();
		$promise = $linkTitleBackend->request('https://www.google.nl/');

		$promise->then(function (\WildPHP\Modules\LinkSniffer\BackendResult $result) use ($expected)
		{
			self::assertEquals($expected, $result);
		});
	}
}
