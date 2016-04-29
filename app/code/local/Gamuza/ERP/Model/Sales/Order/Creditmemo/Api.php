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

class Gamuza_ERP_Model_Sales_Order_Creditmemo_Api
extends Mage_Sales_Model_Order_Creditmemo_Api
// extends Mage_Sales_Model_Api_Resource
{

public function erp_create ($items = null)
{
    $result = null;
    
    foreach ($items as $child)
    {
        $creditmemo_order_increment_id = $child ['creditmemo_order_increment_id'];
        $creditmemo_id = $child ['creditmemo_id'];
        $creditmemo_items_qty = $child ['creditmemo_items_qty'];
        $creditmemo_comment = $child ['creditmemo_comment'];
        $creditmemo_email = $child ['creditmemo_email'];
        $creditmemo_include_comment = $child ['creditmemo_include_comment'];
        $creditmemo_refund = $child ['creditmemo_refund'];
        
        foreach ($creditmemo_items_qty as $value)
        {
            $qtys [$value ['order_item_id']] = $value ['qty'];
        }
        
        try
        {
            $creditmemo_increment_id = $this->create ($creditmemo_order_increment_id,
                                                        $qtys,
                                                        $creditmemo_comment,
                                                        $creditmemo_email,
                                                        $creditmemo_include_comment,
                                                        $creditmemo_refund);
            $result [] = array ( 'creditmemo_increment_id' => $creditmemo_increment_id, 'creditmemo_id' => $creditmemo_id);
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

