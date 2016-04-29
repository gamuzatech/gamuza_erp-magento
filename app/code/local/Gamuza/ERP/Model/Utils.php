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

class Gamuza_ERP_Model_Utils
{

const ERP_LOG_FILE = 'Gamuza_ERP.log';

public function _getEntityTypeId ($name = 'catalog_product')
{
    // return Mage::getModel ('catalog/product')->getResource ()->getTypeId ();
    return Mage::getModel ('eav/entity')->setType ($name)->getTypeId ();
}

public function _getDefaultAttributeSetId ()
{
    return Mage::getModel('catalog/product')->getResource()->getEntityType()->getDefaultAttributeSetId ();
}

public function _getAttributeSetId ($name)
{
    $tName = trim ($name);
    
    $entityTypeId = Mage::getModel ('eav/entity')->setType ('catalog_product')->getTypeId ();
    $collection = Mage::getModel ('eav/entity_attribute_set')->getCollection ();
    $collection->setEntityTypeFilter ($entityTypeId)->addFieldToFilter ('attribute_set_name', $tName);
    
    $item = $collection->getFirstItem ();

    if (!empty ($item) && intval ($item->getId ()) > 0)
    {
        return $item->getAttributeSetId ();
    }
    else
    {
        if (Mage::getStoreConfigFlag ('erp/attributes_set/auto_create')) return $this->_addAttributeSet ($name);
        else return Mage::getStoreConfig ('erp/attributes_set/use_default');
    }
}

public function _addAttributeSet ($name)
{
    $entityTypeId = Mage::getModel ('eav/entity')->setType ('catalog_product')->getTypeId ();
    $attribute_set = Mage::getModel ('eav/entity_attribute_set');
    $attribute_set->setEntityTypeId ($entityTypeId);
    $attribute_set->setAttributeSetName ($name);
    
    $attribute_set->validate ();
    $attribute_set->save ();
   
    $attribute_set->initFromSkeleton ($this->_getDefaultAttributeSetId ());
    $attribute_set->save ();
    
    $attribute_set_id = $attribute_set->getId ();
    
    //$attribute_group = Mage::getModel('eav/entity_attribute_group');
    //$attribute_group->setAttributeGroupName ($name);
    //$attribute_group->setAttributeSetId ($attribute_set_id);
    //$attribute_group->save ();
    
    return $attribute_set_id ? $attribute_set_id : -1;
}

public function _getAttributeId ($entity_type = 'catalog_product', $attribute_code)
{
    $model = Mage::getModel ('eav/entity_attribute')
        ->setEntityTypeId ($entity_type);

    $model->load ($attribute_code, 'attribute_code');

    return $model->getId ();
}

public function _getAttributeOptionIdByValue ($attribute_id, $value, $store_id = 0)
{
    $resource = $this->_getCoreResource ();
    $read = $resource->getConnection ('core_read');
    $tableAttributeOption = $resource->getTableName ('eav_attribute_option');
    $tableAttributeOptionValue = $resource->getTableName ('eav_attribute_option_value');

    $tValue = trim ($value);

    $select = $read->select ()
        ->from (array ('eaov' => $tableAttributeOptionValue), array ('option_id' => 'eaov.option_id'))
        ->join (array ('eao' => $tableAttributeOption), 'eaov.option_id = eao.option_id', null, null)
        ->where ("eao.attribute_id = {$attribute_id} AND eaov.store_id = {$store_id} AND BINARY eaov.value = '{$tValue}'");

    // echo $select->__toString () . PHP_EOL; // die;

    $children = $read->fetchAll ($select);

    $option_id = count ($children) ? $children [0]['option_id'] : -1;

    return (int) $option_id;
}

public function _getAttributeOptionValueById ($attribute_id, $option_id, $store_id = 0)
{
    $read = $this->_getReadConnection ();
    $select = $read->select ()
        ->from (array ('eaov' => 'eav_attribute_option_value'), array ('value' => 'eaov.value'))
        ->join (array ('eao' => 'eav_attribute_option'), 'eaov.option_id = eao.option_id', NULL, NULL)
        ->where ("eao.attribute_id = {$attribute_id} AND eaov.store_id = {$store_id} AND eaov.option_id = '{$option_id}'");

    // echo $select->__toString () . PHP_EOL; // die;

    $children = $read->fetchAll ($select);

    $value = count ($children) ? $children [0]['value'] : null;

    return (string) $value;
}

public function _getAttributeOptionValueId ($option_id, $store_id = 0)
{
    $resource = $this->_getCoreResource ();
    $read = $resource->getConnection ('core_read');
    $tableAttributeOptionValue = $resource->getTableName ('eav_attribute_option_value');

    $select = $read->select ()
        ->from (array ('eaov' => $tableAttributeOptionValue), array ('value_id' => 'eaov.value_id'))
        ->where ("eaov.option_id = {$option_id} AND eaov.store_id = {$store_id}");
    //echo $select->__toString () . PHP_EOL; die;
    $children = $read->fetchAll ($select);

    $value_id = count ($children) ? $children [0]['value_id'] : -1;

    return (int) $value_id;
}

public function _addAttributeOptionValue ($attribute_id, $data)
{
    $label = $data ['label'];
    $order = $data ['order'];
    $default = $data ['default'];

    $resource = $this->_getCoreResource ();
    $write = $resource->getConnection ('core_write');
    $tableAttribute = $resource->getTableName ('eav_attribute');
    $tableAttributeOption = $resource->getTableName ('eav_attribute_option');
    $tableAttributeOptionValue = $resource->getTableName ('eav_attribute_option_value');

    $option_id = -1;

    foreach ($label as $id => $value)
    {
        $store_code = $value ['store_code'];
        $store_value = $value ['value'];

        $store = Mage::getModel ('core/store')->load ($store_code, 'code');
        if (empty ($store)) continue;

        $store_id = $store->getId ();
        if (!is_numeric ($store_id)) continue;

        if ($store_id == 0)
        {
            $option_id = $this->_getAttributeOptionIdByValue ($attribute_id, $store_value, $store_id);
            if ($option_id < 0)
            {
                $write->insert ($tableAttributeOption, array ('attribute_id' => $attribute_id, 'sort_order' => $order));

                $option_id = $write->lastInsertId ();
            }
            else
            {
                $write->insertOnDuplicate ($tableAttributeOption, array ('option_id' => $option_id, 'attribute_id' => $attribute_id, 'sort_order' => $order));
            }
        }

        $value_id = $this->_getAttributeOptionValueId ($option_id, $store_id);
        $tValue = trim ($store_value);

        $write->insertOnDuplicate ($tableAttributeOptionValue, array ('value_id' => $value_id, 'option_id' => $option_id, 'store_id' => $store_id, 'value' => $tValue));
        
        if($default)
        {
            $write->update($tableAttribute, array('default_value' => $option_id), "attribute_id = {$attribute_id}");
        }
    }

    return $option_id;
}

public function _getUserAttributesCollection ($entity_type = 'catalog_product')
{
    $collection = Mage::getResourceModel ('eav/entity_attribute_collection')
        ->setEntityTypeFilter ($this->_getEntityTypeId ($entity_type))
        ->addFieldToFilter ('frontend_input', array ('in' => array ('select', 'multiselect')));
    $collection->getSelect()->where ("source_model = 'eav/entity_attribute_source_table' OR source_model IS NULL");
    $collection->load ();

    // echo $collection->getSelect()->__toString(); // die;

    return $collection;
}

// Hack: Remove magento super attributes for configurable products before load it.
public function _removeSuperAttributes ($product_id)
{
    $resource = $this->_getCoreResource ();
    $write = $this->_getWriteConnection ();
    $table = $resource->getTableName ('catalog_product_super_attribute');
    $write->delete ($table, "product_id = {$product_id}");
}

public function _saveAssociation ($parent_sku, $sku)
{
    $assocItem = Mage::getModel ('erp/products_associations')->load ($sku, 'sku');
    if (empty ($assocItem) || !$assocItem->getId ())
    {
        $assocItem = Mage::getModel ('erp/products_associations');
        $assocItem->setSku ($sku);
    }
    $assocItem->setParentSku ($parent_sku);
    $assocItem->setModified (1);

    try { $assocItem->save (); }
    catch (Exception $e) { $this->_log ($e->getMessage ()); }
}

public function _assignProducts ()
{
    $product_conf_attributes = Mage::getStoreConfig ('erp/attributes_set/associations');
    if (empty ($product_conf_attributes))
    {
        $this->_log (' ! No configurable product attribute selected for association in admin panel.');

        return;
    }

    $options = explode (',', $product_conf_attributes);
    foreach ($options as $_option)
    {
        list ($attribute_set_id, $attribute_id) = explode (':', $_option);

        $read = $this->_getReadConnection ();
        $select = $read->select ()
            ->from ('eav_attribute', array ('attribute_id', 'attribute_code'))
            ->where ("attribute_id = ({$attribute_id})");
        // echo $select->__toString () . PHP_EOL; // die;
        $children = $read->fetchAll ($select);

        $product_attribute_sets [] = array ('attribute_id' => $attribute_id, 'attribute_code' => $children [0]['attribute_code'], 'attribute_set_id' => $attribute_set_id);
    }
    // print_r ($product_attribute_sets); // die;

    $associations_collection = Mage::getModel ('erp/products_associations')->getCollection ();
    $select = $associations_collection->getSelect ();
    $select->where ('is_modified = 1');
    $select->group ('parent_sku');
    // echo $select->__toString () . PHP_EOL; // die;

    if (!$associations_collection->count ())
    {
        $this->_log (' ! No product to associate.');

        return;
    }
    $this->_log (' ! Configurable products to associate : ' . $associations_collection->count ());

    foreach ($associations_collection as $parent)
    {
        $parent_sku = $parent->getParentSku ();

        $mageProduct = Mage::getModel ('catalog/product')->loadByAttribute ('sku', $parent_sku);
        if (empty ($mageProduct) || !$mageProduct->getId ())
        {
            $this->_log (" ! Unable to load configurable product sku : {$parent_sku}.");

            continue;
        }

        $productTypeId = $mageProduct->getTypeId ();
        if (strcmp ($productTypeId, 'configurable'))
        {
             $this->_log (" ! Product sku : {$parent_sku} is not : {$productTypeId}. Skipping ...");

             continue;
        }

        $mageProductId = $mageProduct->getId ();
        $this->_log (" > Processing configurable product sku : {$parent_sku} id : {$mageProductId} ...");

        $this->_removeSuperAttributes ($mageProductId);

        $mageProduct->setCanSaveCustomOptions(true);
        $mageProduct->setCanSaveConfigurableAttributes (true);

        /*
         * Configurable attributes.
         */
        $productInstance = $mageProduct->getTypeInstance();
        // $conf_attributes = $productInstance->getConfigurableAttributesAsArray ();

        $conf_attributes_data = "";
        foreach ($product_attribute_sets as $value)
        {
            if ($mageProduct->getAttributeSetId () == $value ['attribute_set_id'])
            {
                $conf_attributes_data [] = array ('attribute_id' => $value ['attribute_id'], 'attribute_code' => $value ['attribute_code']);
            }
        }
        // print_r ($conf_attributes_data); // die;
        $mageProduct->setConfigurableAttributesData ($conf_attributes_data);

        /*
         * Simple products.
         */
        $simples_collection = Mage::getModel ('erp/products_associations')->getCollection ();
        $select = $simples_collection->getSelect ()->where ("parent_sku = '{$parent_sku}'");
        //echo $select->__toString () . PHP_EOL; // die;

        $conf_products_data = "";
        foreach ($simples_collection as $product)
        {
            $sku = $product->getSku ();

            $mageSimpleProduct = Mage::getModel ('catalog/product')->loadByAttribute ('sku', $sku);
            if (!$mageSimpleProduct || !$mageSimpleProduct->getId ())
            {
                $this->_log (" ! Unable to load simple product sku : {$sku}.");

                continue;
            }
            $mageSimpleProductId = $mageSimpleProduct->getId ();

            $this->_log (" > Processing simple product sku : {$sku} ...");
            // print_r ($product_attribute_sets); // die;

            foreach ($product_attribute_sets as $value)
            {
                if ($mageSimpleProduct->getAttributeSetId () == $value ['attribute_set_id'])
                {
                    $conf_products_data [$mageSimpleProductId][] = array ('attribute_id' => $value ['attribute_id']);
                }
            }
        }
        // print_r ($conf_products_data); // die;
        $mageProduct->setConfigurableProductsData ($conf_products_data);

        $this->_log (' > Saving associations ...');
        $mageProduct->save ();

        $this->_log (' > Cleanup ...');
        foreach ($simples_collection as $product)
        {
            $product->setModified (0);
            $product->save ();
        }
    }
}

public function _getCoreResource ()
{
    return Mage::getSingleton ('core/resource');
}

public function _getReadConnection ()
{
    return $this->_getCoreResource ()->getConnection ('core_read');
}

public function _getWriteConnection ()
{
    return $this->_getCoreResource ()->getConnection ('core_write');
}

public function _log ($message)
{
    Mage::log ($message . PHP_EOL, null, self::ERP_LOG_FILE);
}

}

