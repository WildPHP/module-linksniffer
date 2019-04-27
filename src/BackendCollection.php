<?php
/**
 * Copyright 2019 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Modules\LinkSniffer;

use RuntimeException;
use ValidationClosures\Types;
use Yoshi2889\Collections\Collection;

class BackendCollection extends Collection
{
    /**
     * BackendCollection constructor.
     *
     * @param array $initialValues
     */
    public function __construct(array $initialValues = [])
    {
        parent::__construct(Types::instanceof(BackendInterface::class), $initialValues);
    }

    /**
     * @param string $url
     *
     * @return BackendInterface
     * @throws RuntimeException
     */
    public function findBackendForUrl(string $url): BackendInterface
    {
        /** @var BackendInterface $backend */
        foreach ($this->getArrayCopy() as $backend) {
            if (preg_match($backend::getValidationRegex(), $url)) {
                return $backend;
            }
        }

        throw new RuntimeException('No valid backend found...');
    }
}
