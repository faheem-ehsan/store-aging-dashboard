<?php
namespace App\Controllers;

use App\Models\ExportGoodsBatch;
use App\Models\ExportGoodsMaster;
use App\Models\ProductMaster;
use App\Models\StockBatch;

/**
 * Store Export Goods Aging: the same list, grading, add/edit/delete, CSV import
 * and bulk delete as Finish Goods Aging, on separate export_goods tables.
 * Add Batch picks products from Administration → Export Goods Master.
 */
class ExportGoodsController extends StockController
{
    public const MODULE = [
        'key' => 'eg',
        'base' => '/export-goods',
        'name' => 'Export Goods',
        'index_title' => 'Export Goods Aging',
        'code_label' => 'Product Code',
        'name_label' => 'Product Name',
        'value_label' => 'Stock Value',
        'value_summary_label' => 'Current Stock Value',
        'uses_product_master' => true,
        'master_name' => 'Export Goods Master',
        'master_item' => 'product',
        'audit_prefix' => 'EG_BATCH',
        'audit_entity' => 'export_goods_batch',
        'audit_bulk_entity' => 'export_goods_division_bulk_delete',
        'import_action' => 'EG_CSV_IMPORT',
        'save_hint' => ' The same Division + Product Code + Batch No may already exist.',
        'bulk_keeps' => 'Divisions, users, grading criteria, Finish Goods, Raw Material and Market Return data were not deleted.',
        'bulk_modal_keeps' => 'Divisions, users, grading criteria, Finish Goods, Raw Material and Market Return records remain unchanged.',
        'template_file' => 'export_goods_aging_import_template.csv',
        'template_example' => ['100409', 'SPRINGLEY 500 ML (1*12) EXPORT', '480', 'CS', '144000', 'EX180926', '18/Sep/2026', '18/Sep/2027', 'EXP01'],
        'uom_placeholder' => 'e.g. CS, CTN, PCS',
        'location_placeholder' => 'e.g. EXP01',
        'search_placeholder' => 'Product code, product name or batch no...',
    ];

    protected function masterModel(): ProductMaster
    {
        return new ExportGoodsMaster();
    }

    protected function batchModel(): StockBatch
    {
        return new ExportGoodsBatch();
    }
}
