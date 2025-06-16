<?php

namespace App\Service;

use App\Entity\Donation;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class EmailService {
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer){
        $this->mailer = $mailer;
    }

    function sendRecuFiscal(Donation $donation): void
    {
        $email = (new Email())
            ->from(new Address('contact@stras4water.org', 'Stras4Water'))
            ->to($donation->getEmail())
            ->subject('Votre recu fiscal')
            ->text('Bonjour, vous trouverez en pièce jointe le reçu fiscal pour votre don de ' . $donation->getMontant() . '€ à Stras4Water.')
            ->attachFromPath($donation->getUrlRecuFiscal(), 'recu-fiscal.pdf', 'application/pdf');

        $this->mailer->send($email);
    }

    function sendRequestFiscalData(Donation $donation, string $url): void
    {
        $email = (new Email())
            ->from(new Address('contact@stras4water.org', 'Stras4Water'))
            ->to($donation->getEmail())
            ->subject('Informations nécéssaires - Votre recu fiscal Stras4water')
            ->text('Bonjour, \nSuite à votre don de ' . $donation->getMontant() . ', vos pouvez générer votre recu fiscal en remplissant les informations sur ce lien : ' . $url);

        $this->mailer->send($email);
    }
}