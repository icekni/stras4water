<?php
namespace App\Controller\Admin;

use App\Entity\Lien;
use App\Form\LienType;
use App\Repository\LienRepository;
use App\Service\QrCodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminLinkController extends AbstractController
{
    #[Route('/admin/links', name: 'admin_links_index')]
    public function index(LienRepository $repo): Response
    {
        return $this->render('admin/liens/index.html.twig', [
            'liens' => $repo->findAll(),
        ]);
    }

    #[Route('/admin/links/new', name: 'admin_links_new')]
    public function new(Request $request, EntityManagerInterface $em, QrCodeGenerator $qrCodeGenerator): Response
    {
        $link = new Lien();
        $form = $this->createForm(LienType::class, $link);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $qrPath = $qrCodeGenerator->generateLien($link);
            $link->setQrCodePath($qrPath);
            $em->persist($link);
            $em->flush();

            return $this->redirectToRoute('admin_links_index');
        }

        return $this->render('admin/liens/form.html.twig', [
            'form' => $form->createView(),
            'editMode' => $link->getId() !== null,
        ]);
    }

    #[Route('/admin/links/{id}/edit', name: 'admin_links_edit')]
    public function edit(Lien $link, Request $request, EntityManagerInterface $em, QrCodeGenerator $qrCodeGenerator): Response
    {
        $form = $this->createForm(LienType::class, $link);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $qrPath = $qrCodeGenerator->generateLien($link);
            $link->setQrCodePath($qrPath);
            $em->persist($link);
            $em->flush();

            return $this->redirectToRoute('admin_links_index');
        }

        return $this->render('admin/liens/form.html.twig', [
            'form' => $form->createView(),
            'editMode' => $link->getId() !== null,
        ]);
    }

    #[Route('/admin/links/{id}/delete', name: 'admin_links_delete', methods: ['POST'])]
    public function delete(Request $request, Lien $link, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_link_' . $link->getId(), $request->request->get('_token'))) {
            $em->remove($link);
            $em->flush();
        }

        return $this->redirectToRoute('admin_links_index');
    }

    #[Route('/admin/links/{id}', name: 'admin_links_show')]
    public function showStats(Lien $link): Response
    {
        $clicks = $link->getClics();
        $dateFrom = (new \DateTimeImmutable('-60 days'))->setTime(0, 0);
        $dateTo = new \DateTimeImmutable('today');

        $clicksPerDay = [];
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($dateFrom, $interval, $dateTo->modify('+1 day'));

        foreach ($period as $date) {
            $clicksPerDay[$date->format('Y-m-d')] = 0;
        }

        foreach ($clicks as $clickDate) {
            if ($clickDate >= $dateFrom) {
                $clicksPerDay[$clickDate->format('Y-m-d')]++;
            }
        }

        ksort($clicksPerDay);

        return $this->render('admin/liens/stats.html.twig', [
            'lien' => $link,
            'clics' => $clicksPerDay,
        ]);
    }
}
