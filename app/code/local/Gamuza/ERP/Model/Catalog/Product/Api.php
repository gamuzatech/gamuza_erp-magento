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

class Gamuza_ERP_Model_Catalog_Product_Api
extends Mage_Catalog_Model_Product_Api
// extends Mage_Catalog_Model_Api_Resource
{

public function erp_items ($filters = null, $order = null, $limit = null)
{
    $category_attribute_code = Mage::getStoreConfig ('erp/attributes/category_id');
    $product_attribute_code = Mage::getStoreConfig ('erp/attributes/product_id');

    $attributes = $this->_getUtils ()->_getUserAttributesCollection ();

    $products = $this->items ($filters, $order, $limit);

    $result = array ();

    foreach ($products as $child)
    {
        $product_id = $child ['product_id'];

        $info = $this->info ($product_id);

        $type_id = $info ['type'];
        $sku = $info ['sku'];
        $website_ids = $info ['websites'];
        $category_ids = $info ['categories'];

        $attribute_set_id = $info ['set'];
        $attribute_set = Mage::getModel ('eav/entity_attribute_set')->load ($attribute_set_id);

        $info ['attribute_set_name'] = $attribute_set->getAttributeSetName ();

        $website_codes = null;
        foreach ($website_ids as $id => $value)
        {
            $website = Mage::getModel ('core/website')->load ($value);
            if (!empty ($website) && intval ($website->getId ()) > 0) $website_codes [] = $website->getCode ();
        }
        if ($website_codes) $info ['website_codes'] = $website_codes;
        
        $category_codes = null;
        foreach ($category_ids as $id => $value)
        {
            $category = Mage::getModel ('catalog/category')->load ($value);
            if (!empty ($category) && intval ($category->getId ()) > 0)
            {
                $category_codes [] = $category->getData ($category_attribute_code);
            }
        }
        if ($category_codes) $info ['category_codes'] = $category_codes;

        foreach ($attributes as $value)
        {
            $attribute_id = $value->getAttributeId ();
            $attribute_code = $value->getAttributeCode ();
            
            $option_id = $info [$attribute_code];
            $option_value = $this->_getUtils ()->_getAttributeOptionValueById ($attribute_id, $option_id);

            $info [$attribute_code] = $option_value;
        }

        $result [] = $info;
    }

    return $result;
}

public function erp_create ($items = array ())
{
    $category_attribute_code = Mage::getStoreConfig ('erp/attributes/category_id');
    $product_attribute_code = Mage::getStoreConfig ('erp/attributes/product_id');

    $attributes = $this->_getUtils ()->_getUserAttributesCollection ();

    $result = null;

    foreach ($items as $child)
    {
        $type_id = $child ['type_id'];
        $sku = $child ['sku'];
        $website_codes = $child ['website_codes'];
        $store_codes = $child ['store_codes'];
        $category_codes = $child ['category_codes'];
        
        $attribute_set_name = $child ['attribute_set_name'];
        $attribute_set_id = $this->_getUtils ()->_getAttributeSetId ($attribute_set_name);

        $product_attribute_value = $child [$product_attribute_code];
        
        $product = Mage::getModel ('catalog/product')->loadByAttribute ($product_attribute_code, $product_attribute_value);
        if (empty ($product) || !$product->getId ())
        {
            $product = Mage::getModel ('catalog/product')->loadByAttribute ('sku', $sku);
        }

        try
        {
            $website_ids = null;
            foreach ($website_codes as $id => $code)
            {
                $website = Mage::getModel ('core/website')->load ($code, 'code');
                if (!empty ($website) && intval ($website->getId ()) > 0) $website_ids [] = $website->getId ();
            }
            if ($website_ids) $child ['website_ids'] = $website_ids;
            
            $store_ids = null;
            foreach ($store_codes as $id => $code)
            {
                $store = Mage::getModel ('core/store')->load ($code, 'code');
                if (!empty ($store) && intval ($store->getId ()) > 0) $store_ids [] = $store->getId ();
            }
            if ($store_ids) $child ['store_ids'] = $store_ids;
            
            $category_ids = null;
            foreach ($category_codes as $id => $code)
            {
                $category = Mage::getModel ('catalog/category')->loadByAttribute ($category_attribute_code, $code);
                if (!empty ($category) && intval ($category->getId ()) > 0)
                {
                    $category_ids [] = $category->getId ();
                }
            }
            if ($category_ids) $child ['category_ids'] = $category_ids;
            
            foreach ($attributes as $value)
            {
                $attribute_id = $value->getAttributeId ();
                $attribute_code = $value->getAttributeCode ();
                
                $attribute_value = $child [$attribute_code];
                $product_value = $this->_getUtils ()->_getAttributeOptionIdByValue ($attribute_id, $attribute_value);
                if (intval ($product_value) < 0) unset ($child [$attribute_code]);
                else $child [$attribute_code] = $product_value;
            }
            
            if (!empty ($product) && intval ($product->getId ()) > 0)
            {
                $product_id = $product->getId ();

                $result [] = $this->update ($product_id, $child);
            }
            else
            {
                $product_id = $this->create ($type_id, $attribute_set_id, $sku, $child);
                
                $result [] = $product_id;
            }

            if (!strcmp ($type_id, 'configurable'))
            {
                $this->_getUtils ()->_removeSuperAttributes ($product_id);
                
                $table = $this->_getCoreResource ()->getTableName ('erp/products_associations');
                $this->_getUtils ()->_getWriteConnection ()->update ($table, array ('is_modified' => 1), "parent_sku = '{$sku}'");
            }
            elseif (!strcmp ($type_id, 'simple'))
            {
                $parent_sku = $child ['parent_sku'];
                $parent_product = Mage::getModel ('catalog/product')->loadByAttribute ('sku', $parent_sku);
                if (!empty ($parent_product) && intval ($parent_product->getId ()) > 0)
                {
                    $this->_getUtils ()->_saveAssociation ($parent_sku, $sku);
                }
            }
            
            $media_gallery_upload = array_key_exists ('media_gallery_upload', $child);
            if ($media_gallery_upload)
            {
                $storage = $this->_getMediaApi ()->items ($product_id);
                foreach ($storage as $_storage)
                {
                    $_storage_file = $_storage ['file'];
                    $this->_getMediaApi ()->remove ($product_id, $_storage_file);
                }
                
                $images = $child ['media_gallery']['images'];
                foreach ($images as $_image)
                {
                    $_image_type = $_image ['type'];
                    $_image_content = $_image ['content'];
                    $_image_mime = $_image ['mime'];
                    
                    unset ($_image ['type']);
                    unset ($_image ['content']);
                    unset ($_image ['mime']);
                    
                    $_storage_exist = false;
                    /*
                    foreach ($storage as $_storage)
                    {
                        $_storage_file = $_storage ['file'];
                        $_storage_types = $_storage ['types'];
                        if (in_array ($_image_type, $_storage_types))
                        {
                             $_storage_exist = true;
                             
                             break;
                        }
                    }
                    */
                    $_image ['file'] = array ('content' => $_image_content, 'mime' => $_image_mime);
                    $_image ['types'] = array ($_image_type);
                    
                    if (!$_storage_exist) $this->_getMediaApi ()->create ($product_id, $_image);
                    //// else $this->_getMediaApi ()->update ($product_id, $_storage_file, $_image);
                }
            }
        }
        catch (Mage_Core_Exception $e)
        {
            // $this->_fault('data_invalid', $e->getMessage());

            $result [] = $e->getMessage ();
        }
    }

    $this->_getUtils ()->_assignProducts ();

    return $result;
}

public function items ($filters = null, $order = null, $limit = null /*, $store = null */)
{
    $collection = Mage::getModel ('catalog/product')->getCollection ()
        // ->addStoreFilter ($this->_getStoreId ($store))
        ->addAttributeToSelect ('name');

    /** @var $apiHelper Mage_Api_Helper_Data */
    $apiHelper = Mage::helper ('api');
    $filters = $apiHelper->parseFilters ($filters, $this->_filtersMap);

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

    $result = array ();

    foreach ($collection as $product)
    {
        $result [] = array(
            'product_id'   => $product->getId (),
/*
            'sku'          => $product->getSku (),
            'name'         => $product->getName (),
            'set'          => $product->getAttributeSetId (),
            'type'         => $product->getTypeId (),
            'category_ids' => $product->getCategoryIds (),
            'website_ids'  => $product->getWebsiteIds ()
*/
        );
    }

    return $result;
}


private function _getUtils ()
{
    return Mage::getModel ('erp/utils');
}

private function _getMediaApi ()
{
    return Mage::getModel ('catalog/product_attribute_media_api');
}

private function _getCoreResource ()
{
    return Mage::getSingleton ('core/resource');
}

}

