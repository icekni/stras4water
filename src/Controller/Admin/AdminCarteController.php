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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\GroupeControleRepository;
use App\Service\ControleAccesService;

#[IsGranted('ROLE_ACCUEIL')]
#[Route('/admin/cartes')]
class AdminCarteController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/', name: 'admin_carte_index', methods: ['GET'])]
    public function index(CarteRepository $carteRepository): Response
    {
        return $this->render('admin/carte/index.html.twig', [
            'cartes' => $carteRepository->findAll(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
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

    #[IsGranted('ROLE_ADMIN')]
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

    #[Route('/{id}/verifier', name: 'admin_carte_verifier', methods: ['POST'])]
    public function verifierCarte(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $carteSouscrite = $em->getRepository(CarteSouscrite::class)->find($id);

        if (!$carteSouscrite) {
            throw $this->createNotFoundException(
                "Carte souscrite #$id introuvable"
            );
        }

        if (!$this->isCsrfTokenValid(
            'verifier_carte'.$carteSouscrite->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        $carteSouscrite->setTarifReduitVerifie(true);

        $em->flush();

        $this->addFlash(
            'success',
            'Justificatif vérifié avec succès.'
        );

        $redirectParams = [
            'id' => $carteSouscrite->getUser()->getId(),
        ];

        $groupeId = $request->request->getInt('groupe');

        if ($groupeId) {
            $redirectParams['groupe'] = $groupeId;
        }

        return $this->redirectToRoute(
            'admin_user_check',
            $redirectParams
        );
    }

    #[Route('/{id}/retirer-seance', name: 'admin_carte_retirer_seance', methods: ['POST'])]
    public function retirerSeance(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        GroupeControleRepository $groupeControleRepository,
        ControleAccesService $controleAccesService
    ): Response {
        $carteSouscrite = $em->getRepository(CarteSouscrite::class)->find($id);

        if (!$carteSouscrite) {
            throw $this->createNotFoundException(
                "Carte souscrite #$id introuvable"
            );
        }

        if (!$this->isCsrfTokenValid(
            'retirer_seance'.$carteSouscrite->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        $groupeId = $request->request->getInt('groupe');

        if (!$groupeId) {
            throw $this->createAccessDeniedException(
                'Un groupe de contrôle est requis pour retirer une séance.'
            );
        }

        $groupe = $groupeControleRepository->find($groupeId);

        if ($groupe === null || !$groupe->isActif()) {
            throw $this->createNotFoundException(
                'Groupe de contrôle introuvable ou désactivé.'
            );
        }

        /*
        * On contrôle l'utilisateur avec le groupe.
        *
        * Cela permet notamment de vérifier que la carte fait bien
        * partie des cartes autorisées par ce groupe.
        */
        $controle = $controleAccesService->controler(
            $carteSouscrite->getUser(),
            $groupe
        );

        $carteAutorisee = null;

        foreach ($controle['cartes'] as $item) {
            if ($item['souscrite']->getId() === $carteSouscrite->getId()) {
                $carteAutorisee = $item;
                break;
            }
        }

        if ($carteAutorisee === null) {
            $this->addFlash(
                'danger',
                'Cette carte ne permet pas l’accès à ce groupe.'
            );

            return $this->redirectToRoute('admin_user_check', [
                'id' => $carteSouscrite->getUser()->getId(),
                'groupe' => $groupe->getId(),
            ]);
        }

        /*
        * Le justificatif tarif réduit n'empêche pas de retirer une séance.
        *
        * On utilise donc une validation spécifique à la consommation,
        * qui vérifie :
        * - les séances restantes
        * - que la carte est active
        * - que la souscription est ACTIVE
        *
        * Le justificatif tarif réduit n'est volontairement pas contrôlé ici.
        */
        $consommation = $carteSouscrite->peutRetirerSeance();

        if (!$consommation->isValid) {
            $this->addFlash(
                'warning',
                $consommation->reason
            );

            return $this->redirectToRoute('admin_user_check', [
                'id' => $carteSouscrite->getUser()->getId(),
                'groupe' => $groupe->getId(),
            ]);
        }

        $carteSouscrite->setSeancesRestantes(
            $carteSouscrite->getSeancesRestantes() - 1
        );

        $em->flush();

        $this->addFlash(
            'success',
            'Une séance a été retirée de la carte.'
        );

        return $this->redirectToRoute('admin_user_check', [
            'id' => $carteSouscrite->getUser()->getId(),
            'groupe' => $groupe->getId(),
        ]);
    }
}
