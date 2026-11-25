<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Division;
use App\Models\ProductMaster;
use App\Services\AuditLogger;

class ProductMasterController
{
    public function index(): void
    {
        Auth::requireSuperAdmin();

        $model = new ProductMaster();
        $divisionId = max(0, (int)($_GET['division_id'] ?? 0));
        $search = trim((string)($_GET['q'] ?? ''));
        $editId = max(0, (int)($_GET['edit'] ?? 0));
        $editProduct = $editId > 0 ? $model->find($editId) : null;

        View::render('admin/products', [
            'title' => 'Product Master',
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
            $model = new ProductMaster();
            $id = $model->create((int)$division['id'], $code, $name, $uom);

            AuditLogger::log(
                'PRODUCT_MASTER_CREATE',
                'Added product ' . $code . ' — ' . $name . ' to ' . $division['code'] . '.',
                'product_master',
                $id,
                ['product_code'=>$code,'product_name'=>$name,'uom'=>$uom],
                $current,
                (int)$division['id'],
                (string)$division['code']
            );
            flash('success', 'Product added to the Product Master.');
        } catch (\Throwable $e) {
            flash('error', 'Unable to add product. The Product Code may already exist in this Division.');
        }

        redirect('/admin/products?division_id=' . (int)$division['id']);
    }

    public function update(): void
    {
        verify_csrf();
        $current = Auth::requireSuperAdmin();
        $id = max(0, (int)($_POST['id'] ?? 0));
        $model = new ProductMaster();
        $before = $model->find($id);
        if (!$before) {
            flash('error', 'Product Master record not found.');
            redirect('/admin/products');
        }

        [$division, $code, $name, $uom] = $this->validatedInput();

        try {
            $model->update($id, (int)$division['id'], $code, $name, $uom);
            AuditLogger::log(
                'PRODUCT_MASTER_UPDATE',
                'Updated Product Master record ' . $code . ' — ' . $name . '.',
                'product_master',
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
            flash('success', 'Product Master record updated.');
        } catch (\Throwable $e) {
            flash('error', 'Unable to update product. The Product Code may already exist in this Division.');
        }

        redirect('/admin/products?division_id=' . (int)$division['id']);
    }

    public function toggle(): void
    {
        verify_csrf();
        $current = Auth::requireSuperAdmin();
        $id = max(0, (int)($_POST['id'] ?? 0));
        $model = new ProductMaster();
        $before = $model->find($id);
        if (!$before) {
            flash('error', 'Product Master record not found.');
            redirect('/admin/products');
        }

        $model->toggle($id);
        $after = $model->find($id);
        AuditLogger::log(
            'PRODUCT_MASTER_STATUS',
            (!empty($after['is_active']) ? 'Enabled' : 'Disabled') . ' Product Master record ' . $after['product_code'] . '.',
            'product_master',
            $id,
            ['old_status'=>!empty($before['is_active'])?'active':'disabled','new_status'=>!empty($after['is_active'])?'active':'disabled'],
            $current,
            (int)$after['division_id'],
            (string)$after['division_code']
        );
        flash('success', 'Product Master status updated.');
        redirect('/admin/products?division_id=' . (int)$after['division_id']);
    }

    public function delete(): void
    {
        verify_csrf();
        $current = Auth::requireSuperAdmin();
        $id = max(0, (int)($_POST['id'] ?? 0));
        $model = new ProductMaster();
        $product = $model->find($id);
        if (!$product) {
            flash('error', 'Product Master record not found.');
            redirect('/admin/products');
        }

        $model->delete($id);
        AuditLogger::log(
            'PRODUCT_MASTER_DELETE',
            'Deleted Product Master record ' . $product['product_code'] . ' — ' . $product['product_name'] . '.',
            'product_master',
            $id,
            ['product_code'=>$product['product_code'],'product_name'=>$product['product_name'],'uom'=>$product['uom']],
            $current,
            (int)$product['division_id'],
            (string)$product['division_code']
        );
        flash('success', 'Product removed from the Product Master. Existing stock batches are unchanged.');
        redirect('/admin/products?division_id=' . (int)$product['division_id']);
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
            redirect('/admin/products');
        }
        if ($code === '' || $name === '' || $uom === '') {
            flash('error', 'Division, Product Code, Product Name and Unit of Measure are required.');
            redirect('/admin/products?division_id=' . $divisionId);
        }
        if (strlen($code) > 60 || strlen($name) > 255 || strlen($uom) > 20) {
            flash('error', 'Product Code, Product Name or Unit of Measure is too long.');
            redirect('/admin/products?division_id=' . $divisionId);
        }

        return [$division, $code, $name, $uom];
    }
}
