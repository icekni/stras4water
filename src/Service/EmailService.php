<?php

namespace App\Service;

use App\Entity\Donation;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
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
        $email = (new TemplatedEmail())
            ->from(new Address('contact@stras4water.org', 'Stras4Water'))
            ->to($donation->getEmail())
            ->subject('Votre reçu fiscal Stras4Water est disponible')
            ->htmlTemplate('donation/recu_fiscal_email.html.twig')
            ->attachFromPath($donation->getUrlRecuFiscal(), 'recu-fiscal.pdf', 'application/pdf')
            ->context([
                'don' => $donation->getMontant(),
            ]);

        $this->mailer->send($email);
    }

    function sendRequestFiscalData(Donation $donation, string $url): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('contact@stras4water.org', 'Stras4Water'))
            ->to($donation->getEmail())
            ->subject('Complétez vos informations pour recevoir votre reçu fiscal')
            ->htmlTemplate('donation/request_fiscal_data.html.twig')
                    ->context([
                        'donation' => $donation,
                        'url' => $url,
                    ]);
        $this->mailer->send($email);
    }

    function sendMail(string $nom, string $from, string $subject, string $text): void
    {
        $email = (new Email())
            ->from(new Address($from, $nom))
            ->to($_ENV['EMAIL_CONTACT'])
            ->subject($subject)
            ->text($text);

        $this->mailer->send($email);
    }
}