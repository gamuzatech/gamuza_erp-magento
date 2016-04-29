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

class Gamuza_ERP_Model_Api_Resource_Abstract
extends Mage_Api_Model_Resource_Abstract
{

protected $_modelName = null;

protected $_additionalAttributeCodes = null;

protected $_mapAttributes = array ();

public function _getFlatCollection ($collection = null, array $filters = null)
{
    if (empty ($collection)) $collection = Mage::getModel ($this->_modelName)->getCollection ();
    
    /** @var $apiHelper Mage_Api_Helper_Data */
    $apiHelper = Mage::helper ('api');
    $filters = $apiHelper->parseFilters ($filters, $this->_mapAttributes);

    try
    {
        foreach ($filters as $field => $value)
        {
            // hack for OR condition.
            if (strpos ($field, ',') !== false) $field = explode (',', $field); 
            
            $collection->addFieldToFilter ($field, $value);
        }
    }
    catch (Mage_Core_Exception $e)
    {
        $this->_fault ('filters_invalid', $e->getMessage ());
    }
    
    $result = array ();
    
    foreach ($collection as $child)
    {
        $row = $data = $child->toArray();
        // $row  = array();
        
        foreach ($this->_mapAttributes as $attributeAlias => $attributeCode)
        {
            $row [$attributeAlias] = (isset ($data [$attributeCode]) ? $data [$attributeCode] : null);
        }
        
        foreach ($this->_additionalAttributeCodes as $value) $row [$value] = $data [$value];
        
        $result [] = $row;
    }

    return $result;
}

public function _getCoreResource ()
{
    return Mage::getSingleton ('core/resource');
}

public function _getTable ($model_name)
{
    return $this->_getCoreResource ()->getTableName ($model_name);
}

}

