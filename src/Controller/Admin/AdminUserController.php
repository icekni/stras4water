<?php

namespace App\Controller\Admin;

use App\Entity\Abonnement;
use App\Entity\AbonnementSouscrit;
use App\Entity\CarteSouscrite;
use App\Entity\Saison;
use App\Entity\User;
use App\Enum\Statut;
use App\Form\AdminUserType;
use App\Repository\UserRepository;
use App\Service\IdEncoderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/user')]
class AdminUserController extends AbstractController
{
    #[Route('/', name: 'admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
        ]);
    }#[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function form(?User $user, Request $request, EntityManagerInterface $em): Response
    {
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

        if ($form->isSubmitted() && $form->isValid()) {

            $abonnementsCoches = $form->get('abonnementsDisponibles')->getData();
            $cartesCochées = $form->get('cartesDisponibles')->getData();

            if ($isNew) {
                // Nouveau user => juste ajout de ce qui est coché
                foreach ($abonnementsCoches as $abonnement) {
                    $souscrit = new AbonnementSouscrit();
                    $souscrit->setUser($user);
                    $souscrit->setAbonnement($abonnement);
                    $souscrit->setStatut(Statut::ACTIVE);
                    $user->addAbonnementSouscrit($souscrit);
                    $em->persist($souscrit);
                }

                foreach ($cartesCochées as $carte) {
                    $souscrite = new CarteSouscrite();
                    $souscrite->setUser($user);
                    $souscrite->setCarte($carte);
                    $souscrite->setStatut(Statut::ACTIVE);
                    $souscrite->setSeancesRestantes($carte->getNombreSeances());
                    $user->addCarteSouscrite($souscrite);
                    $em->persist($souscrite);
                }

                $em->persist($user);
            } else {
                // Edition : suppression de ce qui n'est plus coché
                $abonnementsSouscritsExistants = clone $user->getAbonnementSouscrits();
                foreach ($abonnementsSouscritsExistants as $abonnementSouscrit) {
                    if (!in_array($abonnementSouscrit->getAbonnement(), $abonnementsCoches, true)) {
                        $user->removeAbonnementSouscrit($abonnementSouscrit);
                        $em->remove($abonnementSouscrit);
                    }
                }

                foreach ($abonnementsCoches as $abonnement) {
                    $dejaSouscrit = $user->getAbonnementSouscrits()->exists(
                        fn($key, $s) => $s->getAbonnement() === $abonnement
                    );
                    if (!$dejaSouscrit) {
                        $souscrit = new AbonnementSouscrit();
                        $souscrit->setUser($user);
                        $souscrit->setAbonnement($abonnement);
                        $souscrit->setStatut(Statut::ACTIVE);
                        $user->addAbonnementSouscrit($souscrit);
                        $em->persist($souscrit);
                    }
                }

                $cartesSouscritesExistantes = clone $user->getCarteSouscrites();
                foreach ($cartesSouscritesExistantes as $carteSouscrite) {
                    if (!in_array($carteSouscrite->getCarte(), $cartesCochées, true)) {
                        $user->removeCarteSouscrite($carteSouscrite);
                        $em->remove($carteSouscrite);
                    }
                }

                foreach ($cartesCochées as $carte) {
                    $dejaSouscrite = $user->getCarteSouscrites()->exists(
                        fn($key, $s) => $s->getCarte() === $carte
                    );
                    if (!$dejaSouscrite) {
                        $souscrite = new CarteSouscrite();
                        $souscrite->setUser($user);
                        $souscrite->setCarte($carte);
                        $souscrite->setStatut(Statut::ACTIVE);
                        $souscrite->setSeancesRestantes($carte->getNombreSeances());
                        $user->addCarteSouscrite($souscrite);
                        $em->persist($souscrite);
                    }
                }
            }

            $em->flush();

            $this->addFlash('success', $isNew ? 'Utilisateur créé avec succès.' : 'Utilisateur modifié avec succès.');
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/form.html.twig', [
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
        // dd($abonnements, $cartes);
        return $this->render('admin/user/check.html.twig', [
            'user'        => $user,
            'abonnements' => $abonnements,
            'cartes'      => $cartes,
        ]);
    }

    #[Route('/admin/user/scan', name: 'admin_user_scan')]
    public function scan(): Response
    {
        return $this->render('admin/user/scan.html.twig');
    }

    #[Route('/admin/user/scan/{hexId}', name: 'admin_user_scan_id')]
    public function scanId(int $hexId, UserRepository $userRepository, IdEncoderService $idEncoderService): Response
    {
        dd($hexId);

        return $this->render('admin/user/scan.html.twig');
    }
}
