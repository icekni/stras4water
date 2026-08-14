<?php

namespace App\Controller;

use App\Service\CoverService;
use App\Service\DisplayStateProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DisplayController extends AbstractController
{
    #[Route('/live', name:'display_live')]
    public function live(
        DisplayStateProvider $provider
    ): Response
    {
        return $this->render(
            'display/live.html.twig',
            [

                'state' => $provider->getState()

            ]
        );
    }

    #[Route('/api/display/state', name:'display_state')]
    public function state(
        DisplayStateProvider $provider
    ): JsonResponse
    {
        return $this->json(
            $provider->getState()
        );
    }

    #[Route('/api/display/test-cover', name: 'display_test_cover')]
    public function testCover(
        CoverService $coverService
    ): JsonResponse {
        $cover = $coverService->getCover(
            'Marc Anthony',
            'Vivir Mi Vida (Official Remix Version Extended Dance Floor)'
        );

        return $this->json([
            'cover' => $cover,
        ]);
    }
}