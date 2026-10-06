<?php

namespace App\Twig;

use App\Service\CartService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class CartCountExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private readonly CartService $cartService)
    {
    }

    public function getGlobals(): array
    {
        return [
            'cart_count' => $this->cartService->getCount(),
        ];
    }
}