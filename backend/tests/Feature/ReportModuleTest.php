<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Supply;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        // Create Admin User
        $this->adminUser = User::factory()->create([
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('Administrador');

        // Create Regular User (without reports permission)
        $this->regularUser = User::factory()->create([
            'is_active' => true,
        ]);
    }

    /**
     * Test reports endpoints are guarded from unauthenticated requests.
     */
    public function test_unauthenticated_user_cannot_access_reports(): void
    {
        $this->getJson('/api/v1/reports/summary')->assertStatus(401);
        $this->getJson('/api/v1/reports/sales')->assertStatus(401);
        $this->getJson('/api/v1/reports/products')->assertStatus(401);
        $this->getJson('/api/v1/reports/supplies')->assertStatus(401);
        $this->getJson('/api/v1/reports/production')->assertStatus(401);
    }

    /**
     * Test reports endpoints are guarded from unauthorized users.
     */
    public function test_unauthorized_user_cannot_access_reports(): void
    {
        Sanctum::actingAs($this->regularUser);

        $this->getJson('/api/v1/reports/summary')->assertStatus(403);
    }

    /**
     * Test authorized admin can get summary metrics.
     */
    public function test_authorized_admin_can_access_summary_report(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create dummy supply
        Supply::create([
            'name' => 'Azúcar',
            'unit' => 'kg',
            'stock' => 1.00,
            'minimum_stock' => 5.00,
            'average_cost' => 1.20,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/reports/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'period_sales',
                    'registered_orders',
                    'products_sold',
                    'registered_customers',
                    'pending_orders',
                    'delivered_orders',
                    'cancelled_orders',
                    'critical_stock',
                ]
            ]);
    }

    /**
     * Test sales reports return metrics and orders collection.
     */
    public function test_authorized_admin_can_access_sales_report(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/reports/sales');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_sales_count',
                    'total_orders_count',
                    'total_revenue',
                    'average_ticket',
                    'status_counts',
                ],
                'orders' => [
                    'data',
                    'links',
                    'meta'
                ]
            ]);
    }

    /**
     * Test supplies report returns inventory semaphoric states.
     */
    public function test_authorized_admin_can_access_supplies_report(): void
    {
        Sanctum::actingAs($this->adminUser);

        Supply::create([
            'name' => 'Fresa',
            'unit' => 'kg',
            'stock' => 0.50,
            'minimum_stock' => 10.00,
            'average_cost' => 3.00,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/reports/supplies');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Fresa',
                'status' => 'Stock crítico'
            ]);
    }

    /**
     * Test production report returns stats and paginated orders.
     */
    public function test_authorized_admin_can_access_production_report(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/reports/production');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'status_counts'
                ],
                'orders' => [
                    'data',
                    'links',
                    'meta'
                ]
            ]);
    }

    /**
     * Test exporting reports to CSV format.
     */
    public function test_export_reports_to_csv(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->get('/api/v1/reports/sales/export-excel');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
