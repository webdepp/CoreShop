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

namespace CoreShop\IndexService;

use CoreShop\Exception;
use CoreShop\IndexService\Getter\AbstractGetter;
use CoreShop\IndexService\Interpreter\AbstractInterpreter;
use CoreShop\IndexService\Interpreter\RelationInterpreter;
use CoreShop\Model\Index;
use CoreShop\Model\Product;
use Pimcore\Logger;
use Pimcore\Model\Object\AbstractObject;
use Pimcore\Tool;

/**
 * Class AbstractWorker
 * @package CoreShop\IndexService
 */
abstract class AbstractWorker
{
    /**
     * Index Configuration.
     *
     * @var Index
     */
    protected $index = null;

    /**
     * AbstractWorker constructor.
     *
     * @param Index $index
     */
    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    /**
     * prepares Data for index.
     *
     * @param Product $object
     * @param boolean $convertArrayToString
     *
     * @return array("data", "relation")
     *
     * @throws \CoreShop\Exception\UnsupportedException
     */
    protected function prepareData(Product $object, $convertArrayToString = true)
    {
        $a = \Pimcore::inAdmin();
        $b = AbstractObject::doGetInheritedValues();
        \Pimcore::unsetAdminMode();
        AbstractObject::setGetInheritedValues(true);
        $hidePublishedMemory = AbstractObject::doHideUnpublished();
        AbstractObject::setHideUnpublished(false);

        $categories = $object->getCategories();

        $categoryIds = array();
        $parentCategoryIds = array();

        if ($categories) {
            foreach ($categories as $c) {
                $categoryIds[$c->getId()] = $c->getId();

                $parents = $c->getHierarchy();

                foreach ($parents as $p) {
                    $parentCategoryIds[] = $p->getId();
                }
            }
        }

        ksort($categoryIds);
        $categoryIds = array_values($categoryIds);

        $virtualProductId = $object->getId();
        $virtualProductActive = $object->getEnabled();

        if ($object->getType() === Product::OBJECT_TYPE_VARIANT) {
            $parent = $object->getParent();

            while ($parent->getType() === Product::OBJECT_TYPE_VARIANT && $parent instanceof Product) {
                $parent = $parent->getParent();
            }

            $virtualProductId = $parent->getId();
            $virtualProductActive = $parent->getEnabled();
        }

        $data = array(
            'o_id' => $object->getId(),
            'o_key' => $object->getKey(),
            'o_classId' => $object->getClassId(),
            'o_virtualProductId' => $virtualProductId,
            'o_virtualProductActive' => $virtualProductActive === null ? false : $virtualProductActive,
            'o_type' => $object->getType(),
            'categoryIds' => $convertArrayToString ? ','.implode(',', $categoryIds).',' : $categoryIds,
            'parentCategoryIds' => $convertArrayToString ? ','.implode(',', $parentCategoryIds).',' : $parentCategoryIds,
            'active' => $object->getEnabled() === null ? false : $object->getEnabled(),
            'shops' => $convertArrayToString ? ','.@implode(',', $object->getShops()).',' : $object->getShops(),
            'minPrice' => $object->getMinPrice(),
            'maxPrice' => $object->getMaxPrice()
        );

        $relationData = array();
        $columnConfig = $this->getColumnsConfiguration();

        foreach ($columnConfig as $column) {
            if ($column instanceof Index\Config\Column\AbstractColumn) {
                try {
                    $value = null;
                    $getter = $column->getGetter();
                    $interpreter = $column->getInterpreter();

                    if (!empty($getter)) {
                        $getterClass = '\\CoreShop\\IndexService\\Getter\\'.$getter;

                        if (Tool::classExists($getterClass)) {
                            $getterObject = new $getterClass();

                            if ($getterObject instanceof AbstractGetter) {
                                $value = $getterObject->get($object, $column);
                            } else {
                                throw new Exception('Getter class must inherit from AbstractGetter');
                            }
                        }
                    } else {
                        $getter = 'get'.ucfirst($column->getKey());

                        if (method_exists($object, $getter)) {
                            $value = $object->$getter();
                        }
                    }

                    if (!empty($interpreter)) {
                        $interpreterClass = '\\CoreShop\\IndexService\\Interpreter\\'.$interpreter;

                        if (Tool::classExists($interpreterClass)) {
                            $interpreterObject = new $interpreterClass();

                            if ($interpreterObject instanceof AbstractInterpreter) {
                                $value = $interpreterObject->interpret($value, $column);

                                if ($interpreterObject instanceof RelationInterpreter) {
                                    foreach ($value as $v) {
                                        $relData = array();
                                        $relData['src'] = $object->getId();
                                        $relData['src_virtualProductId'] = $virtualProductId;
                                        $relData['dest'] = $v['dest'];
                                        $relData['fieldname'] = $column->name;
                                        $relData['type'] = $v['type'];
                                        $relationData[] = $relData;
                                    }
                                } else {
                                    $data[$column->getName()] = $value;
                                }
                            } else {
                                throw new \Exception('Interpreter class must inherit form AbstractInterpreter');
                            }
                        } else {
                            $data[$column->getName()] = $value;
                        }
                    } else {
                        $data[$column->getName()] = $value;
                    }

                    if (is_array($data[$column->getName()]) && $convertArrayToString) {
                        $data[$column->getName()] = ','.implode($data[$column->getName()], ',').',';
                    }
                } catch (\Exception $e) {
                    Logger::err('Exception in CoreShopIndexService: '.$e->getMessage(), $e);
                }
            }
        }

        if ($a) {
            \Pimcore::setAdminMode();
        }

        AbstractObject::setGetInheritedValues($b);
        AbstractObject::setHideUnpublished($hidePublishedMemory);

        return array(
            'data' => $data,
            'relation' => $relationData,
        );
    }

    /**
     * @return array
     */
    public function getColumnsConfiguration()
    {
        if ($this->index->getConfig() instanceof Index\Config) {
            return $this->index->getConfig()->getColumns();
        }

        return [];
    }

    /**
     * creates or updates necessary index structures (like database tables and so on).
     */
    abstract public function createOrUpdateIndexStructures();

    /**
     * deletes necessary index structuers (like database tables).
     *
     * @return mixed
     */
    abstract public function deleteIndexStructures();

   /**
    * deletes given element from index.
    *
    * @param Product $object
    */
   abstract public function deleteFromIndex(Product $object);

    /**
     * updates given element in index.
     *
     * @param Product $object
     */
    abstract public function updateIndex(Product $object);

    /**
     * returns product list implementation valid and configured for this worker/tenant.
     *
     * @return Product\Listing
     */
    abstract public function getProductList();

    /**
     * Renders the condition to fit the service
     *
     * @param Condition $condition
     * @return mixed
     */
    abstract public function renderCondition(Condition $condition);

    /**
     * get index.
     *
     * @return Index
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * set index.
     *
     * @param Index $index
     */
    public function setIndex($index)
    {
        $this->index = $index;
    }
}
