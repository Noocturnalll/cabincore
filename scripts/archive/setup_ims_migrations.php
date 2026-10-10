<?php

$migrations = [
    'create_ims_categories_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('ims_categories')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('ata_chapter')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_categories'); }
};
PHP,

    'create_ims_units_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_units', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_units'); }
};
PHP,

    'create_ims_locations_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('ims_locations')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type'); // warehouse|rack|shelf|bin|repair_area|quarantine
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_locations'); }
};
PHP,

    'create_ims_suppliers_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type'); // supplier|repair_vendor|both
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_suppliers'); }
};
PHP,

    'create_ims_aircraft_types_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_aircraft_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('manufacturer');
            $table->string('model');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_aircraft_types'); }
};
PHP,

    'create_ims_items_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_items', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique();
            $table->string('name');
            $table->text('description');
            $table->foreignId('category_id')->constrained('ims_categories');
            $table->foreignId('unit_id')->constrained('ims_units');
            $table->string('manufacturer')->nullable();
            $table->string('ata_chapter')->nullable();
            $table->string('tracking_type')->default('quantity');
            $table->boolean('is_rotable')->default(false);
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->nullable();
            $table->foreignId('default_location_id')->nullable()->constrained('ims_locations');
            $table->integer('shelf_life_days')->nullable();
            $table->boolean('is_hazmat')->default(false);
            $table->string('image_path')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('part_number');
        });
    }
    public function down(): void { Schema::dropIfExists('ims_items'); }
};
PHP,

    'create_ims_item_alternates_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_item_alternates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('ims_items')->cascadeOnDelete();
            $table->string('part_number');
            $table->string('relation'); // alternate|interchangeable|superseded_by|supersedes
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['item_id', 'part_number']);
        });
    }
    public function down(): void { Schema::dropIfExists('ims_item_alternates'); }
};
PHP,

    'create_ims_item_aircraft_type_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_item_aircraft_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('ims_items')->cascadeOnDelete();
            $table->foreignId('aircraft_type_id')->constrained('ims_aircraft_types')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_item_aircraft_type'); }
};
PHP,

    'create_ims_item_serials_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_item_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('ims_items')->cascadeOnDelete();
            $table->string('serial_number');
            $table->string('batch_no')->nullable();
            $table->string('condition');
            $table->string('status'); // in_stock|reserved|issued|in_repair|scrapped
            $table->foreignId('location_id')->nullable()->constrained('ims_locations')->nullOnDelete();
            $table->decimal('tsn_hours', 10, 2)->nullable();
            $table->integer('csn_cycles')->nullable();
            $table->decimal('tso_hours', 10, 2)->nullable();
            $table->integer('cso_cycles')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('certificate_no')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['item_id', 'serial_number']);
        });
    }
    public function down(): void { Schema::dropIfExists('ims_item_serials'); }
};
PHP,

    'create_ims_stocks_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('ims_items')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('ims_locations')->cascadeOnDelete();
            $table->unsignedInteger('qty_on_hand')->default(0);
            $table->unsignedInteger('qty_reserved')->default(0);
            $table->timestamps();
            
            $table->unique(['item_id', 'location_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('ims_stocks'); }
};
PHP,

    'create_ims_transactions_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type'); // out|in|adjustment|transfer|repair_in
            $table->string('status'); // draft|pending_approval|approved|rejected|cancelled|completed
            $table->string('usage_type')->nullable(); // consume|loan
            $table->date('expected_return_date')->nullable();
            $table->text('purpose_description');
            $table->string('reference_no')->nullable();
            $table->string('aircraft_registration')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('divisions');
            $table->foreignId('supplier_id')->nullable()->constrained('ims_suppliers');
            $table->string('source')->nullable();
            $table->foreignId('requested_by')->constrained('users');
            $table->dateTime('requested_at');
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->dateTime('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->string('picked_up_by_name')->nullable();
            $table->dateTime('picked_up_at')->nullable();
            $table->text('handover_note')->nullable();
            $table->foreignId('handed_over_by')->nullable()->constrained('users');
            $table->foreignId('parent_transaction_id')->nullable()->constrained('ims_transactions');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_transactions'); }
};
PHP,

    'create_ims_transaction_items_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('ims_transactions')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('ims_items');
            $table->foreignId('serial_id')->nullable()->constrained('ims_item_serials');
            $table->foreignId('location_id')->nullable()->constrained('ims_locations');
            $table->foreignId('to_location_id')->nullable()->constrained('ims_locations');
            $table->unsignedInteger('qty');
            $table->string('condition')->nullable();
            $table->string('batch_no')->nullable();
            $table->integer('system_qty')->nullable();
            $table->integer('actual_qty')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('returned_qty')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_transaction_items'); }
};
PHP,

    'create_ims_stock_movements_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('ims_items');
            $table->foreignId('location_id')->constrained('ims_locations');
            $table->foreignId('serial_id')->nullable()->constrained('ims_item_serials');
            $table->string('movement_type'); 
            $table->integer('qty_change');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->foreignId('transaction_id')->nullable()->constrained('ims_transactions');
            $table->string('repair_code')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['item_id', 'created_at']);
            $table->index(['location_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('ims_stock_movements'); }
};
PHP,

    'create_ims_approvals_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_approvals', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable');
            $table->integer('level')->default(1);
            $table->string('required_role')->nullable();
            $table->foreignId('approver_id')->nullable()->constrained('users');
            $table->string('status'); // pending|approved|rejected|skipped
            $table->text('note')->nullable();
            $table->dateTime('acted_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_approvals'); }
};
PHP,

    'create_ims_repair_waiting_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_repair_waiting', function (Blueprint $table) {
            $table->id();
            $table->string('repair_code')->index();
            $table->foreignId('item_id')->constrained('ims_items');
            $table->foreignId('serial_id')->nullable()->constrained('ims_item_serials');
            $table->integer('qty')->default(1);
            $table->text('fault_description');
            $table->string('priority');
            $table->foreignId('origin_transaction_id')->nullable()->constrained('ims_transactions');
            $table->foreignId('location_id')->constrained('ims_locations');
            $table->dateTime('received_at');
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->string('source');
            $table->string('aircraft_registration')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('ims_suppliers');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_repair_waiting'); }
};
PHP,

    'create_ims_repair_in_progress_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_repair_in_progress', function (Blueprint $table) {
            $table->id();
            $table->string('repair_code')->index();
            $table->foreignId('item_id')->constrained('ims_items');
            $table->foreignId('serial_id')->nullable()->constrained('ims_item_serials');
            $table->integer('qty')->default(1);
            $table->text('fault_description');
            $table->string('priority');
            $table->foreignId('origin_transaction_id')->nullable()->constrained('ims_transactions');
            $table->foreignId('location_id')->constrained('ims_locations');
            $table->dateTime('started_at');
            $table->foreignId('technician_id')->nullable()->constrained('users');
            $table->foreignId('vendor_id')->nullable()->constrained('ims_suppliers');
            $table->string('work_order_no')->nullable();
            $table->date('estimated_completion_date')->nullable();
            $table->text('progress_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_repair_in_progress'); }
};
PHP,

    'create_ims_repair_completed_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_repair_completed', function (Blueprint $table) {
            $table->id();
            $table->string('repair_code')->index();
            $table->foreignId('item_id')->constrained('ims_items');
            $table->foreignId('serial_id')->nullable()->constrained('ims_item_serials');
            $table->integer('qty')->default(1);
            $table->text('fault_description');
            $table->string('priority');
            $table->foreignId('origin_transaction_id')->nullable()->constrained('ims_transactions');
            $table->foreignId('location_id')->constrained('ims_locations');
            $table->dateTime('completed_at');
            $table->string('result');
            $table->text('findings')->nullable();
            $table->text('action_taken')->nullable();
            $table->string('certificate_no')->nullable();
            $table->string('repaired_by_name')->nullable();
            $table->dateTime('returned_to_stock_at')->nullable();
            $table->foreignId('return_transaction_id')->nullable()->constrained('ims_transactions');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_repair_completed'); }
};
PHP,

    'create_ims_repair_logs_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_repair_logs', function (Blueprint $table) {
            $table->id();
            $table->string('repair_code');
            $table->string('from_stage')->nullable();
            $table->string('to_stage');
            $table->foreignId('actor_id')->nullable()->constrained('users');
            $table->text('note')->nullable();
            $table->timestamps(); // created_at only is fine, or standard timestamps
        });
    }
    public function down(): void { Schema::dropIfExists('ims_repair_logs'); }
};
PHP,

    'create_ims_attachments_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->integer('size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_attachments'); }
};
PHP,

    'create_ims_document_sequences_table' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix');
            $table->string('period'); // YYYYMM
            $table->integer('last_number')->default(0);
            $table->timestamps();
            
            $table->unique(['prefix', 'period']);
        });
    }
    public function down(): void { Schema::dropIfExists('ims_document_sequences'); }
};
PHP,
];

$baseTime = time();
foreach ($migrations as $name => $content) {
    $filename = date('Y_m_d_His', $baseTime)."_$name.php";
    file_put_contents(__DIR__."/database/migrations/$filename", $content);
    $baseTime++; // increment 1 second to ensure order
}

echo "All migrations created successfully.\n";
