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

class Gamuza_ERP_Model_Eav_Entity_Attribute_Api
extends Mage_Catalog_Model_Product_Attribute_Api
// extends Mage_Catalog_Model_Api_Resource
{

public function erp_create ($items = array ())
{
    $result = null;
        
    foreach ($items as $child)
    {
        $this->_entityType = $child ['entity_type'];
        $child ['entity_type_id'] = $this->_getUtils ()->_getEntityTypeId ($this->_entityType);
        
        $attribute_code = $child ['attribute_code'];
        $attribute_id = $this->_getUtils ()->_getAttributeId ($this->_entityType, $attribute_code);

        try
        {
            if (empty ($attribute_id))
            {
                $result [] = $this->create ($child);
            }
            else
            {
                $info = $this->info ($attribute_id);
                
                $data = array_merge ($info, $child);
                
                $result [] = $this->update ($attribute_id, $data);
            }

            $attribute_id = $this->_getUtils ()->_getAttributeId ($this->_entityType, $attribute_code);

            $add_option = @$child ['add_option'];
            foreach ($add_option as $value)
            {
                $option_id = $this->_getUtils ()->_addAttributeOptionValue ($attribute_id, $value);
                
                $result ['add_option'][] = $option_id;
            }
        }
        catch (Mage_Core_Exception $e)
        {
            // $this->_fault('data_invalid', $e->getMessage());

            $result [] = $e->getMessage ();
        }
    }

    return $result;
}

private function _getUtils ()
{
    return Mage::getModel ('erp/utils');
}

}

