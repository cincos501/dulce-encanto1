<?php

declare(strict_types=1);

namespace App\AI\Tools\Orders;

use App\AI\Contracts\ToolInterface;
use App\AI\Orders\OrderDraftManager;
use App\Repositories\ProductVariantRepositoryInterface;

class AddExtraToOrderItemTool implements ToolInterface
{
    public function __construct(
        protected OrderDraftManager $draftManager,
        protected ProductVariantRepositoryInterface $variantRepository
    ) {}

    public function getName(): string
    {
        return 'add_extra_to_order_item';
    }

    public function getDescription(): string
    {
        return 'Agregar un adicional (extra) a un producto específico ya agregado al pedido (especificando variant_id del producto y extra_id del adicional).';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'variant_id' => [
                    'type' => 'integer',
                    'description' => 'ID de la presentación/variante del producto al cual agregar el extra.'
                ],
                'extra_id' => [
                    'type' => 'integer',
                    'description' => 'ID del adicional (extra) a agregar.'
                ]
            ],
            'required' => ['variant_id', 'extra_id']
        ];
    }

    public function execute(array $arguments, array $context = []): string
    {
        $phone = $context['phone'] ?? null;
        if (!$phone) {
            return "Error: Contexto de teléfono de cliente no disponible.";
        }

        $variantId = (int) $arguments['variant_id'];
        $extraId = (int) $arguments['extra_id'];

        $variant = $this->variantRepository->findById($variantId);
        if (!$variant) {
            return "Error: La presentación de producto con ID {$variantId} no fue encontrada en el catálogo.";
        }

        // Retrieve the extra associated with this specific product variant to get its correct price
        $variantExtra = $variant->extras()->where('extra_id', $extraId)->first();
        if (!$variantExtra) {
            return "Error: El adicional con ID {$extraId} no está disponible para esta presentación de producto.";
        }

        $price = (float) $variantExtra->pivot->price;

        $draft = $this->draftManager->addExtra($phone, $variantId, $variantExtra->id, $variantExtra->name, $price);

        return "Adicional '{$variantExtra->name}' agregado al producto en el pedido temporal. El subtotal ahora es Bs. {$draft->subtotal}.";
    }
}
