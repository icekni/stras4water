<?php

namespace App\Controller\Admin;

use App\Entity\GroupeControle;
use App\Entity\Saison;
use App\Form\GroupeControleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/groupes-controle')]
class AdminGroupeControleController extends AbstractController
{
    #[Route('/', name: 'admin_groupe_controle_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $groupes = $em->getRepository(GroupeControle::class)->findAll();

        return $this->render('admin/groupe_controle/index.html.twig', [
            'groupes' => $groupes,
        ]);
    }

    #[Route('/new', name: 'admin_groupe_controle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $groupe = new GroupeControle();

        $saisons = $em->getRepository(Saison::class)->findBy(
            [],
            ['dateDebut' => 'DESC']
        );

        $form = $this->createForm(GroupeControleType::class, $groupe, [
            'saisons' => $saisons,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->updateAbonnements($form, $groupe);

            $em->persist($groupe);
            $em->flush();

            $this->addFlash('success', 'Groupe de contrôle créé avec succès.');

            return $this->redirectToRoute('admin_groupe_controle_index');
        }

        return $this->render('admin/groupe_controle/form.html.twig', [
            'form' => $form->createView(),
            'groupe' => $groupe,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_groupe_controle_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        GroupeControle $groupe,
        EntityManagerInterface $em
    ): Response {
        $saisons = $em->getRepository(Saison::class)->findBy(
            [],
            ['dateDebut' => 'DESC']
        );

        $form = $this->createForm(GroupeControleType::class, $groupe, [
            'saisons' => $saisons,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->updateAbonnements($form, $groupe);

            $em->flush();

            $this->addFlash('success', 'Groupe de contrôle modifié avec succès.');

            return $this->redirectToRoute('admin_groupe_controle_index');
        }

        return $this->render('admin/groupe_controle/form.html.twig', [
            'form' => $form->createView(),
            'groupe' => $groupe,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_groupe_controle_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        GroupeControle $groupe,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid(
            'delete_groupe_controle_'.$groupe->getId(),
            $request->request->get('_token')
        )) {
            $em->remove($groupe);
            $em->flush();

            $this->addFlash('success', 'Groupe de contrôle supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_groupe_controle_index');
    }

    private function updateAbonnements($form, GroupeControle $groupe): void
    {
        $groupe->getAbonnements()->clear();

        foreach ($form->get('abonnementsActifs')->getData() as $abonnement) {
            $groupe->addAbonnement($abonnement);
        }

        foreach ($form->get('abonnementsPrecedents')->getData() as $abonnement) {
            $groupe->addAbonnement($abonnement);
        }
    }
}