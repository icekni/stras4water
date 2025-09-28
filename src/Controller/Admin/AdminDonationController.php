<?php
namespace App\Controller\Admin;

use App\Entity\Donation;
use App\Enum\DonationStatus;
use App\Form\DonManuelType;
use App\Repository\DonationRepository;
use App\Service\EmailService;
use App\Service\RecuFiscalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminDonationController extends AbstractController
{
    #[Route('/admin/donations', name: 'admin_donations_index')]
    public function index(Request $request, DonationRepository $donationRepository): Response
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $total = $donationRepository->count([]);
        $donations = $donationRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);

        return $this->render('admin/dons/dons.html.twig', [
            'dons' => $donations,
            'page' => $page,
            'pages' => ceil($total / $limit),
        ]);
    }

    #[IsGranted('ROLE_ACCUEIL')]
    #[Route('/admin/donations/create', name: 'admin_donations_create')]
    public function create(Request $request, EmailService $emailService, EntityManagerInterface $entityManager): Response
    {
        $donation = new Donation();
        $form = $this->createForm(DonManuelType::class, $donation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($donation);

            if ($donation->isWantsRecuFiscal()) {
                $token = bin2hex(random_bytes(32));
                $donation->setToken($token);
                $donation->setStatus(DonationStatus::PAID);

                $url = $this->generateUrl('fillFiscalData', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                $emailService->sendRequestFiscalData($donation, $url);
            } else {
                $donation->setStatus(DonationStatus::COMPLETED);
            }

            $entityManager->flush();
            $this->addFlash('success', sprintf('Donation of %.2f€ has been recorded.', $donation->getMontant()));

            return $this->redirectToRoute('admin_donations_create');
        }

        return $this->render('admin/dons/don_manuel.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/donations/refund/{id}', name: 'admin_donations_refund')]
    public function refund(Donation $donation, EntityManagerInterface $entityManager, RecuFiscalService $recuFiscalService): Response
    {
        if (!$donation->getCheckoutId()) {
            $this->addFlash('danger', 'Refund impossible: no Stripe payment ID found.');
            return $this->redirectToRoute('admin_donations_index');
        }

        $stripe = new \Stripe\StripeClient($_ENV['STRIPE_SECRET_KEY']);
        try {
            $stripe->refunds->create(['payment_intent' => $donation->getCheckoutId()]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Stripe error: ' . $e->getMessage());
            return $this->redirectToRoute('admin_donations_index');
        }

        $recuFiscalService->invalidate($donation);
        $donation->setStatus(DonationStatus::REFUNDED);
        $entityManager->flush();

        $this->addFlash('success', 'Donation refunded successfully.');

        return $this->redirectToRoute('admin_donations_index');
    }

    #[Route('/admin/donations/resend/{id}', name: 'admin_donations_resend')]
    public function resend(Request $request, Donation $donation, EntityManagerInterface $entityManager, EmailService $emailService): Response
    {
        $email = $request->request->get('email');
        if ($email && $donation->getEmail() !== $email) {
            $donation->setEmail($email);
        }

        $token = bin2hex(random_bytes(32));
        $donation->setToken($token);

        $url = $this->generateUrl('fillFiscalData', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
        $emailService->sendRequestFiscalData($donation, $url);

        $entityManager->flush();

        $this->addFlash('success', 'Fiscal receipt request email has been resent.');

        return $this->redirectToRoute('admin_donations_index');
    }

    #[Route('/admin/donations/receipt/{id}', name: 'admin_donations_receipt')]
    public function getReceipt(Donation $donation): Response
    {
        $pdfPath = $donation->getUrlRecuFiscal();

        if (!file_exists($pdfPath)) {
            throw $this->createNotFoundException('PDF receipt not found.');
        }

        return new BinaryFileResponse($pdfPath, 200, ['Content-Type' => 'application/pdf'], false, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/admin/donations/delete/{id}', name: 'admin_donations_delete', methods: ['POST'])]
    public function delete(Request $request, Donation $donation, EntityManagerInterface $em): Response
    {
        if ($donation->getStatus() !== DonationStatus::CREATED) {
            $this->addFlash('danger', 'Seuls les dons en attente peuvent être supprimés.');
            return $this->redirectToRoute('admin_donations_index');
        }

        if ($this->isCsrfTokenValid('delete' . $donation->getId(), $request->request->get('_token'))) {
            $em->remove($donation);
            $em->flush();
            $this->addFlash('success', 'Don supprimé avec succès.');
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('admin_donations_index');
    }
}
