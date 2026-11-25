<?php
namespace App\Controllers;

use App\Models\ProductMaster;
use App\Models\RawMaterialMaster;

/**
 * Administration → Raw Material Master: approved raw materials per Division,
 * used by the Raw Material Aging "Add Batch" form. Same screen and behaviour
 * as Product Master, stored in raw_material_master.
 */
class RawMaterialMasterController extends ProductMasterController
{
    public const MODULE = [
        'base' => '/admin/raw-materials',
        'title' => 'Raw Material Master',
        'item' => 'Material',
        'item_lower' => 'material',
        'items' => 'Materials',
        'code_label' => 'Material Code',
        'name_label' => 'Material Name',
        'audit_prefix' => 'RM_MASTER',
        'audit_entity' => 'raw_material_master',
        'used_by' => 'the Raw Material Aging Add Batch form',
        'code_placeholder' => 'e.g. RM-10021',
        'name_placeholder' => 'e.g. Sugar Refined',
        'uom_placeholder' => 'e.g. KG, G, LTR, ML, PCS',
    ];

    protected function model(): ProductMaster
    {
        return new RawMaterialMaster();
    }
}
