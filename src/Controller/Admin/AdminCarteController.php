<?php

namespace App\Controller\Admin;

use App\Entity\Carte;
use App\Entity\CarteSouscrite;
use App\Form\CarteType;
use App\Repository\CarteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/cartes')]
class AdminCarteController extends AbstractController
{
    #[Route('/', name: 'admin_carte_index', methods: ['GET'])]
    public function index(CarteRepository $carteRepository): Response
    {
        return $this->render('admin/carte/index.html.twig', [
            'cartes' => $carteRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_carte_new', methods: ['GET', 'POST'])]
    #[Route('/{id}/edit', name: 'admin_carte_edit', methods: ['GET', 'POST'])]
    public function form(Request $request, EntityManagerInterface $em, ?Carte $carte = null): Response
    {
        $carte ??= new Carte();

        $form = $this->createForm(CarteType::class, $carte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($carte);
            $em->flush();

            $this->addFlash('success', 'Carte enregistrée avec succès.');

            return $this->redirectToRoute('admin_carte_index');
        }

        return $this->render('admin/carte/form.html.twig', [
            'form' => $form->createView(),
            'carte' => $carte,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_carte_delete', methods: ['POST'])]
    public function delete(Request $request, Carte $carte, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$carte->getId(), $request->request->get('_token'))) {
            $em->remove($carte);
            $em->flush();
            $this->addFlash('success', 'Carte supprimée.');
        }

        return $this->redirectToRoute('admin_carte_index');
    }

    #[Route('/admin/carte/{id}/verifier', name: 'admin_carte_verifier')]
    public function verifierAbonnement(
        int $id,
        EntityManagerInterface $em
    ): Response {
        $souscrit = $em->getRepository(CarteSouscrite::class)->find($id);
        if (!$souscrit) {
            throw $this->createNotFoundException("Carte souscrite #$id introuvable");
        }

        $souscrit->setTarifReduitVerifie(true);
        $em->flush();

        $this->addFlash('success', 'Justificatif vérifié avec succès.');

        return $this->redirectToRoute('admin_user_check', ['id' => $souscrit->getUser()->getId()]);
    }

    #[Route('/admin/carte/{id}/retirer-seance', name: 'admin_carte_retirer_seance')]
    public function retirerSeance(
        int $id,
        EntityManagerInterface $em
    ): Response {
        $carteSouscrite = $em->getRepository(CarteSouscrite::class)->find($id);

        if (!$carteSouscrite) {
            throw $this->createNotFoundException("Carte souscrite #$id introuvable");
        }

        // Vérifier qu'il reste des séances
        if ($carteSouscrite->getSeancesRestantes() > 0) {
            $carteSouscrite->setSeancesRestantes($carteSouscrite->getSeancesRestantes() - 1);
            $em->flush();
            $this->addFlash('success', 'Une séance a été retirée de la carte.');
        } else {
            $this->addFlash('warning', 'Cette carte n’a plus de séances restantes.');
        }

        return $this->redirectToRoute('admin_user_check', ['id' => $carteSouscrite->getUser()->getId()]);
    }
}
