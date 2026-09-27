<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Division;
use App\Models\MarketReturnDivisionMapping;
use App\Services\AuditLogger;

class MarketReturnMappingController
{
    public function index(): void
    {
        $user = Auth::requireSuperAdmin();
        $filters = [
            'q' => trim((string)($_GET['q'] ?? '')),
            'division_id' => isset($_GET['division_id']) && $_GET['division_id'] !== '' ? (int)$_GET['division_id'] : null,
            'match_type' => trim((string)($_GET['match_type'] ?? '')),
        ];

        $rows = [];
        $databaseReady = true;
        try {
            $rows = (new MarketReturnDivisionMapping())->all($filters);
        } catch (\Throwable $e) {
            $databaseReady = false;
        }

        View::render('market_return/mapping', [
            'title' => 'Market Return Division Mapping',
            'user' => $user,
            'divisions' => (new Division())->all(true),
            'rows' => $rows,
            'filters' => $filters,
            'databaseReady' => $databaseReady,
        ]);
    }

    public function store(): void
    {
        verify_csrf();
        $user = Auth::requireSuperAdmin();
        $matchText = trim((string)($_POST['match_text'] ?? ''));
        $matchType = trim((string)($_POST['match_type'] ?? 'exact'));
        $divisionId = (int)($_POST['division_id'] ?? 0);

        try {
            if ($matchText === '') throw new \RuntimeException('Enter a product name or keyword.');
            if (!in_array($matchType, ['exact','contains'], true)) throw new \RuntimeException('Choose Exact Product or Contains Keyword.');
            $division = (new Division())->find($divisionId);
            if (!$division || !(int)$division['is_active']) throw new \RuntimeException('Choose an active division.');

            (new MarketReturnDivisionMapping())->add($matchText, $matchType, $divisionId, (int)$user['id'], 'manual');
            AuditLogger::log(
                'MARKET_RETURN_MAPPING_CREATE',
                'Added Market Return division mapping for ' . $matchText . '.',
                'market_return_division_mapping',
                null,
                ['match_text' => $matchText, 'match_type' => $matchType, 'division_id' => $divisionId, 'division_code' => $division['code']],
                $user,
                $divisionId,
                $division['code']
            );
            flash('success', 'Market Return division mapping saved. Future imports can use it automatically.');
        } catch (\Throwable $e) {
            flash('error', $e instanceof \RuntimeException ? $e->getMessage() : 'Unable to save mapping. Import database/migrations/008_add_market_return_division_mapping.sql first.');
        }
        redirect('/market-return/mapping');
    }

    public function delete(): void
    {
        verify_csrf();
        $user = Auth::requireSuperAdmin();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                (new MarketReturnDivisionMapping())->delete($id);
                AuditLogger::log('MARKET_RETURN_MAPPING_DELETE', 'Deleted a Market Return division mapping.', 'market_return_division_mapping', $id, null, $user);
                flash('success', 'Mapping deleted.');
            } catch (\Throwable $e) {
                flash('error', 'Unable to delete mapping.');
            }
        }
        redirect('/market-return/mapping');
    }
}
