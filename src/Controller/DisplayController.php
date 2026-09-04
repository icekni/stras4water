<?php

namespace App\Controller;

use App\Service\CoverService;
use App\Service\DisplayStateProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DisplayController extends AbstractController
{
    #[Route('/live', name: 'display_live')]
    public function live(
        DisplayStateProvider $provider
    ): Response {
        return $this->render(
            'display/live.html.twig',
            [
                'state' => $provider->getState()
            ]
        );
    }

    #[Route(
        '/api/display/state',
        name: 'display_state',
        methods: ['GET']
    )]
    public function state(
        DisplayStateProvider $provider
    ): JsonResponse {
        return $this->json(
            $provider->getState()
        );
    }

    #[Route(
        '/api/display/state',
        name: 'display_state_update',
        methods: ['POST']
    )]
    public function updateState(
        Request $request,
        DisplayStateProvider $provider
    ): JsonResponse {
        $data = json_decode(
            $request->getContent(),
            true
        );

        if (!is_array($data)) {
            return $this->json(
                [
                    'error' => 'JSON invalide'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $provider->updateState($data);

        return $this->json([
            'success' => true
        ]);
    }
}