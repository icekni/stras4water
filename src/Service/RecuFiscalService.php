<?php

namespace App\Service;

use App\Entity\Donation;
use App\Enum\MoyenPaiement;
use App\Enum\TypeDon;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpKernel\KernelInterface;

class RecuFiscalService
{
    private string $projectDir;
    private EmailService $emailService;

    public function __construct(KernelInterface $kernel, EmailService $emailService){
        $this->projectDir = $kernel->getProjectDir();
        $this->emailService = $emailService;
    }
        // generate("RF2025-00001", $user, "50.05", $dateDon, new DateTimeImmutable(), TypeDon::NUMERAIRE, MoyenPaiement::CASH);
    public function generate(string $numeroOrdre, 
        Donation $donation,
        TypeDon $typeDon, 
        MoyenPaiement $moyenPaiement, 
        string $nom, 
        string $prenom, 
        string $numeroRue, 
        string $rue, 
        string $codePostal, 
        string $ville, 
        string $pays
    ): Donation
    {
        $pdf = new Fpdi();
        $pagecount = $pdf->setSourceFile('recuFiscaux/modele-vierge.pdf');

        $tpl = $pdf->importPage(1);
        $pdf->AddPage();
        $pdf->useTemplate($tpl);
        $pdf->SetFont('Helvetica');
        $pdf->SetTextColor(0, 0, 255);

        $pdf->SetFontSize('10'); 

        // Numéro d'ordre du recu
        $pdf->SetXY(148, 39.5); 
        $pdf->Cell(52, 6.3, mb_convert_encoding($numeroOrdre, 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');

        // Dénomination Asso
        $pdf->SetXY(12, 56.5); 
        $pdf->Cell(185, 10, mb_convert_encoding($_ENV['DENOMINATIONASSO'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        // SIREN Asso
        $pdf->SetXY(68, 60); 
        $pdf->Cell(130, 10, mb_convert_encoding($_ENV['SIRENASSO'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        // N° rue Asso
        $pdf->SetXY(17, 70.5); 
        $pdf->Cell(20, 10, mb_convert_encoding($_ENV['NUMRUEASSO'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        // Rue Asso
        $pdf->SetXY(47, 70.5); 
        $pdf->Cell(150, 10, mb_convert_encoding($_ENV['RUEASSO'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        // Code postal Asso
        $pdf->SetXY(34, 75.5); 
        $pdf->Cell(27, 10, mb_convert_encoding($_ENV['CPASSO'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        // Ville Asso
        $pdf->SetXY(81, 75.5); 
        $pdf->Cell(116, 10, mb_convert_encoding($_ENV['VILLEASSO'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        // Pays Asso
        $pdf->SetXY(23, 79.8); 
        $pdf->Cell(42, 10, mb_convert_encoding($_ENV['PAYSASSO'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        // Objet Asso
        $objet = explode('|', wordwrap($_ENV['OBJETASSO'], 110, '|'));
        $pdf->SetXY(24, 84); 
        $pdf->Cell(173, 10, mb_convert_encoding($objet[0], 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $pdf->SetXY(12, 87.7); 
        $pdf->Cell(185, 10, mb_convert_encoding($objet[1], 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        // Type Asso
        $pdf->SetXY(10.9, 134.8); 
        $pdf->Cell(5, 10, 'x', 0, 0, 'C');
        $pdf->SetXY(21.4, 170.6); 
        $pdf->Cell(5, 10, 'x', 0, 0, 'C');
        $pdf->SetXY(53, 170.3); 
        $pdf->Cell(100, 10, mb_convert_encoding($_ENV['TYPEASSO'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');

        $tpl = $pdf->importPage(2);
        $pdf->AddPage();
        $pdf->useTemplate($tpl);

        // art. 200 du CGI
        $pdf->SetXY(34, 152); 
        $pdf->Cell(5, 10, 'x', 0, 0, 'C');

        // Forme et nature du don
        switch ($typeDon) 
        {
            case TypeDon::RENONCEMENT_FRAIS:
                $pdf->SetXY(169.2, 165); 
                $pdf->Cell(5, 10, 'x', 0, 0, 'C');
                $pdf->SetXY(11, 185); 
                $pdf->Cell(5, 10, 'x', 0, 0, 'C');
                break;
            case TypeDon::NUMERAIRE:
            default:
                $pdf->SetXY(101.4, 165); 
                $pdf->Cell(5, 10, 'x', 0, 0, 'C');
                $pdf->SetXY(11, 178); 
                $pdf->Cell(5, 10, 'x', 0, 0, 'C');
                break;
        }
        // Mode de versement du don
        switch ($moyenPaiement) 
        {
            case MoyenPaiement::CASH:
                $pdf->SetXY(11, 202); 
                $pdf->Cell(5, 10, 'x', 0, 0, 'C');
                break;
            case MoyenPaiement::CHEQUE:
                $pdf->SetXY(51.7, 202); 
                $pdf->Cell(5, 10, 'x', 0, 0, 'C');
                break;
            case MoyenPaiement::CARTE:
            case MoyenPaiement::VIREMENT:
                $pdf->SetXY(101.3, 202); 
                $pdf->Cell(5, 10, 'x', 0, 0, 'C');
                break;
        }

        // Montant du don
        $pdf->SetXY(15, 123.8); 
        $pdf->Cell(40, 6.3, number_format($donation->getMontant(), 2, ',', ''), 0, 0, 'C');
        $pdf->SetXY(125, 123.5); 
        $pdf->Cell(70, 6.3, '***' . number_format($donation->getMontant(), 2, ',', '') . chr(128) . '***', 0, 0, 'L');

        // Données du donateur
        $pdf->SetXY(23, 83.8); 
        $pdf->Cell(70, 6.3, mb_convert_encoding($nom, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $pdf->SetXY(124, 83.8); 
        $pdf->Cell(69, 6.3, mb_convert_encoding($prenom, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $pdf->SetXY(17, 94.5); 
        $pdf->Cell(20, 6.3, mb_convert_encoding($numeroRue, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $pdf->SetXY(47, 94.5); 
        $pdf->Cell(145, 6.3, mb_convert_encoding($rue, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $pdf->SetXY(34, 99.5); 
        $pdf->Cell(20, 6.3, mb_convert_encoding($codePostal, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $pdf->SetXY(81, 99.5); 
        $pdf->Cell(112, 6.3, mb_convert_encoding($ville, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $pdf->SetXY(23, 104.8); 
        $pdf->Cell(80, 6.3, mb_convert_encoding($pays, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');

        // Date du don
        $pdf->SetXY(64, 130.5); 
        $pdf->Cell(8, 6.3, $donation->getCreatedAt()->format('d'), 0, 0, 'C');
        $pdf->SetXY(74, 130.5); 
        $pdf->Cell(8, 6.3, $donation->getCreatedAt()->format('m'), 0, 0, 'C');
        $pdf->SetXY(84, 130.5); 
        $pdf->Cell(10, 6.3, $donation->getCreatedAt()->format('Y'), 0, 0, 'C');

        // Cadre signature
        $pdf->SetXY(108.5, 221.5); 
        $pdf->Cell(6, 6.3, $donation->getCreatedAt()->format('d'), 0, 0, 'C');
        $pdf->SetXY(115.6, 221.5); 
        $pdf->Cell(6, 6.3, $donation->getCreatedAt()->format('m'), 0, 0, 'C');
        $pdf->SetXY(122.7, 221.5); 
        $pdf->Cell(8, 6.3, $donation->getCreatedAt()->format('Y'), 0, 0, 'C');
        $pdf->SetFontSize('6'); 
        $pdf->SetXY(110, 234); 
        $pdf->Cell(70, 6.3, mb_convert_encoding("Document généré électroniquement", 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
        $pdf->SetXY(110, 236); 
        $pdf->Cell(70, 6.3, mb_convert_encoding("sans signature manuscrite originale", 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
        $pdf->Image($_ENV['URLSIGNATURE'], 150, 215, 20);

        $filePath = $this->projectDir . '/public/recuFiscaux/' . $numeroOrdre . '.pdf';
        $pdf->Output($filePath, 'F');

        $donation->setUrlRecuFiscal($filePath);

        $this->emailService->sendRecuFiscal($donation);

        return $donation;
    }

    public function invalidate(Donation $donation): Donation 
    {
        $pdf = new Fpdi();
        $pagecount = $pdf->setSourceFile($donation->getUrlRecuFiscal());

        $text = explode("\n", wordwrap("Stras4Water certifie que le don initialement enregistré le " . $donation->getCreatedAt()->format('d/m/y') . ", d’un montant de " . $donation->getMontant() . "€, a été remboursé.\nCe reçu annule et remplace le reçu n° " . $donation->getNumeroOrdreRF() . ".\nAucun avantage fiscal ne peut être obtenu au titre de ce don.", 75));

        for ($i = 1; $i <= $pagecount; $i++) {
            $tpl = $pdf->importPage($i);
            $pdf->AddPage();
            $pdf->useTemplate($tpl);
            $pdf->SetFont('Helvetica');
            $pdf->SetTextColor(255, 0, 0);
            $pdf->SetFillColor(255, 255, 255);

            $y = 123.8;
            foreach ($text as $ligne) {
                $pdf->SetXY(10, $y); 
                $pdf->Cell(200, 10, mb_convert_encoding($ligne, 'Windows-1252', 'UTF-8'), 0, 0, 'C', true);
                $y += 10;
            }
        }

        $pdf->Output($donation->getUrlRecuFiscal(), 'F');

        // $this->emailService->sendRecuFiscal($donation);

        return $donation;
    }
}