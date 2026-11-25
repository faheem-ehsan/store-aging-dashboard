<?php
namespace App\Controllers;

use App\Models\ExportGoodsMaster;
use App\Models\ProductMaster;

/**
 * Administration → Export Goods Master: approved export products per Division,
 * used by the Export Goods Aging "Add Batch" form. Same screen and behaviour
 * as Product Master, stored in export_goods_master.
 */
class ExportGoodsMasterController extends ProductMasterController
{
    public const MODULE = [
        'base' => '/admin/export-goods',
        'title' => 'Export Goods Master',
        'item' => 'Product',
        'item_lower' => 'product',
        'items' => 'Products',
        'code_label' => 'Product Code',
        'name_label' => 'Product Name',
        'audit_prefix' => 'EG_MASTER',
        'audit_entity' => 'export_goods_master',
        'used_by' => 'the Export Goods Aging Add Batch form',
        'code_placeholder' => 'e.g. 100409',
        'name_placeholder' => 'e.g. Springley 500ml Export',
        'uom_placeholder' => 'e.g. CS, CTN, PCS',
    ];

    protected function model(): ProductMaster
    {
        return new ExportGoodsMaster();
    }
}
