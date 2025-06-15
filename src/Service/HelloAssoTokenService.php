<?php

namespace App\Service;

use App\Entity\HelloAssoToken;
use App\Repository\HelloAssoTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HelloAssoTokenService
{
    private string $clientId;
    private string $clientSecret;
    private string $tokenUrl;

    public function __construct(
        private readonly HelloAssoTokenRepository $repository,
        private readonly EntityManagerInterface $em,
        private readonly HttpClientInterface $httpClient,
        ParameterBagInterface $params
    ) {
        $this->clientId = $params->get('HELLOASSO_CLIENT_ID');
        $this->tokenUrl = $params->get('HELLOASSO_API_URL') . '/oauth2/token';
        $this->clientSecret = $params->get('HELLOASSO_CLIENT_SECRET');
    }

    public function getValidAccessToken(): string
    {
        $token = $this->repository->getSingleton();

        if ($token->isAccessTokenValid()) {
            return $token->getAccessToken();
        }

        if ($token->isRefreshTokenValid()) {
            $this->refreshAccessToken($token);
        } else {
            $this->regenerateTokensFromClientCredentials($token);
        }

        $this->em->flush();

        return $token->getAccessToken();
    }

    private function refreshAccessToken(HelloAssoToken $token): void
    {
        $response = $this->httpClient->request('POST', $this->tokenUrl, [
            'body' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $token->getRefreshToken(),
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ],
        ]);

        $data = $response->toArray();
        $this->updateTokenFromApiResponse($token, $data);
    }

    private function regenerateTokensFromClientCredentials(HelloAssoToken $token): void
    {
        $response = $this->httpClient->request('POST', $this->tokenUrl, [
            'body' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ],
        ]);

        $data = $response->toArray();
        $this->updateTokenFromApiResponse($token, $data);
    }

    private function updateTokenFromApiResponse(HelloAssoToken $token, array $data): void
    {
        $token->setAccessToken($data['access_token']);
        $token->setAccessTokenExpiresAt(new \DateTimeImmutable('+'.$data['expires_in'].' seconds'));

        if (!empty($data['refresh_token'])) {
            $token->setRefreshToken($data['refresh_token']);
            $token->setRefreshTokenExpiresAt(new \DateTimeImmutable('+6 months'));
        }
    }
}