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

class Gamuza_ERP_Model_Core_Store_Api
extends Gamuza_ERP_Model_Api_Resource_Abstract
// extends Mage_Api_Model_Resource_Abstract
{

protected $_modelName = 'core/store';

protected $_additionalAttributeCodes = array ('website_code', 'group_name');

// protected $_mapAttributes = array ('store_id' => 'entity_id');

public function erp_items (array $filters = null)
{
    $collection = Mage::getModel ($this->_modelName)->getCollection ();

    $select = $collection->getSelect ();
    $select->join(
        array ('core_website' => $this->_getTable ('core_website')),
        'main_table.website_id = core_website.website_id',
        array ('website_code' => 'core_website.code')
    );
    $select->join(
        array ('core_store_group' => $this->_getTable ('core_store_group')),
        'main_table.group_id = core_store_group.group_id',
        array ('group_name' => 'core_store_group.name')
    );

    return $this->_getFlatCollection ($collection, $filters);
}

}

