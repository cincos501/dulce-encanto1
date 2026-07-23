<?php

declare(strict_types=1);

namespace App\AI\Tools\Orders;

use App\AI\Contracts\ToolInterface;
use App\AI\Orders\OrderDraftManager;
use App\Repositories\ProductVariantRepositoryInterface;

class AddToOrderDraftTool implements ToolInterface
{
    public function __construct(
        protected OrderDraftManager $draftManager,
        protected ProductVariantRepositoryInterface $variantRepository
    ) {}

    public function getName(): string
    {
        return 'add_to_order_draft';
    }

    public function getDescription(): string
    {
        return 'Agregar un producto (especificando variant_id y cantidad) al borrador del pedido temporal del cliente.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'variant_id' => [
                    'type' => 'integer',
                    'description' => 'ID de la presentación/variante del producto a agregar.'
                ],
                'quantity' => [
                    'type' => 'integer',
                    'description' => 'Cantidad a agregar (por defecto 1).'
                ]
            ],
            'required' => ['variant_id']
        ];
    }

    public function execute(array $arguments, array $context = []): string
    {
        $phone = $context['phone'] ?? null;
        if (!$phone) {
            return "Error: Contexto de teléfono de cliente no disponible.";
        }

        $variantId = (int) $arguments['variant_id'];
        $quantity = isset($arguments['quantity']) ? (int) $arguments['quantity'] : 1;

        if ($quantity <= 0) {
            return "Error: La cantidad a agregar debe ser mayor a 0.";
        }

        $variant = $this->variantRepository->findById($variantId);
        if (!$variant) {
            return "Error: La presentación de producto con ID {$variantId} no fue encontrada en el catálogo.";
        }

        $product = $variant->product;
        $productName = $product ? $product->name : 'Producto';

        $draft = $this->draftManager->addItem(
            phone: $phone,
            productId: $product ? $product->id : 0,
            productName: $productName,
            variantId: $variant->id,
            variantName: $variant->name,
            quantity: $quantity,
            unitPrice: (float) $variant->price
        );

        return "Se agregaron {$quantity} unidad(es) de '{$productName} ({$variant->name})' al pedido temporal. " .
               "El subtotal del pedido ahora es Bs. {$draft->subtotal}.";
    }
}
