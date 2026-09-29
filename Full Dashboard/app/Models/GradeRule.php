<?php
namespace App\Models;

use App\Core\Database;

class GradeRule
{
    public function all(): array
    {
        return Database::connection()->query(
            'SELECT * FROM grade_rules ORDER BY division_id IS NULL, division_id, sort_order'
        )->fetchAll();
    }
}
