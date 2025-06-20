<?php

namespace App\Service;

use App\Entity\Donation;
use App\Enum\MoyenPaiement;
use App\Enum\TypeDon;
use DateTimeImmutable;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class RecuFiscalService
{
    private string $projectDir;
    private MailerInterface $mailer;

    public function __construct(KernelInterface $kernel, MailerInterface $mailer){
        $this->projectDir = $kernel->getProjectDir();
        $this->mailer = $mailer;
    }
        // generate("RF2025-00001", $user, "50.05", $dateDon, new DateTimeImmutable(), TypeDon::NUMERAIRE, MoyenPaiement::CASH);
    public function generate(Donation $donation, string $numeroOrdre): Donation
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
        $pdf->Cell(52, 6.3, $numeroOrdre, 0, 0, 'C');

        // Dénomination Asso
        $pdf->SetXY(12, 56.5); 
        $pdf->Cell(185, 10, 'Stras4Water', 0, 0, 'L');
        // SIREN Asso
        $pdf->SetXY(68, 60); 
        $pdf->Cell(130, 10, '929 570 497', 0, 0, 'L');
        // N° rue Asso
        $pdf->SetXY(17, 70.5); 
        $pdf->Cell(20, 10, '1a', 0, 0, 'L');
        // Rue Asso
        $pdf->SetXY(47, 70.5); 
        $pdf->Cell(150, 10, 'Place des Orphelins', 0, 0, 'L');
        // Code postal Asso
        $pdf->SetXY(34, 75.5); 
        $pdf->Cell(27, 10, '67000', 0, 0, 'L');
        // Ville Asso
        $pdf->SetXY(81, 75.5); 
        $pdf->Cell(116, 10, 'Strasbourg', 0, 0, 'L');
        // Pays Asso
        $pdf->SetXY(23, 79.8); 
        $pdf->Cell(42, 10, 'France', 0, 0, 'L');
        // Objet Asso
        $objet = explode('|', wordwrap('Soutien à des projets humanitaires liés à l\'eau, l\'hygiène et l\'assainissement et actions locales de sensibilisation par des activités culturelles et solidaires.', 110, '|'));
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
        $pdf->Cell(100, 10, 'Association de droit local (Bas-Rhin, Haut-Rhin et Moselle)', 0, 0, 'L');

        $tpl = $pdf->importPage(2);
        $pdf->AddPage();
        $pdf->useTemplate($tpl);

        // art. 200 du CGI
        $pdf->SetXY(34, 152); 
        $pdf->Cell(5, 10, 'x', 0, 0, 'C');

        // Forme et nature du don
        switch ($donation->getTypeDon()) 
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
        switch ($donation->getMoyenPaiement()) 
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
        $pdf->Cell(70, 6.3, $donation->getNom(), 0, 0, 'L');
        $pdf->SetXY(124, 83.8); 
        $pdf->Cell(69, 6.3, $donation->getPrenom(), 0, 0, 'L');
        $pdf->SetXY(17, 94.5); 
        $pdf->Cell(20, 6.3, $donation->getAdresseNumero(), 0, 0, 'L');
        $pdf->SetXY(47, 94.5); 
        $pdf->Cell(145, 6.3, $donation->getAdresseRue(), 0, 0, 'L');
        $pdf->SetXY(34, 99.5); 
        $pdf->Cell(20, 6.3, $donation->getAdresseCodePostal(), 0, 0, 'L');
        $pdf->SetXY(81, 99.5); 
        $pdf->Cell(112, 6.3, $donation->getAdresseVille(), 0, 0, 'L');
        $pdf->SetXY(23, 104.8); 
        $pdf->Cell(80, 6.3, 'France', 0, 0, 'L');

        // Date du don
        $pdf->SetXY(64, 130.5); 
        $pdf->Cell(8, 6.3, $donation->getDate()->format('d'), 0, 0, 'C');
        $pdf->SetXY(74, 130.5); 
        $pdf->Cell(8, 6.3, $donation->getDate()->format('m'), 0, 0, 'C');
        $pdf->SetXY(84, 130.5); 
        $pdf->Cell(10, 6.3, $donation->getDate()->format('Y'), 0, 0, 'C');

        // Cadre signature
        $pdf->SetXY(108.5, 221.5); 
        $pdf->Cell(6, 6.3, $donation->getDate()->format('d'), 0, 0, 'C');
        $pdf->SetXY(115.6, 221.5); 
        $pdf->Cell(6, 6.3, $donation->getDate()->format('m'), 0, 0, 'C');
        $pdf->SetXY(122.7, 221.5); 
        $pdf->Cell(8, 6.3, $donation->getDate()->format('Y'), 0, 0, 'C');
        $pdf->SetFontSize('6'); 
        $pdf->SetXY(110, 234); 
        $pdf->Cell(70, 6.3, mb_convert_encoding("Document généré électroniquement", 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
        $pdf->SetXY(110, 236); 
        $pdf->Cell(70, 6.3, mb_convert_encoding("sans signature manuscrite originale", 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
        $pdf->Image('recuFiscaux/sign.png', 150, 215, 20);

        $filePath = $this->projectDir . '/public/recuFiscaux/' . $numeroOrdre . '.pdf';
        $pdf->Output($filePath, 'F');

        $donation->setUrlRecuFiscal($filePath);

        $this->sendByEmail($donation);

        return $donation;
    }
    
    private function sendByEmail(Donation $donation): void
    {
        $email = (new Email())
            ->from('contact@stras4water.org')
            ->to($donation->getEmail())
            ->subject('Votre recu fiscal')
            ->text('Bonjour, vous trouverez en pièce jointe le reçu fiscal pour votre don de ' . $donation->getMontant() . '€ à Stras4Water.')
            ->attachFromPath($donation->getUrlRecuFiscal(), 'recu-fiscal.pdf', 'application/pdf');

        $this->mailer->send($email);
    }
}