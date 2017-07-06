<?php
/**
 * Copyright 2017 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Modules\LinkSniffer;


use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

interface BackendInterface
{
	/**
	 * BackendInterface constructor.
	 *
	 * @param LoopInterface $loop
	 */
	public function __construct(LoopInterface $loop);

	/**
	 * @param string $url
	 *
	 * @return PromiseInterface
	 */
	public function request(string $url): PromiseInterface;

	/**
	 * @return string
	 */
	public static function getValidationRegex(): string;
}