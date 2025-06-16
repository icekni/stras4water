<?php

namespace App\Controller;

use App\Enum\DonationStatus;
use App\Repository\DonationRepository;
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
    public function handleStripeWebhook(Request $request, EntityManagerInterface $entityManager, DonationRepository $donationRepository, EmailService $emailService): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $endpointSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            return new Response('Webhook Error', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            if (isset($session->payment_intent)) {
                $stripe = new \Stripe\StripeClient($_ENV['STRIPE_SECRET_KEY']);
                $paymentIntent = $stripe->paymentIntents->retrieve($session->payment_intent);

                $donId = $paymentIntent->metadata->don_id ?? null;
            } else {
                $donId = null;
            }

            if ($donId) {
                $donation = $donationRepository->find($donId);

                if ($donation && $donation->isWantsRecuFiscal()) {
                    $token = bin2hex(random_bytes(32));
                    $donation->setToken($token);
                    $donation->setStatus(DonationStatus::PAID);

                    $entityManager->flush();

                    $url = $this->generateUrl('fillFiscalData', ['token' => $token ], UrlGeneratorInterface::ABSOLUTE_URL);
                    $emailService->sendRequestFiscalData($donation, $url);
                }
                elseif ($donation && !$donation->isWantsRecuFiscal()) {
                    $donation->setStatus(DonationStatus::COMPLETED);

                    $entityManager->flush();
                }
            }
        }

        return new Response('OK', 200);
    }
}
