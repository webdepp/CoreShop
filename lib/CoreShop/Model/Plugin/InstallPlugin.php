<?php
/**
 * CoreShop.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2016 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Model\Plugin;

use CoreShop\Plugin\Install;

/**
 * Interface InstallPlugin
 * @package CoreShop\Model\Plugin
 */
interface InstallPlugin
{
    /**
     * Install Plugin.
     *
     * @param Install $installer
     */
    public function install(Install $installer);

    /**
     * Uninstall Plugin.
     *
     * @param Install $installer
     */
    public function uninstall(Install $installer);
}
