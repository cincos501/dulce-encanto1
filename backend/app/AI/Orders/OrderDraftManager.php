<?php

declare(strict_types=1);

namespace App\AI\Orders;

use App\Repositories\WhatsAppSessionRepositoryInterface;

class OrderDraftManager
{
    protected string $prefix = 'wa_order_draft:';

    public function __construct(
        protected WhatsAppSessionRepositoryInterface $sessionRepository
    ) {}

    /**
     * Check if a draft exists for the given phone number.
     */
    public function exists(string $phone): bool
    {
        return $this->sessionRepository->get($this->prefix . $phone) !== null;
    }

    /**
     * Create a new draft.
     */
    public function createDraft(string $phone): OrderDraft
    {
        $draft = new OrderDraft($phone);
        $this->saveDraft($draft);
        return $draft;
    }

    /**
     * Retrieve the draft or create one if it does not exist.
     */
    public function getDraft(string $phone): OrderDraft
    {
        $data = $this->sessionRepository->get($this->prefix . $phone);
        if ($data === null) {
            return $this->createDraft($phone);
        }
        return OrderDraft::fromArray($data);
    }

    /**
     * Save the draft back to Redis.
     */
    public function saveDraft(OrderDraft $draft): void
    {
        $draft->calculateTotals();
        $this->sessionRepository->set($this->prefix . $draft->customerPhone, $draft->toArray());
    }

    /**
     * Clear/delete the draft from Redis.
     */
    public function clearDraft(string $phone): void
    {
        $this->sessionRepository->delete($this->prefix . $phone);
    }

    /**
     * Add a product item to the draft.
     */
    public function addItem(string $phone, int $productId, string $productName, int $variantId, string $variantName, int $quantity, float $unitPrice, array $extras = []): OrderDraft
    {
        $draft = $this->getDraft($phone);

        // Check if item with same variant and exact same extras already exists to sum quantities
        $found = false;
        foreach ($draft->items as $item) {
            if ($item->variantId === $variantId) {
                $existingExtras = $item->extras;
                usort($existingExtras, fn($a, $b) => $a['id'] <=> $b['id']);
                $newExtras = $extras;
                usort($newExtras, fn($a, $b) => $a['id'] <=> $b['id']);

                if (json_encode($existingExtras) === json_encode($newExtras)) {
                    $item->quantity += $quantity;
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            $draft->items[] = new OrderItemDraft(
                productId: $productId,
                productName: $productName,
                variantId: $variantId,
                variantName: $variantName,
                quantity: $quantity,
                unitPrice: $unitPrice,
                extras: $extras
            );
        }

        $this->saveDraft($draft);
        return $draft;
    }

    /**
     * Remove a variant item from the draft.
     */
    public function removeItem(string $phone, int $variantId): OrderDraft
    {
        $draft = $this->getDraft($phone);
        $draft->items = array_values(array_filter($draft->items, fn($item) => $item->variantId !== $variantId));
        $this->saveDraft($draft);
        return $draft;
    }

    /**
     * Update quantity of an item in the draft.
     */
    public function updateQuantity(string $phone, int $variantId, int $quantity): OrderDraft
    {
        $draft = $this->getDraft($phone);
        foreach ($draft->items as $item) {
            if ($item->variantId === $variantId) {
                $item->quantity = $quantity;
                break;
            }
        }
        $this->saveDraft($draft);
        return $draft;
    }

    /**
     * Change the presentation/variant of an item in the draft.
     */
    public function changeVariant(string $phone, int $oldVariantId, int $newVariantId, string $newVariantName, float $newUnitPrice): OrderDraft
    {
        $draft = $this->getDraft($phone);
        foreach ($draft->items as $item) {
            if ($item->variantId === $oldVariantId) {
                $item->variantId = $newVariantId;
                $item->variantName = $newVariantName;
                $item->unitPrice = $newUnitPrice;
                break;
            }
        }
        $this->saveDraft($draft);
        return $draft;
    }

    /**
     * Add an extra to an item in the draft.
     */
    public function addExtra(string $phone, int $variantId, int $extraId, string $extraName, float $extraPrice): OrderDraft
    {
        $draft = $this->getDraft($phone);
        foreach ($draft->items as $item) {
            if ($item->variantId === $variantId) {
                $exists = false;
                foreach ($item->extras as $extra) {
                    if (($extra['id'] ?? null) === $extraId) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $item->extras[] = [
                        'id' => $extraId,
                        'name' => $extraName,
                        'price' => $extraPrice
                    ];
                }
                break;
            }
        }
        $this->saveDraft($draft);
        return $draft;
    }

    /**
     * Remove an extra from an item in the draft.
     */
    public function removeExtra(string $phone, int $variantId, int $extraId): OrderDraft
    {
        $draft = $this->getDraft($phone);
        foreach ($draft->items as $item) {
            if ($item->variantId === $variantId) {
                $item->extras = array_values(array_filter($item->extras, fn($e) => ($e['id'] ?? null) !== $extraId));
                break;
            }
        }
        $this->saveDraft($draft);
        return $draft;
    }
}
