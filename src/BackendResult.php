<?php
/**
 * Copyright 2017 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Modules\LinkSniffer;


class BackendResult
{
	/**
	 * @var string
	 */
	protected $url = '';

	/**
	 * @var string
	 */
	protected $result = '';

	/**
	 * BackendResult constructor.
	 *
	 * @param string $url
	 * @param string $result
	 */
	public function __construct(string $url, string $result)
	{
		$this->setUrl($url);
		$this->setResult($result);
	}

	/**
	 * @return string
	 */
	public function getUrl(): string
	{
		return $this->url;
	}

	/**
	 * @param string $url
	 */
	public function setUrl(string $url)
	{
		$this->url = $url;
	}

	/**
	 * @return string
	 */
	public function getResult(): string
	{
		return $this->result;
	}

	/**
	 * @param string $result
	 */
	public function setResult(string $result)
	{
		$this->result = $result;
	}
}