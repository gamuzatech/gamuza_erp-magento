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

class Gamuza_ERP_Model_Adminhtml_System_Config_Source_Attributes_Abstract
{

protected $_entity_type = '';

public function toOptionArray ()
{
    $attributes = $this->_getAttributeCollection ();

    $result [""] = "";

    if ($attributes->count ())
    {
        foreach ($attributes as $id => $value)
        {
            $attribute_id = $value->getAttributeId ();
            $attribute_code = $value->getAttributeCode ();
            $attribute_frontend_label = $value->getFrontendLabel ();

            $result [$attribute_code] = "{$attribute_code} ( {$attribute_frontend_label} - {$attribute_id} )";
        }
    }

    return $result;
}

public function _getEntityTypeId ()
{
    $item = Mage::getModel ($this->_entity_type);

    return $item->getResource ()->getTypeId ();
}

public function _getAttributeSetCollection ()
{
    $collection = Mage::getResourceModel ('eav/entity_attribute_set_collection')
        ->setEntityTypeFilter ($this->_getEntityTypeId ());

    return $collection;
}

public function _getAttributeCollection ()
{
    $collection = Mage::getResourceModel ('eav/entity_attribute_collection')
        ->setEntityTypeFilter ($this->_getEntityTypeId ());

    return $collection;
}

}

