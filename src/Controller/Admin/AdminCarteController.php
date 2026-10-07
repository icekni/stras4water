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
        EntityManagerInterface $em,
        GroupeControleRepository $groupeControleRepository,
        ControleAccesService $controleAccesService
    ): Response {
        $carteSouscrite = $em->getRepository(CarteSouscrite::class)->find($id);
        $isFragment = (bool) $request->request->get('fragment');

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

        if (!$isFragment) {
            $this->addFlash('success', 'Justificatif vérifié avec succès.');
        }

        // ---- Mode scan continu : renvoyer le fragment mis à jour ----
        if ($request->request->get('fragment')) {
            return $this->fragmentResponse($carteSouscrite->getUser(), $request, $em, $groupeControleRepository, $controleAccesService, 'justificatif_verifie');
        }

        $redirectParams = ['id' => $carteSouscrite->getUser()->getId()];
        $groupeId = $request->request->getInt('groupe');
        if ($groupeId) {
            $redirectParams['groupe'] = $groupeId;
        }

        return $this->redirectToRoute('admin_user_check', $redirectParams);
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
        $isFragment = (bool) $request->request->get('fragment');
        $actionEffectuee = null;

        if (!$carteSouscrite) {
            throw $this->createNotFoundException(
                "Carte souscrite #$id introuvable"
            );
        }

        if (!$this->isCsrfTokenValid(
            'retirer_seance'.$carteSouscrite->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $groupeId = $request->request->getInt('groupe');
        $groupe = null;

        // Si un groupe est fourni, on vérifie que la carte y est autorisée.
        if ($groupeId) {
            $groupe = $groupeControleRepository->find($groupeId);

            if ($groupe === null || !$groupe->isActif()) {
                throw $this->createNotFoundException(
                    'Groupe de contrôle introuvable ou désactivé.'
                );
            }

            $controle = $controleAccesService->controler(
                $carteSouscrite->getUser(),
                $groupe
            );

            $carteAutorisee = false;

            foreach ($controle['cartes'] as $item) {
                if ($item['souscrite']->getId() === $carteSouscrite->getId()) {
                    $carteAutorisee = true;
                    break;
                }
            }

            if (!$carteAutorisee) {
                if ($request->request->get('fragment')) {
                    // fragment sans action : verdict recalculé, la carte
                    // concernée apparaîtra avec sa raison dans le détail
                    return $this->fragmentResponse(
                        $carteSouscrite->getUser(),
                        $request, $em, $groupeControleRepository, $controleAccesService
                    );
                }

                $this->addFlash('danger', 'Cette carte ne permet pas l’accès à ce groupe.');
                return $this->redirectToRoute('admin_user_check', [
                    'id' => $carteSouscrite->getUser()->getId(),
                    'groupe' => $groupe->getId(),
                ]);
            }
        }

        // Le justificatif tarif réduit n'empêche pas de retirer une séance.
        $consommation = $carteSouscrite->peutRetirerSeance();

        if (!$consommation->isValid) {
            if (!$isFragment) {
                $this->addFlash('warning', $consommation->reason);
            }
        } else {
            $carteSouscrite->setSeancesRestantes(
                $carteSouscrite->getSeancesRestantes() - 1
            );
            $em->flush();
            $actionEffectuee = 'seance_retiree';

            if (!$isFragment) {
                $this->addFlash('success', 'Une séance a été retirée de la carte.');
            }
        }

        // ---- Mode scan continu : renvoyer le fragment mis à jour ----
        if ($request->request->get('fragment')) {
            return $this->fragmentResponse($carteSouscrite->getUser(), $request, $em, $groupeControleRepository, $controleAccesService, $actionEffectuee);
        }

        // Retour à la page d'où vient l'action.
        if ($groupe !== null) {
            return $this->redirectToRoute('admin_user_check', [
                'id' => $carteSouscrite->getUser()->getId(),
                'groupe' => $groupe->getId(),
            ]);
        }

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Scan continu : rend le fragment de contrôle mis à jour (sans redirection).
     */
    private function fragmentResponse(
        \App\Entity\User $user,
        Request $request,
        EntityManagerInterface $em,
        GroupeControleRepository $groupeControleRepository,
        ControleAccesService $controleAccesService,
        ?string $actionEffectuee = null
    ): Response {
        $groupe = null;
        $controle = null;

        $groupeId = $request->request->getInt('groupe');
        if ($groupeId) {
            $groupe = $groupeControleRepository->find($groupeId);
            if ($groupe !== null && $groupe->isActif()) {
                $controle = $controleAccesService->controler($user, $groupe);
            }
        }

        $abonnements = $em->getRepository(\App\Entity\AbonnementSouscrit::class)
            ->findBy(['user' => $user], ['id' => 'DESC']);
        $cartes = $em->getRepository(CarteSouscrite::class)
            ->findBy(['user' => $user], ['id' => 'DESC']);

        return $this->render('admin/user/_check_fragment.html.twig', [
            'user' => $user,
            'groupe' => $groupe,
            'controle' => $controle,
            'abonnements' => $abonnements,
            'cartes' => $cartes,
            'action_effectuee' => $actionEffectuee
        ]);
    }
}
