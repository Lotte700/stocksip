<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('inventories', function (Blueprint $table) {
        // เพิ่มคอลัมน์โดยต่อท้าย outlet_id เดิม และอนุญาตให้ว่างได้
        $table->foreignId('from_outlet_id')->nullable()->after('outlet_id')->constrained('outlets')->onDelete('cascade');
    });
}

public function down()
{
    Schema::table('inventories', function (Blueprint $table) {
        $table->dropForeign(['from_outlet_id']);
        $table->dropColumn('from_outlet_id');
    });
}
};
