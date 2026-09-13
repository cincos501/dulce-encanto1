<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status')->default('Pendiente')->after('status');
            $table->string('delivery_type')->default('RECOJO_TIENDA')->after('delivery_date');
            $table->text('delivery_address')->nullable()->after('delivery_type');
            $table->text('delivery_notes')->nullable()->after('delivery_address');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            if (! Schema::hasColumn('product_variants', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('extras', function (Blueprint $table) {
            if (! Schema::hasColumn('extras', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'delivery_type', 'delivery_address', 'delivery_notes']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('extras', function (Blueprint $table) {
            if (Schema::hasColumn('extras', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
