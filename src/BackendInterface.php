<?php
/**
 * Copyright 2017 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Modules\LinkSniffer;


use React\Promise\PromiseInterface;
use WildPHP\Core\ComponentContainer;

interface BackendInterface
{
	/**
	 * BackendInterface constructor.
	 *
	 * @param ComponentContainer $container
	 */
	public function __construct(ComponentContainer $container);

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