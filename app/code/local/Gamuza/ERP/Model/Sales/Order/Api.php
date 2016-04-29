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

class Gamuza_ERP_Model_Sales_Order_Api
extends Mage_Sales_Model_Order_Api
// extends Mage_Sales_Model_Api_Resource
{

public function __construct()
{
    $this->_attributesMap = array(
        'order' => array ('order_id' => 'entity_id'),
        'order_address' => array ('address_id' => 'entity_id'),
        'order_payment' => array ('payment_id' => 'entity_id'),
        'order_status_history' => array ('status_history_id' => 'entity_id'),
    );
}

public function erp_items ($filters = null, $order = null, $limit = null)
{
    $orders = $this->items ($filters, $order, $limit);

    $result = array ();

    foreach ($orders as $child)
    {
        $increment_id = $child ['increment_id'];

        $info = $this->info ($increment_id);

        $store_id = $info ['store_id'];
        $store = Mage::getModel ('core/store')->load ($store_id);
        $info ['store_code'] = $store->getCode ();

        $customer_group_id = $info ['customer_group_id'];
        $customer_group = Mage::getModel ('customer/group')->load ($customer_group_id);
        $info ['customer_group_code'] = $customer_group->getCode ();

        $customer_id = $info ['customer_id'];
        $customer = Mage::getModel ('customer/customer')->load ($customer_id);
        $info ['customer_taxvat'] = $customer->getTaxvat ();

        $gift_message_id = $info ['gift_message_id'];
        $gift_message = Mage::getModel ('giftmessage/message')->load ($gift_message_id);
        if (!empty ($gift_message) && intval ($gift_message->getId () > 0))
        {
            $info ['gift_message_sender'] = $gift_message->getSender ();
            $info ['gift_message_recipient'] = $gift_message->getRecipient ();
        }
        
        $items = $info ['items'];
        foreach ($items as $_id => $_item)
        {
            $parent_item_id = $_item ['parent_item_id'];
            $parent_item = Mage::getModel ('sales/order_item')->load ($parent_item_id);
            if (!empty ($parent_item) && intval ($parent_item->getId () > 0))
            {
                $product_id = $parent_item->getProductId ();
                $item_field = 'parent_sku';
            }
            else
            {
                $product_id = $_item ['product_id'];
                $item_field = 'original_sku';
            }
            
            $product = Mage::getModel ('catalog/product')->load ($product_id);
            if (!empty ($product) && intval ($product->getId () > 0))
            {
                $info ['items'][$_id][$item_field] = $product->getSku ();
            }

            $gift_message_id = $_item ['gift_message_id'];
            $gift_message = Mage::getModel ('giftmessage/message')->load ($gift_message_id);
            if (!empty ($gift_message) && intval ($gift_message->getId () > 0))
            {
                $info ['items'][$_id]['gift_message_sender'] = $gift_message->getSender ();
                $info ['items'][$_id]['gift_message_recipient'] = $gift_message->getRecipient ();
            }
            
            $store_id = $_item ['store_id'];
            $store = Mage::getModel ('core/store')->load ($store_id);
            if (!empty ($store) && intval ($store->getId () > 0))
            {
                $info ['items'][$_id]['store_code'] = $store->getCode ();
            }
        }
        
        $payment_method = $info ['payment']['method'];
        if (!strcmp ($payment_method, 'ccsave'))
        {
            $payment_cc_number = Mage::helper ('core')->decrypt ($info ['payment']['cc_number_enc']);
            $payment_cc_cid = Mage::helper ('core')->decrypt ($info ['payment']['cc_cid_enc']);
            
            $info ['payment']['cc_number_enc'] = $this->_encrypt ($payment_cc_number);
            $info ['payment']['cc_cid_enc'] = $this->_encrypt ($payment_cc_cid);
        }

        $result [] = $info;
    }

    return $result;
}

public function erp_add_comment ($items = array ())
{
    $result = null;

    foreach ($items as $child)
    {
        $order_increment_id = $child ['order_increment_id'];
        $is_customer_notified = $child ['is_customer_notified'];
        $comment = $child ['comment'];
        $status = $child ['status'];

        try
        {
            $result [] = $this->addComment ($order_increment_id, $status, $comment, $is_customer_notified);
        }
        catch (Mage_Core_Exception $e)
        {
            // $result [] = $this->_fault ('status_not_changed', $e->getMessage ());

            $result [] = $e->getMessage ();
        }
    }

    return $result;
}

public function erp_cancel ($items = array ())
{
    $result = null;

    foreach ($items as $child)
    {
        $order_increment_id = $child ['order_increment_id'];

        try
        {
            $result [] = $this->cancel ($order_increment_id);
        }
        catch (Mage_Core_Exception $e)
        {
            // $result [] = $this->_fault ('status_not_changed', $e->getMessage());

            $result [] = $e->getMessage ();
        }
    }

    return $result;
}

public function items ($filters = null, $order = null, $limit = null)
{
    $orders = array ();

    // TODO: add full name logic
    // $billingAliasName = 'billing_o_a';
    // $shippingAliasName = 'shipping_o_a';

    /** @var $orderCollection Mage_Sales_Model_Mysql4_Order_Collection */
    $orderCollection = Mage::getModel ("sales/order")->getCollection ();
/*
    $billingFirstnameField = "{$billingAliasName}.firstname";
    $billingLastnameField = "{$billingAliasName}.lastname";
    $shippingFirstnameField = "{$shippingAliasName}.firstname";
    $shippingLastnameField = "{$shippingAliasName}.lastname";

    $orderCollection->addAttributeToSelect ('*')
        ->addAddressFields ()
        ->addExpressionFieldToSelect ('billing_firstname', "{{billing_firstname}}",
            array ('billing_firstname' => $billingFirstnameField))
        ->addExpressionFieldToSelect ('billing_lastname', "{{billing_lastname}}",
            array ('billing_lastname' => $billingLastnameField))
        ->addExpressionFieldToSelect ('shipping_firstname', "{{shipping_firstname}}",
            array ('shipping_firstname' => $shippingFirstnameField))
        ->addExpressionFieldToSelect ('shipping_lastname', "{{shipping_lastname}}",
            array ('shipping_lastname' => $shippingLastnameField))
        ->addExpressionFieldToSelect ('billing_name', "CONCAT({{billing_firstname}}, ' ', {{billing_lastname}})",
            array ('billing_firstname' => $billingFirstnameField, 'billing_lastname' => $billingLastnameField))
        ->addExpressionFieldToSelect ('shipping_name', 'CONCAT({{shipping_firstname}}, " ", {{shipping_lastname}})',
            array ('shipping_firstname' => $shippingFirstnameField, 'shipping_lastname' => $shippingLastnameField)
    );
*/
    /** @var $apiHelper Mage_Api_Helper_Data */
    $apiHelper = Mage::helper ('api');
    $filters = $apiHelper->parseFilters ($filters, $this->_attributesMap ['order']);
    try
    {
        foreach ($filters as $field => $value)
        {
            // hack for OR condition.
            if (strpos ($field, ',') !== false) $field = explode (',', $field);

            $orderCollection->addFieldToFilter ($field, $value);
        }

        if (!empty ($order))     $orderCollection->getSelect ()->order ($order);
        if (intval ($limit) > 0) $orderCollection->getSelect ()->limit (intval ($limit));
    }
    catch (Mage_Core_Exception $e)
    {
        $this->_fault ('filters_invalid', $e->getMessage ());
    }

    foreach ($orderCollection as $order)
    {
        $orders [] = $this->_getAttributes ($order, 'order');
    }

    return $orders;
}

public function _encrypt ($data)
{
    $key = Mage::getStoreConfig ('erp/settings/crypt_key');
    $crypt = Varien_Crypt::factory ()->init ($key);
    
    return base64_encode ($crypt->encrypt ((string) $data));
}

}

