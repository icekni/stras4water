<?php

namespace App\Controller\Admin;

use App\Dto\InscriptionData;
use App\Entity\User;
use App\Form\InscriptionType;
use App\Repository\CarteRepository;
use App\Repository\SaisonRepository;
use App\Service\InscriptionService;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ACCUEIL')]
#[Route('/admin')]
class AdminInscriptionController extends AbstractController
{
    #[Route(
        '/inscription',
        name: 'admin_inscription_new',
        methods: ['GET', 'POST']
    )]
    public function new(
        Request $request,
        SaisonRepository $saisonRepository,
        CarteRepository $carteRepository,
        InscriptionService $inscriptionService
    ): Response {
        $data = new InscriptionData();

        $form = $this->createForm(
            InscriptionType::class,
            $data,
            $this->getFormOptions(
                $saisonRepository,
                $carteRepository
            )
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $inscriptionService->inscrire(
                    $data
                );
            } catch (InvalidArgumentException $e) {
                $this->addFlash('danger', $e->getMessage());

                return $this->render(
                    'admin/inscription/new.html.twig',
                    [
                        'form' => $form,
                        'existingUser' => null,
                    ]
                );
            }

            $this->addFlash(
                'success',
                sprintf(
                    'Inscription de %s %s enregistrée.',
                    $user->getPrenom(),
                    $user->getNom()
                )
            );

            return $this->redirectToRoute('admin_user_check', [
                'id' => $user->getId(),
            ]);
        }

        return $this->render('admin/inscription/new.html.twig', [
            'form' => $form,
            'existingUser' => null,
        ]);
    }

    #[Route(
        '/user/{id}/inscription',
        name: 'admin_user_inscription',
        methods: ['GET', 'POST']
    )]
    public function existingUser(
        User $user,
        Request $request,
        SaisonRepository $saisonRepository,
        CarteRepository $carteRepository,
        InscriptionService $inscriptionService
    ): Response {
        $data = new InscriptionData();

        $form = $this->createForm(
            InscriptionType::class,
            $data,
            $this->getFormOptions(
                $saisonRepository,
                $carteRepository,
                $user
            )
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $inscriptionService->inscrire(
                    $data,
                    $user
                );
            } catch (InvalidArgumentException $e) {
                $this->addFlash('danger', $e->getMessage());

                return $this->render(
                    'admin/inscription/new.html.twig',
                    [
                        'form' => $form,
                        'existingUser' => $user,
                    ]
                );
            }

            $this->addFlash(
                'success',
                'Modification enregistrée avec succès.'
            );

            return $this->redirectToRoute('admin_user_check', [
                'id' => $user->getId(),
            ]);
        }

        return $this->render('admin/inscription/new.html.twig', [
            'form' => $form,
            'existingUser' => $user,
        ]);
    }

    private function getFormOptions(
        SaisonRepository $saisonRepository,
        CarteRepository $carteRepository,
        ?User $existingUser = null
    ): array {
        $saisonsActives = $saisonRepository->findBy(
            ['isActif' => true],
            ['dateDebut' => 'ASC']
        );

        $abonnements = [];

        foreach ($saisonsActives as $saison) {
            foreach ($saison->getAbonnements() as $abonnement) {
                if ($abonnement->isActif()) {
                    $abonnements[] = $abonnement;
                }
            }
        }

        $cartes = $carteRepository->findBy([
            'isActif' => true,
        ]);

        return [
            'abonnements' => $abonnements,
            'cartes' => $cartes,
            'existing_user' => $existingUser,
        ];
    }
}