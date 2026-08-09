<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. View for most sold products and variants
        DB::statement("DROP VIEW IF EXISTS vw_most_sold_products");
        DB::statement("
            CREATE VIEW vw_most_sold_products AS
            SELECT 
                p.name AS product_name,
                pv.name AS variant_name,
                oi.quantity AS quantity_sold,
                (oi.quantity * oi.price) AS total_generated,
                o.created_at AS order_created_at,
                o.status AS order_status
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN product_variants pv ON oi.product_variant_id = pv.id
            JOIN products p ON pv.product_id = p.id
        ");

        // 2. View for supplies inventory status and mapping
        DB::statement("DROP VIEW IF EXISTS vw_supplies_status");
        DB::statement("
            CREATE VIEW vw_supplies_status AS
            SELECT 
                id,
                name,
                stock,
                unit,
                minimum_stock,
                CASE 
                    WHEN stock <= minimum_stock * 0.25 OR stock <= 0 THEN 'Stock crítico'
                    WHEN stock <= minimum_stock THEN 'Stock bajo'
                    ELSE 'Stock suficiente'
                END AS status
            FROM supplies
        ");

        // 3. View for orders summary metrics and product item count
        DB::statement("DROP VIEW IF EXISTS vw_orders_dashboard_summary");
        DB::statement("
            CREATE VIEW vw_orders_dashboard_summary AS
            SELECT 
                o.id AS order_id,
                o.total AS total,
                o.status AS status,
                o.created_at AS created_at,
                COALESCE((SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.order_id = o.id), 0) AS products_count
            FROM orders o
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS vw_orders_dashboard_summary");
        DB::statement("DROP VIEW IF EXISTS vw_supplies_status");
        DB::statement("DROP VIEW IF EXISTS vw_most_sold_products");
    }
};
