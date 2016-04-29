<?php
/*
 * Gamuza ERP - ERP support for magento platform.
 * Copyright (c) 2016 Gamuza Technologies (http://www.gamuza.com.br/)
 * Author: Eneias Ramos de Melo <eneias@gamuza.com.br>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Library General Public
 * License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Library General Public License for more details.
 *
 * You should have received a copy of the GNU Library General Public
 * License along with this library; if not, write to the
 * Free Software Foundation, Inc., 51 Franklin St, Fifth Floor,
 * Boston, MA 02110-1301, USA.
 */

/*
 * See the AUTHORS file for a list of people on the Gamuza Team.
 * See the ChangeLog files for a list of changes.
 * These files are distributed with gamuza_erp-magento at http://github.com/gamuzabrasil/.
 */

class Gamuza_ERP_Model_Catalog_Category_Api
extends Mage_Catalog_Model_Category_Api
// extends Mage_Catalog_Model_Api_Resource
{

protected $_mapAttributes = array(
    'category_id' => 'entity_id'
);

public function erp_items (array $filters = null, $order = null, $limit = null)
{
    $category_attribute_code = Mage::getStoreConfig ('erp/attributes/category_id');

    $collection = Mage::getModel ('catalog/category')->getCollection ()->addAttributeToSelect ('*');

    /** @var $apiHelper Mage_Api_Helper_Data */
    $apiHelper = Mage::helper ('api');
    $filters = $apiHelper->parseFilters ($filters, $this->_mapAttributes);

    try
    {
        foreach ($filters as $field => $value)
        {
            // hack for OR condition.
            if (!strcmp (strtoupper ($field), 'OR'))
            {
                $field = $value;
                $value = null;
            }

            $collection->addAttributeToFilter ($field, $value);
        }

        if (!empty ($order))     $collection->getSelect ()->order ($order);
        if (intval ($limit) > 0) $collection->getSelect ()->limit (intval ($limit));
    }
    catch (Mage_Core_Exception $e)
    {
        $this->_fault ('filters_invalid', $e->getMessage ());
    }

    $result = array();

    foreach ($collection as $category)
    {
        $data = $category->toArray ();
        $row  = array ();

        foreach ($this->_mapAttributes as $attributeAlias => $attributeCode)
        {
            $row [$attributeAlias] = (isset ($data [$attributeCode]) ? $data [$attributeCode] : null);
        }

        foreach ($this->getAllowedAttributes ($category) as $attributeCode => $attribute)
        {
            if (isset ($data [$attributeCode]))
            {
                $row [$attributeCode] = $data [$attributeCode];
            }
        }

        $entity_id = $data ['entity_id'];

        $row ['assigned_products'] = $this->_getAssignedProducts ($entity_id);

        $parent_id = $data ['parent_id'];

        $parent_category = Mage::getModel ('catalog/category')->load ($parent_id);
        if (!empty ($parent_category) && intval ($parent_category->getId ()) > 0)
        {
            $row ["parent_{$category_attribute_code}"] = $parent_category->getData ($category_attribute_code);
        }

        // foreach ($this->_additionalAttributeCodes as $value) $row [$value] = $data [$value];

        $result [] = $row;
    }

    return $result;
}

public function erp_create ($items = array ())
{
    $category_attribute_code = Mage::getStoreConfig ('erp/attributes/category_id');
    $product_attribute_code = Mage::getStoreConfig ('erp/attributes/product_id');

    $result = null;

    foreach ($items as $child)
    {
        try
        {
            $store_code = $child ['store_code'];
            $store = Mage::getModel ('core/store')->load ($store_code, 'code');
            if (!empty ($store) && intval ($store->getId ()) > 0) $child ['store_id'] = $store->getId ();
            
            $attribute_set_name = $child ['attribute_set_name'];
            
            $child ['attribute_set_id'] = $this->_getUtils ()->_getAttributeSetId ($attribute_set_name);
            
            $category_attribute_value = $child [$category_attribute_code];
            
            $category = Mage::getModel ('catalog/category')->loadByAttribute ($category_attribute_code, $category_attribute_value);

            if (!empty ($category) && intval ($category->getId () > 0))
            {
                $category_id = $category->getId ();

                $result [] = $this->update ($category_id, $child);
            }
            else
            {
                $parent_attribute_value = $child ["parent_{$category_attribute_code}"];

                $parent_category = Mage::getModel ('catalog/category')->loadByAttribute ($category_attribute_code, $parent_attribute_value);
                if (!empty ($parent_category) && intval ($parent_category->getId ()) > 0) $category_parent_id = $parent_category->getId ();
                else $category_parent_id = Mage_Catalog_Model_Category::TREE_ROOT_ID;

                $category_id = $this->create ($category_parent_id, $child);

                $result [] = $category_id;
            }

            $assigned_products = $child ['assigned_products'];
            $new_assigned_products = null;

            foreach ($assigned_products as $product_value => $position)
            {
                $product = Mage::getModel ('catalog/product')->loadByAttribute ($product_attribute_code, $product_value);
                if (!empty ($product) && intval ($product->getId ()) > 0)
                {
                    $new_assigned_products [$product->getId ()] = $position;
                }
            }

            $this->_assignProducts ($category_id, $new_assigned_products);
        }
        catch (Mage_Core_Exception $e)
        {
            // $this->_fault('data_invalid', $e->getMessage());

            $result [] = $e->getMessage ();
        }
    }

    return $result;
}

public function create ($parentId, $categoryData, $store = null)
{
    return parent::create ($parentId, $this->_checkUseConfig ($categoryData), $store);
}

public function update ($categoryId, $categoryData, $store = null)
{
    return parent::update ($categoryId, $this->_checkUseConfig ($categoryData), $store);
}

private function _checkUseConfig ($categoryData)
{
    if (empty ($categoryData ['available_sort_by'])) $categoryData ['available_sort_by'] = 'use_config';
    if (empty ($categoryData ['default_sort_by'])) $categoryData ['default_sort_by'] = 'use_config';
    if (empty ($categoryData ['filter_price_range'])) $categoryData ['filter_price_range'] = 'use_config';
    //// if (empty ($categoryData ['default_sort_by_second'])) $categoryData ['default_sort_by_second'] = 'use_config';

    return $categoryData;
}

public function getAllowedAttributes ($entity, array $filter = null)
{
    $attributes = $entity->getResource ()
                    ->loadAllAttributes ($entity)
                    ->getAttributesByCode ();

    $result = array();

    foreach ($attributes as $attribute)
    {
        if ($this->_isAllowedAttribute ($attribute, $filter))
        {
            $result [$attribute->getAttributeCode ()] = $attribute;
        }
    }

    return $result;
}

private function _getAssignedProducts ($category_id)
{
    $product_attribute_code = Mage::getStoreConfig ('erp/attributes/product_id');
    
    $category = Mage::getModel ('catalog/category')->load ($category_id);
    $products_position = $category->getProductsPosition();

    $result = array ();

    foreach ($products_position as $product_id => $position)
    {
        $product = Mage::getModel ('catalog/product')->load ($product_id);
        if (!empty ($product) && intval ($product->getId ()) > 0)
        {
            $result [$product->getData ($product_attribute_code)] = $position;
        }
    }

    return $result;
}

private function _assignProducts ($category_id, $data = array ())
{
    $category = Mage::getModel ('catalog/category')->load ($category_id);

    $products_position = $category->getProductsPosition ();
    foreach ($data as $product_id => $position)
    {
        $products_position [$product_id] = $position;
    }
    $category->setPostedProducts ($products_position);

    try { $category->save (); }
    catch (Mage_Core_Exception $e) { $this->_log ($e->getMessage ()); }
}

private function _getUtils ()
{
    return Mage::getModel ('erp/utils');
}

}

