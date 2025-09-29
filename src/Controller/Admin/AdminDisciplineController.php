<?php

namespace App\Controller\Admin;

use App\Entity\Discipline;
use App\Form\DisciplineType;
use App\Repository\DisciplineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/disciplines', name: 'admin_discipline_')]
class AdminDisciplineController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(DisciplineRepository $disciplineRepository): Response
    {
        return $this->render('admin/discipline/index.html.twig', [
            'disciplines' => $disciplineRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $discipline = new Discipline();
        $form = $this->createForm(DisciplineType::class, $discipline);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($discipline);
            $em->flush();

            $this->addFlash('success', 'Discipline ajoutée avec succès.');
            return $this->redirectToRoute('admin_discipline_index');
        }

        return $this->render('admin/discipline/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Discipline $discipline, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(DisciplineType::class, $discipline);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Discipline modifiée avec succès.');
            return $this->redirectToRoute('admin_discipline_index');
        }

        return $this->render('admin/discipline/form.html.twig', [
            'form' => $form->createView(),
            'discipline' => $discipline,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Discipline $discipline, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_discipline_' . $discipline->getId(), $request->request->get('_token'))) {
            $em->remove($discipline);
            $em->flush();
            $this->addFlash('success', 'Discipline supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_discipline_index');
    }
}
