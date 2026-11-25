<?php
namespace App\Controllers;

use App\Models\RawMaterialBatch;
use App\Models\StockBatch;

/**
 * Raw Material Aging: the same list, grading, add/edit/delete, CSV import and
 * bulk delete as Finish Goods Aging, on separate raw_material tables.
 * Materials are entered as free text (there is no Raw Material master yet).
 */
class RawMaterialController extends StockController
{
    public const MODULE = [
        'key' => 'rm',
        'base' => '/raw-materials',
        'name' => 'Raw Material',
        'index_title' => 'Raw Material Aging',
        'code_label' => 'Material Code',
        'name_label' => 'Material Name',
        'value_label' => 'Stock Value',
        'value_summary_label' => 'Current Stock Value',
        'uses_product_master' => false,
        'audit_prefix' => 'RM_BATCH',
        'audit_entity' => 'raw_material_batch',
        'audit_bulk_entity' => 'raw_material_division_bulk_delete',
        'import_action' => 'RM_CSV_IMPORT',
        'save_hint' => ' The same Division + Material Code + Batch No may already exist.',
        'bulk_keeps' => 'Divisions, users, grading criteria, Finish Goods and Market Return data were not deleted.',
        'bulk_modal_keeps' => 'Divisions, users, grading criteria, Finish Goods and Market Return records remain unchanged.',
        'template_file' => 'raw_material_aging_import_template.csv',
        'template_example' => ['RM-10021', 'SUGAR REFINED', '1250', 'KG', '187500', 'SUG180926', '18/Sep/2026', '18/Sep/2027', 'RM01'],
        'uom_placeholder' => 'e.g. KG, LTR, PCS',
        'location_placeholder' => 'e.g. RM01',
        'search_placeholder' => 'Material code, material name or batch no...',
    ];

    protected function batchModel(): StockBatch
    {
        return new RawMaterialBatch();
    }
}
