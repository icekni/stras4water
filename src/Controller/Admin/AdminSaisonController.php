<?php

namespace App\Controller\Admin;

use App\Entity\Saison;
use App\Form\SaisonType;
use App\Repository\SaisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/saisons')]
class AdminSaisonController extends AbstractController
{
    #[Route('/', name: 'admin_saison_index')]
    public function index(SaisonRepository $saisonRepository): Response
    {
        return $this->render('admin/saison/index.html.twig', [
            'saisons' => $saisonRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_saison_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $saison = new Saison();
        $form = $this->createForm(SaisonType::class, $saison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($saison);
            $em->flush();

            $this->addFlash('success', 'Saison ajoutée avec succès.');
            return $this->redirectToRoute('admin_saison_index');
        }

        return $this->render('admin/saison/form.html.twig', [
            'form' => $form->createView(),
            'saison' => $saison,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_saison_edit')]
    public function edit(Saison $saison, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SaisonType::class, $saison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Saison modifiée avec succès.');
            return $this->redirectToRoute('admin_saison_index');
        }

        return $this->render('admin/saison/form.html.twig', [
            'form' => $form->createView(),
            'saison' => $saison,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_saison_delete', methods: ['POST'])]
    public function delete(Request $request, Saison $saison, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_saison_' . $saison->getId(), $request->request->get('_token'))) {
            $em->remove($saison);
            $em->flush();
            $this->addFlash('success', 'Saison supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_saison_index');
    }
}
