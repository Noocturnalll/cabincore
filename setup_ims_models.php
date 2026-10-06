<?php

$models = [
    'Category' => "protected \$table = 'ims_categories';\n    protected \$guarded = [];",
    'Unit' => "protected \$table = 'ims_units';\n    protected \$guarded = [];",
    'Location' => "protected \$table = 'ims_locations';\n    protected \$guarded = [];",
    'Supplier' => "protected \$table = 'ims_suppliers';\n    protected \$guarded = [];",
    'AircraftType' => "protected \$table = 'ims_aircraft_types';\n    protected \$guarded = [];",
    'Item' => "protected \$table = 'ims_items';\n    protected \$guarded = [];\n    public function category() { return \$this->belongsTo(Category::class); }\n    public function unit() { return \$this->belongsTo(Unit::class); }\n    public function location() { return \$this->belongsTo(Location::class, 'default_location_id'); }",
    'ItemAlternate' => "protected \$table = 'ims_item_alternates';\n    protected \$guarded = [];",
    'ItemSerial' => "protected \$table = 'ims_item_serials';\n    protected \$guarded = [];",
    'Stock' => "protected \$table = 'ims_stocks';\n    protected \$guarded = [];\n    public function item() { return \$this->belongsTo(Item::class); }\n    public function location() { return \$this->belongsTo(Location::class); }",
    'StockMovement' => "protected \$table = 'ims_stock_movements';\n    protected \$guarded = [];",
    'Transaction' => "protected \$table = 'ims_transactions';\n    protected \$guarded = [];\n    public function items() { return \$this->hasMany(TransactionItem::class); }",
    'TransactionItem' => "protected \$table = 'ims_transaction_items';\n    protected \$guarded = [];\n    public function item() { return \$this->belongsTo(Item::class); }",
    'Approval' => "protected \$table = 'ims_approvals';\n    protected \$guarded = [];",
    'RepairWaiting' => "protected \$table = 'ims_repair_waiting';\n    protected \$guarded = [];",
    'RepairInProgress' => "protected \$table = 'ims_repair_in_progress';\n    protected \$guarded = [];",
    'RepairCompleted' => "protected \$table = 'ims_repair_completed';\n    protected \$guarded = [];",
    'RepairLog' => "protected \$table = 'ims_repair_logs';\n    protected \$guarded = [];",
    'Attachment' => "protected \$table = 'ims_attachments';\n    protected \$guarded = [];",
    'DocumentSequence' => "protected \$table = 'ims_document_sequences';\n    protected \$guarded = [];",
];

if (!is_dir(__DIR__ . '/app/Models/Ims')) {
    mkdir(__DIR__ . '/app/Models/Ims', 0777, true);
}

foreach ($models as $name => $body) {
    $content = <<<PHP
<?php

namespace App\Models\Ims;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class {$name} extends Model
{
    use SoftDeletes;
    
    {$body}
}
PHP;

    // Remove SoftDeletes from pivot/immutable tables
    if (in_array($name, ['Stock', 'StockMovement', 'TransactionItem', 'RepairLog', 'Attachment', 'DocumentSequence'])) {
        $content = str_replace("use Illuminate\Database\Eloquent\SoftDeletes;\n\n", "", $content);
        $content = str_replace("use SoftDeletes;\n    \n    ", "", $content);
    }
    
    file_put_contents(__DIR__ . "/app/Models/Ims/{$name}.php", $content);
}

echo "All models created successfully.\n";
