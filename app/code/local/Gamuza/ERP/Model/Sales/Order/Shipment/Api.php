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

class Gamuza_ERP_Model_Sales_Order_Shipment_Api
extends Mage_Sales_Model_Order_Shipment_Api
// extends Mage_Sales_Model_Api_Resource
{

public function erp_create ($items = null)
{
    $result = null;
    
    foreach ($items as $child)
    {
        $shipment_order_increment_id = $child ['shipment_order_increment_id'];
        $shipment_id = $child ['shipment_id'];
        $shipment_items_qty = $child ['shipment_items_qty'];
        $shipment_comment = $child ['shipment_comment'];
        $shipment_email = $child ['shipment_email'];
        $shipment_include_comment = $child ['shipment_include_comment'];
        
        foreach ($shipment_items_qty as $value)
        {
            $qtys [$value ['order_item_id']] = $value ['qty'];
        }
        
        try
        {
            $shipment_increment_id = $this->create ($shipment_order_increment_id,
                                                    $qtys,
                                                    $shipment_comment,
                                                    $shipment_email,
                                                    $shipment_include_comment);
            $result [] = array ( 'shipment_increment_id' => $shipment_increment_id, 'shipment_id' => $shipment_id);
        }
        catch (Mage_Core_Exception $e)
        {
            // $this->_fault('data_invalid', $e->getMessage());

            $result [] = $e->getMessage ();
        }
    }
    
    return $result;
}

}

