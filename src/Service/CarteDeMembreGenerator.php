<?php

namespace App\Service;

use App\Entity\User;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpKernel\KernelInterface;

class CarteDeMembreGenerator
{
    private string $projectDir;

    public function __construct(
        private KernelInterface $kernel,
        private QrCodeGenerator $qrCodeGenerator
    ) {
        $this->projectDir = $kernel->getProjectDir();
    }

    public function generate(User $user, string $annee): ?string
    {
        if (!$user->isAdherent()) {
            return null;
        }

        // Générer le QR code (retourne chemin absolu d'une image PNG)
        $qrCodePath = $this->qrCodeGenerator->generateMembre($user);

        $pdf = new Fpdi();
        $pdf->setSourceFile('files/badge.pdf');
        $template = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($template);
        $pdf->SetMargins(0, 0, 0);
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($template);

        // Année d’adhésion
        $pdf->SetFont('Helvetica', 'b', 15.5);
        $pdf->SetTextColor(1, 67, 96);
        $pdf->Text(55, 20.4, $annee);

        // Nom & prénom
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->SetTextColor(0, 118, 168);
        $pdf->Text(4, 37, mb_convert_encoding($user->getNom(), 'Windows-1252', 'UTF-8'));
        $pdf->Text(4, 50, mb_convert_encoding($user->getPrenom(), 'Windows-1252', 'UTF-8'));

        // Intégrer le QR code
        if (file_exists($qrCodePath)) {
            $pdf->Image($qrCodePath, 61, 25, 26, 26); // X, Y, largeur, hauteur
        }

        $filePath = $this->projectDir . '/public/cartesMembre/' . $user->getId() . '.pdf';
        $pdf->Output($filePath, 'F');

        return $filePath;
    }
}
