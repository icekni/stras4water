<?php

namespace App\Service;

use App\Entity\Donation;
use App\Entity\User;
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
            ->htmlTemplate('emails/recu_fiscal_email.html.twig')
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
            ->htmlTemplate('emails/request_fiscal_data.html.twig')
                    ->context([
                        'donation' => $donation,
                        'url' => $url,
                    ]);
        $this->mailer->send($email);
    }

    function sendMail(string $nom, string $from, string $subject, string $text): void
    {
        $email = (new Email())
            ->from($_ENV['EMAIL_CONTACT'])
            ->to($_ENV['EMAIL_CONTACT'])
            ->subject("Nouveau message de : " . $from . " - " . $nom . " : " . $subject)
            ->text($text);

        $this->mailer->send($email);
    }

    public function sendConfirmationCommande(User $user, bool $withAdhesionCard, ?string $pdfCard = null, array $detailsCommande = []): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('contact@stras4water.org', 'Stras4Water'))
            ->to($user->getEmail())
            ->subject('Confirmation de votre commande Stras4Water')
            ->htmlTemplate('emails/confirmation_commande.html.twig')
            ->context([
                'user' => $user,
                'withAdhesionCard' => $withAdhesionCard,
                'commandes' => $detailsCommande,
            ]);

        if ($withAdhesionCard && $pdfCard) {
            $email->attach($pdfCard, 'carte-adhesion.pdf', 'application/pdf');
        }

        $this->mailer->send($email);
    }
}