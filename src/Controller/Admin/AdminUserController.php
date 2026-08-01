<?php

namespace App\Controller\Admin;

use App\Entity\Abonnement;
use App\Entity\AbonnementSouscrit;
use App\Entity\Adhesion;
use App\Entity\CarteSouscrite;
use App\Entity\Saison;
use App\Entity\User;
use App\Enum\Statut;
use App\Form\AbonnementSouscritType;
use App\Form\AdhesionType;
use App\Form\AdminUserType;
use App\Form\CarteSouscriteType;
use App\Repository\AdhesionRepository;
use App\Repository\UserRepository;
use App\Service\CarteDeMembreGenerator;
use App\Service\CsvExporterService;
use App\Service\EmailService;
use App\Service\IdEncoderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ACCUEIL')]
#[Route('/admin/user')]
class AdminUserController extends AbstractController
{
    #[Route('/', name: 'admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        $users = $userRepository->findAll();
        
        $search = $request->query->get('search');

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'search' => $search,
        ]);
    }
    
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $em): Response
    {
        // Protection CSRF
        if (!$this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_user_index');
        }

        // Suppression automatique en cascade via orphanRemoval=true
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        return $this->redirectToRoute('admin_user_index');
    }
    
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(
        ?User $user,
        Request $request,
        EntityManagerInterface $em,
        CarteDeMembreGenerator $carteDeMembreGenerator,
        UserRepository $userRepository,
        EmailService $emailService
    ): Response {
        $user = new User();

        $form = $this->createForm(AdminUserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // ✅ Vérification si un user existe déjà avec le même email
            if ($user->getEmail()) {
                $existingUser = $userRepository->findOneBy(['email' => $user->getEmail()]);
                if ($existingUser) {
                    $this->addFlash('warning', sprintf(
                        "Un utilisateur avec l’email %s existe déjà. Vous pouvez le modifier directement.",
                        $user->getEmail()
                    ));
                    return $this->redirectToRoute('admin_user_edit', ['id' => $existingUser->getId()]);
                }
            }

            if ($form->isValid()) {
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Utilisateur créé avec succès.');
                return $this->redirectToRoute('admin_user_index', [
                    'search' => $user->getEmail(),
                ]);
            }
        }

        return $this->render('admin/user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        ?User $user,
        Request $request,
        EntityManagerInterface $em,
        CarteDeMembreGenerator $carteDeMembreGenerator,
        UserRepository $userRepository
    ): Response {
        $isNew = false;

        if (!$user) {
            $user = new User();
            $isNew = true;
        }

        $abonnementsDejaSouscrits = $user->getAbonnementSouscrits()->map(fn($s) => $s->getAbonnement())->toArray();
        $cartesDejaSouscrites = $user->getCarteSouscrites()->map(fn($s) => $s->getCarte())->toArray();

        $form = $this->createForm(AdminUserType::class, $user, [
            'abonnements_souscrits' => $abonnementsDejaSouscrits,
            'cartes_souscrites' => $cartesDejaSouscrites,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // ✅ Vérification si un user existe déjà avec le même email
            if ($isNew && $user->getEmail()) {
                $existingUser = $userRepository->findOneBy(['email' => $user->getEmail()]);
                if ($existingUser) {
                    $this->addFlash('warning', sprintf(
                        "Un utilisateur avec l’email %s existe déjà. Vous pouvez le modifier directement.",
                        $user->getEmail()
                    ));
                    return $this->redirectToRoute('admin_user_edit', ['id' => $existingUser->getId()]);
                }
            }

            if ($form->isValid()) {
                $em->flush();

                $carteDeMembreGenerator->generate($user, $this->getSaisonAdhesion());

                $this->addFlash('success', $isNew ? 'Utilisateur créé avec succès.' : 'Utilisateur modifié avec succès.');
                return $this->redirectToRoute('admin_user_index');
            }
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/{id}/check', name: 'admin_user_check')]
    public function check(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException("Utilisateur #$id introuvable");
        }

        $abonnements = $em->getRepository(AbonnementSouscrit::class)->findBy(
            ['user' => $user],
            ['id' => 'DESC']
        );

        $cartes = $em->getRepository(CarteSouscrite::class)->findBy(
            ['user' => $user],
            ['id' => 'DESC']
        );

        return $this->render('admin/user/check.html.twig', [
            'user'        => $user,
            'abonnements' => $abonnements,
            'cartes'      => $cartes,
        ]);
    }

    #[Route('/scan', name: 'admin_user_scan')]
    public function scan(): Response
    {
        return $this->render('admin/user/scan.html.twig');
    }

    #[Route('/scan/{hexId}', name: 'admin_user_scan_id', methods: ['GET', 'POST'])]
    public function scanId(string $hexId, UserRepository $userRepository, IdEncoderService $idEncoderService): Response
    {
        $id = $idEncoderService->decode($hexId);
        $user = $userRepository->find($id);

        if ($user == null) {
            throw $this->createNotFoundException("Utilisateur #$id introuvable");
        }

        return $this->redirectToRoute('admin_user_check', ['id' => $user->getId()]);
    }

    #[Route('/{id}/adhesion/add', name: 'admin_user_adhesion_add')]
    public function ajouterAdhesion(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        CarteDeMembreGenerator $carteDeMembreGenerator,
        EmailService $emailService
    ): Response {
        $adhesion = new Adhesion();
        $adhesion->setUser($user);
        $adhesion->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(AdhesionType::class, $adhesion);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($adhesion);
            $em->flush();

            $carteDeMembreGenerator->generate($user, $this->getSaisonAdhesion());            
            $emailService->sendMembershipCard($user);

            return $this->redirectToRoute('admin_user_edit', [
                'id' => $user->getId(),
            ]);
        }

        return $this->render('admin/user/addAdhesion.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/adhesion/{id}/delete', name: 'admin_user_adhesion_delete', methods: ['POST'])]
    public function supprimerAdhesion(
        Adhesion $adhesion,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        if (!$this->isCsrfTokenValid('delete_adhesion' . $adhesion->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $userId = $adhesion->getUser()->getId();

        $em->remove($adhesion);
        $em->flush();

        return $this->redirectToRoute('admin_user_edit', [
            'id' => $userId,
        ]);
    }

    #[Route('/{id}/abonnement/add', name: 'admin_abonnement_ajouter')]
    public function ajouterAbonnement(
        User $user,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $abonnementSouscrit = new AbonnementSouscrit();
        $abonnementSouscrit->setUser($user);

        $form = $this->createForm(
            AbonnementSouscritType::class,
            $abonnementSouscrit
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($abonnementSouscrit);
            $em->flush();

            return $this->redirectToRoute('admin_user_edit', [
                'id' => $user->getId(),
            ]);
        }

        return $this->render('admin/user/addAbonnement.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/abonnement/{id}/delete', name: 'admin_user_abonnement_delete', methods: ['POST'])]
    public function supprimerAbonnement(
        AbonnementSouscrit $abonnementSouscrit,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid(
            'delete_abonnement' . $abonnementSouscrit->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException();
        }

        $userId = $abonnementSouscrit->getUser()->getId();

        $em->remove($abonnementSouscrit);
        $em->flush();

        return $this->redirectToRoute('admin_user_edit', [
            'id' => $userId,
        ]);
    }

    #[Route('/{id}/carte/add', name: 'admin_user_carte_add')]
    public function ajouterCarte(
        User $user,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $carteSouscrite = new CarteSouscrite();
        $carteSouscrite->setUser($user);
        $carteSouscrite->setSeancesRestantes(10);

        $form = $this->createForm(CarteSouscriteType::class, $carteSouscrite);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($carteSouscrite);
            $em->flush();

            return $this->redirectToRoute('admin_user_edit', [
                'id' => $user->getId(),
            ]);
        }

        return $this->render('admin/user/addCarte.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/carte/{id}/delete', name: 'admin_user_carte_delete', methods: ['POST'])]
    public function supprimerCarte(
        CarteSouscrite $carteSouscrite,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid(
            'delete_carte' . $carteSouscrite->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException();
        }

        $userId = $carteSouscrite->getUser()->getId();

        $em->remove($carteSouscrite);
        $em->flush();

        return $this->redirectToRoute('admin_user_edit', [
            'id' => $userId,
        ]);
    }

    #[Route('/{id}/send-card', name: 'admin_user_send_card', methods: ['POST'])]
    public function sendCard(
        User $user,
        Request $request,
        EmailService $emailService,
    ): Response {

        if (!$this->isCsrfTokenValid('send_card_'.$user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $emailService->sendMembershipCard($user);

        $this->addFlash('success', 'La carte de membre a été envoyée.');

        return $this->redirectToRoute('admin_user_index', [
            'id' => $user->getId(),
        ]);
    }

    #[Route('/export', name: 'admin_user_export', methods: ['GET'])]
    public function export(
        AdhesionRepository $adhesionRepository,
        CsvExporterService $csvExporter,
    ): Response {
        $users = array_map(fn($adhesion) => $adhesion->getUser(), $adhesionRepository->findAll());
        
        return $csvExporter->exportAdherents($users);
    }

    private function getSaisonAdhesion(): string
    {
        $now = new \DateTimeImmutable();
        $year = (int) $now->format('Y');
        $month = (int) $now->format('n'); // 1 à 12

        if ($month >= 9) {
            return $year . '/' . ($year + 1);
        } else {
            return ($year - 1) . '/' . $year;
        }
    }
}
