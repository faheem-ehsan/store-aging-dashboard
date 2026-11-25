<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Division;
use App\Models\ProductMaster;
use App\Services\AuditLogger;

class ProductMasterController
{
    /** Per-master settings. RawMaterialMasterController reuses this controller and view. */
    public const MODULE = [
        'base' => '/admin/products',
        'title' => 'Product Master',
        'item' => 'Product',
        'item_lower' => 'product',
        'items' => 'Products',
        'code_label' => 'Product Code',
        'name_label' => 'Product Name',
        'audit_prefix' => 'PRODUCT_MASTER',
        'audit_entity' => 'product_master',
        'used_by' => 'the manual Add Batch form',
        'code_placeholder' => 'e.g. 100324',
        'name_placeholder' => 'e.g. JS Sparkling 500ml',
        'uom_placeholder' => 'e.g. CS, CTN, BTLS, BOX, EA',
    ];

    protected function m(string $key): string
    {
        return static::MODULE[$key];
    }

    protected function model(): ProductMaster
    {
        return new ProductMaster();
    }

    public function index(): void
    {
        Auth::requireSuperAdmin();

        $model = $this->model();
        $divisionId = max(0, (int)($_GET['division_id'] ?? 0));
        $search = trim((string)($_GET['q'] ?? ''));
        $editId = max(0, (int)($_GET['edit'] ?? 0));
        $editProduct = $editId > 0 ? $model->find($editId) : null;

        View::render('admin/products', [
            'master' => static::MODULE,
            'title' => $this->m('title'),
            'divisions' => (new Division())->all(true),
            'products' => $model->all($divisionId ?: null, $search),
            'selectedDivisionId' => $divisionId,
            'search' => $search,
            'editProduct' => $editProduct,
        ]);
    }

    public function store(): void
    {
        verify_csrf();
        $current = Auth::requireSuperAdmin();
        [$division, $code, $name, $uom] = $this->validatedInput();

        try {
            $model = $this->model();
            $id = $model->create((int)$division['id'], $code, $name, $uom);

            AuditLogger::log(
                $this->m('audit_prefix') . '_CREATE',
                'Added ' . $this->m('item_lower') . ' ' . $code . ' — ' . $name . ' to ' . $division['code'] . '.',
                $this->m('audit_entity'),
                $id,
                ['product_code'=>$code,'product_name'=>$name,'uom'=>$uom],
                $current,
                (int)$division['id'],
                (string)$division['code']
            );
            flash('success', $this->m('item') . ' added to the ' . $this->m('title') . '.');
        } catch (\Throwable $e) {
            flash('error', 'Unable to add ' . $this->m('item_lower') . '. The ' . $this->m('code_label') . ' may already exist in this Division.');
        }

        redirect($this->m('base') . '?division_id=' . (int)$division['id']);
    }

    public function update(): void
    {
        verify_csrf();
        $current = Auth::requireSuperAdmin();
        $id = max(0, (int)($_POST['id'] ?? 0));
        $model = $this->model();
        $before = $model->find($id);
        if (!$before) {
            flash('error', $this->m('title') . ' record not found.');
            redirect($this->m('base'));
        }

        [$division, $code, $name, $uom] = $this->validatedInput();

        try {
            $model->update($id, (int)$division['id'], $code, $name, $uom);
            AuditLogger::log(
                $this->m('audit_prefix') . '_UPDATE',
                'Updated ' . $this->m('title') . ' record ' . $code . ' — ' . $name . '.',
                $this->m('audit_entity'),
                $id,
                [
                    'before'=>[
                        'division'=>$before['division_code'] ?? null,
                        'product_code'=>$before['product_code'],
                        'product_name'=>$before['product_name'],
                        'uom'=>$before['uom'],
                    ],
                    'after'=>[
                        'division'=>$division['code'],
                        'product_code'=>$code,
                        'product_name'=>$name,
                        'uom'=>$uom,
                    ],
                ],
                $current,
                (int)$division['id'],
                (string)$division['code']
            );
            flash('success', $this->m('title') . ' record updated.');
        } catch (\Throwable $e) {
            flash('error', 'Unable to update ' . $this->m('item_lower') . '. The ' . $this->m('code_label') . ' may already exist in this Division.');
        }

        redirect($this->m('base') . '?division_id=' . (int)$division['id']);
    }

    public function toggle(): void
    {
        verify_csrf();
        $current = Auth::requireSuperAdmin();
        $id = max(0, (int)($_POST['id'] ?? 0));
        $model = $this->model();
        $before = $model->find($id);
        if (!$before) {
            flash('error', $this->m('title') . ' record not found.');
            redirect($this->m('base'));
        }

        $model->toggle($id);
        $after = $model->find($id);
        AuditLogger::log(
            $this->m('audit_prefix') . '_STATUS',
            (!empty($after['is_active']) ? 'Enabled' : 'Disabled') . ' ' . $this->m('title') . ' record ' . $after['product_code'] . '.',
            $this->m('audit_entity'),
            $id,
            ['old_status'=>!empty($before['is_active'])?'active':'disabled','new_status'=>!empty($after['is_active'])?'active':'disabled'],
            $current,
            (int)$after['division_id'],
            (string)$after['division_code']
        );
        flash('success', $this->m('title') . ' status updated.');
        redirect($this->m('base') . '?division_id=' . (int)$after['division_id']);
    }

    public function delete(): void
    {
        verify_csrf();
        $current = Auth::requireSuperAdmin();
        $id = max(0, (int)($_POST['id'] ?? 0));
        $model = $this->model();
        $product = $model->find($id);
        if (!$product) {
            flash('error', $this->m('title') . ' record not found.');
            redirect($this->m('base'));
        }

        $model->delete($id);
        AuditLogger::log(
            $this->m('audit_prefix') . '_DELETE',
            'Deleted ' . $this->m('title') . ' record ' . $product['product_code'] . ' — ' . $product['product_name'] . '.',
            $this->m('audit_entity'),
            $id,
            ['product_code'=>$product['product_code'],'product_name'=>$product['product_name'],'uom'=>$product['uom']],
            $current,
            (int)$product['division_id'],
            (string)$product['division_code']
        );
        flash('success', $this->m('item') . ' removed from the ' . $this->m('title') . '. Existing stock batches are unchanged.');
        redirect($this->m('base') . '?division_id=' . (int)$product['division_id']);
    }

    private function validatedInput(): array
    {
        $divisionId = max(0, (int)($_POST['division_id'] ?? 0));
        $code = trim((string)($_POST['product_code'] ?? ''));
        $name = trim((string)($_POST['product_name'] ?? ''));
        $uom = strtoupper(trim((string)($_POST['uom'] ?? '')));

        $division = $divisionId > 0 ? (new Division())->find($divisionId) : null;
        if (!$division || !(int)$division['is_active']) {
            flash('error', 'Select an active Division.');
            redirect($this->m('base'));
        }
        if ($code === '' || $name === '' || $uom === '') {
            flash('error', 'Division, ' . $this->m('code_label') . ', ' . $this->m('name_label') . ' and Unit of Measure are required.');
            redirect($this->m('base') . '?division_id=' . $divisionId);
        }
        if (strlen($code) > 60 || strlen($name) > 255 || strlen($uom) > 20) {
            flash('error', $this->m('code_label') . ', ' . $this->m('name_label') . ' or Unit of Measure is too long.');
            redirect($this->m('base') . '?division_id=' . $divisionId);
        }

        return [$division, $code, $name, $uom];
    }
}
