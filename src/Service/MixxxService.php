<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class MixxxService
{
    private const DEFAULT_URL = 'http://127.0.0.1:8787';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Récupère l'état actuel de Mixxx.
     */
    public function getState(): array
    {
        try {

            $response = $this->httpClient->request(
                'GET',
                self::DEFAULT_URL . '/state',
                [
                    'timeout' => 1,
                ]
            );

            if ($response->getStatusCode() !== 200) {
                return $this->getEmptyState();
            }

            $data = $response->toArray(false);

            return [
                'current' => $data['current'] ?? null,
                'previous' => $data['previous'] ?? null,
                'next' => $data['next'] ?? [],
            ];

        } catch (\Throwable) {

            /*
             * Mixxx peut être éteint ou temporairement
             * inaccessible.
             *
             * L'écran ne doit pas planter pour autant.
             */
            return $this->getEmptyState();
        }
    }


    /**
     * État vide utilisé lorsque Mixxx n'est pas disponible.
     */
    private function getEmptyState(): array
    {
        return [
            'current' => null,
            'previous' => null,
            'next' => [],
        ];
    }
}