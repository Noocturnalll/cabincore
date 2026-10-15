<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_references', function (Blueprint $table) {
            $table->id();
            $table->string('tab', 80);
            $table->unsignedInteger('row_no');
            $table->json('cells');            // header name => text, in the sheet's own column order
            $table->json('links')->nullable(); // header name => [label, url] for cells that carry a hyperlink
            $table->text('search');           // lower-cased text of the row, for the search box
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->index(['tab', 'row_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_references');
    }
};
