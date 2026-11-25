<?php
namespace App\Services;

use App\Controllers\ProductMasterController;
use App\Controllers\StockController;
use App\Models\StockBatch;

/**
 * Discovers the extra aging modules (Raw Material, Export Goods, and any added
 * later) so routes, the sidebar, Administration and the Dashboard never list
 * them by hand.
 *
 * To add a new aging module, create:
 *   - app/Controllers/<Name>Controller.php        extends StockController (with MODULE)
 *   - app/Models/<Name>Batch.php                   extends StockBatch (own tables)
 *   - app/Controllers/<Name>MasterController.php   extends ProductMasterController (optional)
 *   - app/Models/<Name>Master.php                  extends ProductMaster (optional)
 * Everything else picks it up automatically.
 */
final class AgingModuleRegistry
{
    private static ?array $modules = null;
    private static ?array $masters = null;

    /** Aging modules other than Finish Goods: MODULE['key'] => controller class, in MODULE['order']. */
    public static function modules(): array
    {
        return self::$modules ??= self::discover(StockController::class, 'key');
    }

    /** Masters other than Product Master: MODULE['base'] => controller class, in MODULE['order']. */
    public static function masters(): array
    {
        return self::$masters ??= self::discover(ProductMasterController::class, 'base');
    }

    /** The batch model for a module controller class. */
    public static function batchModel(string $controllerClass): StockBatch
    {
        return (new $controllerClass())->agingBatchModel();
    }

    private static function discover(string $parentClass, string $keyField): array
    {
        $classes = [];
        foreach (glob(APP_PATH . '/Controllers/*Controller.php') ?: [] as $file) {
            $class = 'App\\Controllers\\' . basename($file, '.php');
            if (class_exists($class) && is_subclass_of($class, $parentClass)) {
                $classes[] = $class;
            }
        }

        usort($classes, static fn(string $a, string $b): int =>
            [(int)($a::MODULE['order'] ?? 100), (string)$a::MODULE['base']]
            <=> [(int)($b::MODULE['order'] ?? 100), (string)$b::MODULE['base']]);

        $byKey = [];
        foreach ($classes as $class) {
            $byKey[(string)$class::MODULE[$keyField]] = $class;
        }
        return $byKey;
    }
}
