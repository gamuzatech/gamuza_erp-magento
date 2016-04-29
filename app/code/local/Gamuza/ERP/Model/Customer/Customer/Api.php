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

class Gamuza_ERP_Model_Customer_Customer_Api
extends Mage_Customer_Model_Customer_Api
// extends Mage_Customer_Model_Api_Resource
{

protected $_additionalAttributeCodes = array ('website_code', 'store_code', 'group_code');

public function erp_items (array $filters = null, $order = null, $limit = null)
{
    $collection = Mage::getModel ('customer/customer')->getCollection ()->addAttributeToSelect ('*');

    $select = $collection->getSelect ();
    $select->join(
        array ('core_website' => $this->_getTable ('core_website')),
        'e.website_id = core_website.website_id',
        array ('website_code' => 'core_website.code')
    );
    $select->join(
        array ('core_store' => $this->_getTable ('core_store')),
        'e.store_id = core_store.store_id',
        array ('store_code' => 'core_store.code')
    );
    $select->join(
        array ('customer_group' => $this->_getTable ('customer_group')),
        'e.group_id = customer_group.customer_group_id',
        array ('group_code' => 'customer_group.customer_group_code')
    );

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

    foreach ($collection as $customer)
    {
        $data = $customer->toArray ();
        $row  = array ();

        foreach ($this->_mapAttributes as $attributeAlias => $attributeCode)
        {
            $row [$attributeAlias] = (isset ($data [$attributeCode]) ? $data [$attributeCode] : null);
        }

        foreach ($this->getAllowedAttributes ($customer) as $attributeCode => $attribute)
        {
            if (isset ($data[$attributeCode]))
            {
                $row [$attributeCode] = $data [$attributeCode];
            }
        }

        foreach ($this->_additionalAttributeCodes as $value) $row [$value] = $data [$value];

        $result[] = $row;
    }

    return $result;
}

private function _getCoreResource ()
{
    return Mage::getSingleton ('core/resource');
}

private function _getTable ($model_name)
{
    return $this->_getCoreResource ()->getTableName ($model_name);
}

}

