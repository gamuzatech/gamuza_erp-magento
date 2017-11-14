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

function addOrUpdateAttribute ($installer, $model_name, $attribute_code, $label)
{
    $installer->addAttribute ($model_name, $attribute_code, array(
        'group'         => 'ERP',
        'input'         => 'text',
        'type'          => 'text',
        'label'         => $label,
        'backend'       => '',
        'required'      => 1,
        'user_defined'  => 0,
        'unique'        => 1,
        'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    ));
    $installer->updateAttribute ($model_name, $attribute_code, 'is_configurable', 0);
    $installer->updateAttribute ($model_name, $attribute_code, 'is_visible', 1);
}

function addCreatedUpdatedAt ($installer, $model_name, $conditions)
{
    $table = $installer->getTable ($model_name);
    
    $installer->getConnection ()
        ->addColumn ($table, 'created_at', array(
            'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
            'default' => null,
            'nullable' => true,
            'comment' => 'Created At',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'updated_at', array(
            'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
            'default' => null,
            'nullable' => true,
            'comment' => 'Updated At',
        ));
    
    $now = Mage::getModel ('core/date')->gmtDate ();
    
$sqlBlock = <<< SQLBLOCK
UPDATE {$installer->getTable ($model_name)} SET created_at = '{$now}' WHERE {$conditions};
SQLBLOCK;
    
    $installer->run ($sqlBlock);
}

function addERPProductsAssociations ($installer, $model_name)
{
    $sqlBlock = <<< SQLBLOCK
CREATE TABLE IF NOT EXISTS {$installer->getTable ($model_name)}
(
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT='Gamuza ERP - Products Associations';
SQLBLOCK;

    $installer->run ($sqlBlock);

    $table = $installer->getTable ($model_name);
    
    $installer->getConnection ()
        ->addColumn ($table, 'parent_sku', array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length' => 255,
            'nullable' => false,
            'comment' => 'Parent SKU',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'sku', array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length' => 255,
            'nullable' => false,
            'comment' => 'SKU',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'is_modified', array(
            'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
            'unsigned' => true,
            'nullable' => true,
            'comment' => 'Is Modified',
        ));
}

$installer = $this;
$installer->startSetup ();

addOrUpdateAttribute ($installer, 'catalog_category', 'erp_category_id', 'ID da Categoria para ERP');
addOrUpdateAttribute ($installer, 'catalog_product',  'erp_product_id',  'ID do Produto para ERP');

addCreatedUpdatedAt ($installer, 'core_website',     'website_id > 0');
addCreatedUpdatedAt ($installer, 'core_store_group', 'group_id > 0');
addCreatedUpdatedAt ($installer, 'core_store',       'store_id > 0');
addCreatedUpdatedAt ($installer, 'customer_group',   'customer_group_id > 0');

addERPProductsAssociations ($installer, 'gamuza_erp_products_associations');

$coreConfig = Mage::getModel ('core/config');

$coreConfig->saveConfig ('api/config/charset',            'UTF-8');
$coreConfig->saveConfig ('api/config/session_timeout',    '86400');
$coreConfig->saveConfig ('api/config/compliance_wsi',     '1');
$coreConfig->saveConfig ('api/config/wsdl_cache_enabled', '1');

$coreConfig->saveConfig ('erp/attributes/category_id', Gamuza_ERP_Helper_Data::ATTRIBUTE_CATEGORY_ID);
$coreConfig->saveConfig ('erp/attributes/product_id',  Gamuza_ERP_Helper_Data::ATTRIBUTE_PRODUCT_ID);

$installer->endSetup ();

