<?php
// src/Controller/Admin/AbonnementController.php
namespace App\Controller\Admin;

use App\Entity\Abonnement;
use App\Form\AbonnementType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/abonnements')]
class AdminAbonnementController extends AbstractController
{
    #[Route('/', name: 'admin_abonnement_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $abonnements = $em->getRepository(Abonnement::class)->findAll();

        return $this->render('admin/abonnement/index.html.twig', [
            'abonnements' => $abonnements,
        ]);
    }

    #[Route('/new', name: 'admin_abonnement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $abonnement = new Abonnement();
        $form = $this->createForm(AbonnementType::class, $abonnement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($abonnement->getTarifReduit() != null) {
                $abonnement->setHasTarifReduit(true);
            }
            $em->persist($abonnement);
            $em->flush();

            $this->addFlash('success', 'Abonnement créé avec succès.');

            return $this->redirectToRoute('admin_abonnement_index');
        }

        return $this->render('admin/abonnement/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_abonnement_show', methods: ['GET'])]
    public function show(Abonnement $abonnement): Response
    {
        return $this->render('admin/abonnement/show.html.twig', [
            'abonnement' => $abonnement,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_abonnement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Abonnement $abonnement, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AbonnementType::class, $abonnement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($abonnement->getTarifReduit() != null) {
                $abonnement->setHasTarifReduit(true);
            }
            $em->flush();

            $this->addFlash('success', 'Abonnement modifié avec succès.');

            return $this->redirectToRoute('admin_abonnement_index');
        }

        return $this->render('admin/abonnement/form.html.twig', [
            'form' => $form->createView(),
            'abonnement' => $abonnement,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_abonnement_delete', methods: ['POST'])]
    public function delete(Request $request, Abonnement $abonnement, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$abonnement->getId(), $request->request->get('_token'))) {
            $em->remove($abonnement);
            $em->flush();

            $this->addFlash('success', 'Abonnement supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_abonnement_index');
    }
}
