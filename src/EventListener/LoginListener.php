<?php

namespace App\EventListener;

use App\Service\CartService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginListener
{
    public function __construct(private readonly CartService $cartService)
    {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $this->cartService->refreshAdhesion();
    }
}