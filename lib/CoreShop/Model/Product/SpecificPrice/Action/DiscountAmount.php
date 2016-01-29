<?php
/**
 * CoreShop
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015 Dominik Pfaffenbauer (http://dominik.pfaffenbauer.at)
 * @license    http://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Model\Product\SpecificPrice\Action;

use CoreShop\Model\Currency;
use CoreShop\Model\Product;
use Pimcore\Model;
use CoreShop\Tool;

/**
 * Class DiscountAmount
 * @package CoreShop\Model\PriceRule\Action
 */
class DiscountAmount extends AbstractAction {

    /**
     * @var int
     */
    public $currency;

    /**
     * @var float
     */
    public $amount;

    /**
     * @var string
     */
    public $type = "discountAmount";

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param Currency|int $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * Calculate discount
     *
     * @param Product $product
     * @return float
     */
    public function getDiscount(Product $product) {
        return Tool::convertToCurrency($this->getAmount(), $this->getCurrency(), Tool::getCurrency());
    }
}