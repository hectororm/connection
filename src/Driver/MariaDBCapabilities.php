<?php
/*
 * This file is part of Hector ORM.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2025 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Hector\Connection\Driver;

class MariaDBCapabilities implements DriverCapabilities
{
    public function __construct(private DriverInfo $driverInfo)
    {
    }

    /**
     * @inheritDoc
     */
    public function hasLock(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function hasLockAndSkip(): bool
    {
        return version_compare($this->driverInfo->getVersion(), '10.6.0', '>=');
    }

    /**
     * @inheritDoc
     */
    public function hasWindowFunctions(): bool
    {
        return version_compare($this->driverInfo->getVersion(), '10.2.0', '>=');
    }

    /**
     * @inheritDoc
     */
    public function hasJson(): bool
    {
        return version_compare($this->driverInfo->getVersion(), '10.2.0', '>=');
    }

    /**
     * @inheritDoc
     */
    public function hasStrictMode(): bool
    {
        return true;
    }
}
