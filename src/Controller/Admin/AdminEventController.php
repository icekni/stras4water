<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Entity\EventType;
use App\Form\EventFormType;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/event')]
class AdminEventController extends AbstractController
{
    #[Route('/', name: 'admin_event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        return $this->render('admin/event/index.html.twig', [
            'upcomingEvents' => $eventRepository->findUpcoming(),
            'pastEvents' => $eventRepository->findPast(10),
        ]);
    }

    #[Route('/new', name: 'admin_event_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $event = new Event();

        $form = $this->createForm(EventFormType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($event);
            $entityManager->flush();

            $this->addFlash('success', 'L’événement a été créé.');

            return $this->redirectToRoute('admin_event_show', [
                'id' => $event->getId(),
            ]);
        }

        return $this->render('admin/event/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/type/new', name: 'admin_event_type_new', methods: ['POST'])]
    public function newType(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $name = trim($request->request->get('name', ''));

        if ($name === '') {
            return $this->json([
                'success' => false,
                'message' => 'Le nom du type est obligatoire.',
            ], 400);
        }

        $type = new EventType();
        $type->setName($name);

        $entityManager->persist($type);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'id' => $type->getId(),
            'name' => $type->getName(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_event_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(EventFormType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'L’événement a été modifié.');

            return $this->redirectToRoute('admin_event_show', [
                'id' => $event->getId(),
            ]);
        }

        return $this->render('admin/event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id<\d+>}/delete', name: 'admin_event_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'delete_event_' . $event->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $entityManager->remove($event);
        $entityManager->flush();

        $this->addFlash('success', 'L’événement a été supprimé.');

        return $this->redirectToRoute('admin_event_index');
    }

    #[Route('/{id<\d+>}', name: 'admin_event_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        return $this->render('admin/event/show.html.twig', [
            'event' => $event,
        ]);
    }
}