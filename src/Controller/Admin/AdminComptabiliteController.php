<?php

namespace App\Controller\Admin;

use App\Service\ComptabiliteCsvExporter;
use App\Service\ComptabiliteService;
use App\Service\CsvExporterService;
use App\Service\EmailService;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/comptabilite')]
class AdminComptabiliteController extends AbstractController
{
    #[Route('', name: 'admin_comptabilite_index', methods: ['GET'])]
    public function index(
        Request $request,
        ComptabiliteService $comptabiliteService,
    ): Response {
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        $fromDate = $from ? new DateTimeImmutable($from . ' 00:00:00') : null;
        $toDate = $to ? new DateTimeImmutable($to . ' 23:59:59') : null;

        $lignes = $comptabiliteService->getLignes($fromDate, $toDate);

        return $this->render('admin/comptabilite/index.html.twig', [
            'lignes' => $lignes,
            'from' => $from,
            'to' => $to,
        ]);
    }

    #[Route('/export', name: 'admin_comptabilite_export', methods: ['GET'])]
    public function export(
        Request $request,
        ComptabiliteService $comptabiliteService,
        CsvExporterService $csvExporter,
    ): Response {
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        $fromDate = $from ? new DateTimeImmutable($from . ' 00:00:00') : null;
        $toDate = $to ? new DateTimeImmutable($to . ' 23:59:59') : null;

        $lignes = $comptabiliteService->getLignes($fromDate, $toDate);

        return $csvExporter->export($lignes);
    }

    #[Route('/export/mail', name: 'admin_comptabilite_export_mail', methods: ['POST'])]
    public function exportMail(
        Request $request,
        ComptabiliteService $comptabiliteService,
        CsvExporterService $csvExporter,
        EmailService $emailService,
    ): Response {
        if (!$this->isCsrfTokenValid('export_csv_mail', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $from = $request->request->get('from');
        $to = $request->request->get('to');
        $email = $request->request->get('email');

        $fromDate = $from ? new DateTimeImmutable($from.' 00:00:00') : null;
        $toDate = $to ? new DateTimeImmutable($to.' 23:59:59') : null;

        $lignes = $comptabiliteService->getLignes($fromDate, $toDate);

        $csv = $csvExporter->generate($lignes);

        $emailService->sendComptabiliteCsv($email, $csv);

        $this->addFlash('success', 'Le CSV a été envoyé.');

        return $this->redirectToRoute('admin_comptabilite_index', [
            'from' => $from,
            'to' => $to,
        ]);
    }
}