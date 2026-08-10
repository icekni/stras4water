<?php

namespace App\Controller;

use App\Entity\Adhesion;
use App\Enum\DonationStatus;
use App\Enum\MoyenPaiement;
use App\Enum\Statut;
use App\Repository\AbonnementSouscritRepository;
use App\Repository\AdhesionRepository;
use App\Repository\CarteSouscriteRepository;
use App\Repository\DonationRepository;
use App\Repository\UserRepository;
use App\Service\CarteDeMembreGenerator;
use App\Service\CommandeDetailsBuilder;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class WebHookController extends AbstractController
{
    #[Route('/webhook/stripe', name: 'stripe_webhook')]
    public function handleStripeWebhook(
        Request $request, 
        EntityManagerInterface $entityManager, 
        DonationRepository $donationRepository, 
        UserRepository $userRepository,
        AdhesionRepository $adhesionRepository,
        AbonnementSouscritRepository $abonnementRepository,
        CarteSouscriteRepository $carteRepository,
        EmailService $emailService,
        CarteDeMembreGenerator $carteDeMembreGenerator,
        CommandeDetailsBuilder $commandeDetailsBuilder
    ): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $endpointSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            return new Response('Webhook Error: ' . $e->getMessage(), 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            if (isset($session->payment_intent)) {
                $stripe = new \Stripe\StripeClient($_ENV['STRIPE_SECRET_KEY']);
                $paymentIntent = $stripe->paymentIntents->retrieve($session->payment_intent);
                
                $donId = $paymentIntent->metadata->don_id ?? null;
                $userId = $paymentIntent->metadata->user_id ?? null;
                $adhesion = $paymentIntent->metadata->adhesion === "true";
                $abonnementIds = isset($paymentIntent->metadata->abonnement_ids) ? explode(',', $paymentIntent->metadata->abonnement_ids) : [];
                $carteIds = isset($paymentIntent->metadata->carte_ids) ? explode(',', $paymentIntent->metadata->carte_ids) : [];
            } else {
                $donId = null;
                return new Response('No payment intent', 400);
            }
            
            if ($donId) {
                $donation = $donationRepository->find($donId);
                $donation->setCheckoutId($session->payment_intent);

                if ($donation && $donation->isWantsRecuFiscal()) {
                    $token = bin2hex(random_bytes(32));
                    $donation->setToken($token);
                    $donation->setStatus(DonationStatus::PAID);

                    $url = $this->generateUrl('fillFiscalData', ['token' => $token ], UrlGeneratorInterface::ABSOLUTE_URL);
                    $emailService->sendRequestFiscalData($donation, $url);
                }
                elseif ($donation && !$donation->isWantsRecuFiscal()) {
                    $donation->setStatus(DonationStatus::COMPLETED);
                }

                $emailService->sendMail(
                    'Stras4Water - Don',
                    'don@stras4water.org',
                    'Réception d\'un nouveau don',
                    "Bonjour,\nVous avez reçu un nouveau don de {$donation->getMontantNet()} € via Stripe."
                );
            }
            else {
                $user = $userId ? $userRepository->find($userId) : null;

                if (!$user) {
                    return new Response('User not found', 400);
                }

                foreach ($abonnementIds as $id) {
                    $abonnement = $abonnementRepository->find($id);
                    if ($abonnement && $abonnement->getUser() === $user && $abonnement->getStatut() === Statut::CREATED) {
                        if ($abonnement->isTarifReduit()) {
                            $abonnement->setStatut(Statut::PENDING);
                            $abonnement->setTarifReduitVerifie(false);
                        }
                        else {
                            $abonnement->setStatut(Statut::ACTIVE);
                        }
                        $abonnement->setMoyenPaiement(MoyenPaiement::STRIPE);
                    }
                }

                foreach ($carteIds as $id) {
                    $carte = $carteRepository->find($id);
                    if ($carte && $carte->getUser() === $user && $carte->getStatut() === Statut::CREATED) {
                        if ($carte->isTarifReduit()) {
                            $carte->setStatut(Statut::PENDING);
                            $carte->setTarifReduitVerifie(false);
                        }
                        else {
                            $carte->setStatut(Statut::ACTIVE);
                        }
                        $carte->setMoyenPaiement(MoyenPaiement::STRIPE);
                    }
                }

                if ($adhesion) {
                    if (!$user->getAdhesion()) {
                        $adhesionEntity = new Adhesion();

                        $adhesionEntity->setUser($user);
                        $adhesionEntity->setMoyenPaiement(MoyenPaiement::STRIPE);

                        $entityManager->persist($adhesionEntity);
                        $entityManager->flush();
                    }
                }

                $detailsCommande = $commandeDetailsBuilder->build($adhesion, $abonnementIds, $carteIds, $user, $this->getSaisonAdhesion());
                $pdfCard = $adhesion ? $carteDeMembreGenerator->generate($user, $this->getSaisonAdhesion()) : null;

                $emailService->sendConfirmationCommande($user, $adhesion, $pdfCard, $detailsCommande);

            }

            $entityManager->flush();
        }

        return new Response('OK', 200);
    }

    private function getSaisonAdhesion(): string
    {
        $now = new \DateTimeImmutable();
        $year = (int) $now->format('Y');
        $month = (int) $now->format('n'); // 1 à 12

        if ($month >= 7) {
            return $year . '/' . ($year + 1);
        } else {
            return ($year - 1) . '/' . $year;
        }
    }
}
