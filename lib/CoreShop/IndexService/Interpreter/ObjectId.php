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

namespace CoreShop\IndexService\Interpreter;

use CoreShop\Exception\UnsupportedException;
use Pimcore\Model\Object\AbstractObject;

/**
 * Class ObjectId
 * @package CoreShop\IndexService\Interpreter
 */
class ObjectId extends AbstractInterpreter
{
    /**
     * interpret value.
     *
     * @param mixed $value
     * @param array $config
     *
     * @return mixed
     *
     * @throws UnsupportedException
     */
    public function interpret($value, $config = null)
    {
        if($value instanceof AbstractObject) {
            return $value->getId();
        }

        return null;
    }
}
