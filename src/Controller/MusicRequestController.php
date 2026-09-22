<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Entity\MusicRequest;
use App\Enum\DonationStatus;
use App\Enum\MoyenPaiement;
use App\Enum\MusicRequestStatus;
use App\Enum\TypeDon;
use App\Form\MusicRequestType;
use App\Repository\MusicRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Entity\MusicRequestVote;
use App\Repository\MusicRequestVoteRepository;
use Symfony\Component\HttpFoundation\Cookie;

final class MusicRequestController extends AbstractController
{
    private float $paidAmount;
    private float $votesRequired;

    public function __construct()
    {
        $this->paidAmount = (float) $_ENV['MUSIC_REQUEST_PAID_AMOUNT'];
        $this->votesRequired = (int) $_ENV['MUSIC_REQUEST_VOTES_REQUIRED'];
    }

    #[Route(
        '/music-request/{id}/vote',
        name: 'music_request_vote',
        methods: ['POST']
    )]
    public function vote(
        MusicRequest $musicRequest,
        Request $request,
        EntityManagerInterface $entityManager,
        MusicRequestVoteRepository $musicRequestVoteRepository,
    ): Response {
        if (!$this->isCsrfTokenValid(
            'music_request_vote_' . $musicRequest->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        if ($musicRequest->getStatus() !== MusicRequestStatus::PENDING) {
            $this->addFlash(
                'warning',
                'Cette demande musicale n’est plus disponible.'
            );

            return $this->redirectToRoute('music_request');
        }

        // Récupération du token de vote dans le cookie
        $token = $request->cookies->get('music_request_vote_token');

        if (!$token) {
            $token = bin2hex(random_bytes(32));
        }

        // Vérifie si ce navigateur a déjà voté pour ce morceau
        $existingVote = $musicRequestVoteRepository->findOneBy([
            'request' => $musicRequest,
            'token' => $token,
        ]);

        if ($existingVote) {
            $this->addFlash(
                'warning',
                'Tu as déjà voté pour ce morceau.'
            );

            $response = $this->redirectToRoute('music_request');

            $response->headers->setCookie(
                Cookie::create(
                    'music_request_vote_token',
                    $token,
                    new \DateTimeImmutable('+1 year'),
                    '/',
                    null,
                    true,
                    true,
                    false,
                    Cookie::SAMESITE_LAX
                )
            );

            return $response;
        }

        // Création du vote
        $vote = new MusicRequestVote();
        $vote->setRequest($musicRequest);
        $vote->setToken($token);

        $entityManager->persist($vote);

        // Incrément du nombre de votes
        $musicRequest->setVotes(
            $musicRequest->getVotes() + 1
        );

        // Validation automatique à X votes
        if ($musicRequest->getVotes() >= $this->votesRequired) {
            $musicRequest->setStatus(
                MusicRequestStatus::VALIDATED
            );

            $musicRequest->setValidationType(
                \App\Enum\MusicRequestValidationType::VALIDATED_BY_VOTES
            );

            $musicRequest->setValidatedAt(
                new \DateTimeImmutable()
            );

            $this->addFlash(
                'success',
                '🎵 Ce morceau a atteint les ' . $this->votesRequired . ' votes et est maintenant validé !'
            );
        } else {
            $this->addFlash(
                'success',
                'Vote enregistré !'
            );
        }

        $entityManager->flush();

        // Création du cookie si nécessaire
        $response = $this->redirectToRoute('music_request');

        $response->headers->setCookie(
            Cookie::create(
                'music_request_vote_token',
                $token,
                new \DateTimeImmutable('+1 year'),
                '/',
                null,
                true,
                true,
                false,
                Cookie::SAMESITE_LAX
            )
        );

        return $response;
    }

    #[Route(
        '/music-request/{id}/pay',
        name: 'music_request_pay',
        methods: ['POST']
    )]
    public function pay(
        MusicRequest $musicRequest,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid(
            'music_request_pay_' . $musicRequest->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        if ($musicRequest->getStatus() !== MusicRequestStatus::PENDING) {
            $this->addFlash(
                'warning',
                'Cette demande musicale n’est plus disponible.'
            );

            return $this->redirectToRoute('music_request');
        }

        $donation = new Donation();

        $donation->setMontant(2.0);
        $donation->setStatus(DonationStatus::CREATED);
        $donation->setMoyenPaiement(MoyenPaiement::STRIPE);
        $donation->setTypeDon(TypeDon::NUMERAIRE);
        $donation->setWantsRecuFiscal(false);

        if ($this->getUser()) {
            $donation->setUser($this->getUser());
        }

        $entityManager->persist($donation);
        $entityManager->flush();

        $stripe = new \Stripe\StripeClient(
            $_ENV['STRIPE_SECRET_KEY']
        );

        $product = $stripe->products->create([
            'name' => 'Validation demande musicale - ' . (int) round($this->paidAmount * 100) . '€',
        ]);

        $price = $stripe->prices->create([
            'unit_amount' => (int) round($this->paidAmount * 100),
            'currency' => 'eur',
            'product' => $product->id,
        ]);

        $session = $stripe->checkout->sessions->create([
            'success_url' => $this->generateUrl(
                'music_request_payment_success',
                ['id' => $musicRequest->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'cancel_url' => $this->generateUrl(
                'music_request_payment_cancel',
                ['id' => $musicRequest->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'line_items' => [
                [
                    'price' => $price->id,
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'customer' => $_ENV['STRIPE_ANONYMOUS_CUSTOMER_ID'],
            'payment_intent_data' => [
                'metadata' => [
                    'music_request_id' => $musicRequest->getId(),
                    'don_id' => $donation->getId(),
                ],
            ],
        ]);

        return $this->redirect($session->url, 303);
    }

    #[Route(
        '/music-request/{id}/payment-success',
        name: 'music_request_payment_success',
        methods: ['GET']
    )]
    public function paymentSuccess(
        MusicRequest $musicRequest,
    ): Response {
        $this->addFlash(
            'success',
            'Votre paiement a bien été pris en compte.'
        );

        return $this->redirectToRoute('music_request');
    }

    #[Route(
        '/music-request/{id}/payment-cancel',
        name: 'music_request_payment_cancel',
        methods: ['GET']
    )]
    public function paymentCancel(
        MusicRequest $musicRequest,
    ): Response {
        $this->addFlash(
            'warning',
            'Le paiement a été annulé. Vous ne serez pas débité.'
        );

        return $this->redirectToRoute('music_request');
    }

    #[Route(
        '/proposer-musique',
        name: 'music_request',
        methods: ['GET', 'POST']
    )]
    public function propose(
        Request $request,
        EntityManagerInterface $entityManager,
        MusicRequestRepository $musicRequestRepository,
    ): Response {
        $musicRequest = new MusicRequest();

        $form = $this->createForm(
            MusicRequestType::class,
            $musicRequest
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $musicRequest->setVotes(0);
            $musicRequest->setStatus(MusicRequestStatus::PENDING);

            $entityManager->persist($musicRequest);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre morceau a bien été proposé !'
            );

            return $this->redirectToRoute('music_request');
        }

        $musicRequests = $musicRequestRepository->findBy(
            ['status' => [
                MusicRequestStatus::PENDING,
                MusicRequestStatus::VALIDATED,
            ]],
            ['votes' => 'DESC', 'createdAt' => 'ASC']
        );

        return $this->render('music_request/propose.html.twig', [
            'form' => $form->createView(),
            'musicRequests' => $musicRequests,
            'nbVotesRequis' => $this->votesRequired,
            'price' => (int) round($this->paidAmount)
        ]);
    }
}