<?php
namespace App\Controller\Admin;

use App\Repository\DonationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ACCUEIL')]
class AdminDashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(DonationRepository $donationRepository): Response
    {
        $interval = 30;
        $donations = $donationRepository->getLastDonations($interval);

        $totalAmount = array_reduce($donations, fn($total, $don) => $total + round($don->getMontantNet(), 2), 0);

        return $this->render('admin/index.html.twig', [
            'montantTotal' => $totalAmount,
            'donationInterval' => $interval,
        ]);
    }
}
