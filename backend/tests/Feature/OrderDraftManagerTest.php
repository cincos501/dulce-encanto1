<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\AI\Orders\OrderDraftManager;
use App\AI\Orders\OrderDraft;
use App\Repositories\WhatsAppSessionRepositoryInterface;
use Tests\TestCase;

class OrderDraftManagerTest extends TestCase
{
    protected OrderDraftManager $manager;
    protected WhatsAppSessionRepositoryInterface $sessionRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // Bind an in-memory session repository to isolate test from Redis dependencies
        $this->app->singleton(WhatsAppSessionRepositoryInterface::class, function () {
            return new class implements WhatsAppSessionRepositoryInterface {
                protected array $store = [];

                public function get(string $phone): ?array
                {
                    return $this->store[$phone] ?? null;
                }

                public function set(string $phone, array $data, int $ttl = 3600): void
                {
                    $this->store[$phone] = $data;
                }

                public function delete(string $phone): void
                {
                    unset($this->store[$phone]);
                }
            };
        });

        $this->sessionRepository = $this->app->make(WhatsAppSessionRepositoryInterface::class);
        $this->manager = new OrderDraftManager($this->sessionRepository);
    }

    public function test_can_create_retrieve_and_clear_draft(): void
    {
        $phone = '59170012345';
        $this->manager->clearDraft($phone);

        $this->assertFalse($this->manager->exists($phone));

        $draft = $this->manager->getDraft($phone);
        $this->assertInstanceOf(OrderDraft::class, $draft);
        $this->assertEquals($phone, $draft->customerPhone);
        $this->assertEmpty($draft->items);
        $this->assertEquals(0.0, $draft->total);

        $this->assertTrue($this->manager->exists($phone));

        $this->manager->clearDraft($phone);
        $this->assertFalse($this->manager->exists($phone));
    }

    public function test_can_add_item_and_sum_quantities(): void
    {
        $phone = '59170012345';
        $this->manager->clearDraft($phone);

        // Add item
        $draft = $this->manager->addItem($phone, 1, 'Torta Chocolate', 10, 'Mediana', 2, 100.0);
        $this->assertCount(1, $draft->items);
        $this->assertEquals(200.0, $draft->total);
        $this->assertEquals(2, $draft->items[0]->quantity);

        // Add identical item (sums quantity)
        $draft = $this->manager->addItem($phone, 1, 'Torta Chocolate', 10, 'Mediana', 1, 100.0);
        $this->assertCount(1, $draft->items);
        $this->assertEquals(300.0, $draft->total);
        $this->assertEquals(3, $draft->items[0]->quantity);

        // Add different variant
        $draft = $this->manager->addItem($phone, 1, 'Torta Chocolate', 11, 'Grande', 1, 150.0);
        $this->assertCount(2, $draft->items);
        $this->assertEquals(450.0, $draft->total);

        $this->manager->clearDraft($phone);
    }

    public function test_can_modify_quantity_and_remove_item(): void
    {
        $phone = '59170012345';
        $this->manager->clearDraft($phone);

        $this->manager->addItem($phone, 1, 'Torta Chocolate', 10, 'Mediana', 2, 100.0);
        
        // Update quantity
        $draft = $this->manager->updateQuantity($phone, 10, 5);
        $this->assertEquals(500.0, $draft->total);
        $this->assertEquals(5, $draft->items[0]->quantity);

        // Remove item
        $draft = $this->manager->removeItem($phone, 10);
        $this->assertEmpty($draft->items);
        $this->assertEquals(0.0, $draft->total);

        $this->manager->clearDraft($phone);
    }

    public function test_can_add_and_remove_extras(): void
    {
        $phone = '59170012345';
        $this->manager->clearDraft($phone);

        $this->manager->addItem($phone, 1, 'Torta Chocolate', 10, 'Mediana', 2, 100.0);

        // Add extra
        $draft = $this->manager->addExtra($phone, 10, 100, 'Chispas', 15.0);
        // Extras price (15.0) added to unit price (100.0) = 115.0 * 2 quantity = 230.0
        $this->assertEquals(230.0, $draft->total);
        $this->assertCount(1, $draft->items[0]->extras);

        // Remove extra
        $draft = $this->manager->removeExtra($phone, 10, 100);
        $this->assertEquals(200.0, $draft->total);
        $this->assertEmpty($draft->items[0]->extras);

        $this->manager->clearDraft($phone);
    }
}
