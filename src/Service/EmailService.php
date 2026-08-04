<?php

namespace App\Service;

use App\Entity\Donation;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class EmailService {
    private MailerInterface $mailer;
    private ParameterBagInterface $parameterBag;

    public function __construct(MailerInterface $mailer, ParameterBagInterface $parameterBag){
        $this->mailer = $mailer;
        $this->parameterBag = $parameterBag;
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
        $groupesWhatsapp = [
            'bachata' => false,
            'salsa' => false,
            'anglais' => false,
            'espagnol' => false,
        ];

        foreach ($detailsCommande as $detail) {
            if ($detail['type'] === 'Abonnement') {
                foreach ($user->getAbonnementSouscrits() as $abonnementSouscrit) {
                    if ($abonnementSouscrit->getId() != $detail['id']) {
                        continue;
                    }

                    $discipline = $abonnementSouscrit
                        ->getAbonnement()
                        ->getDiscipline()
                        ->getNom();

                    switch ($discipline) {
                        case 'Bachata':
                            $groupesWhatsapp['bachata'] = true;
                            break;
                        case 'Salsa':
                            $groupesWhatsapp['salsa'] = true;
                            break;
                        case 'Anglais':
                            $groupesWhatsapp['anglais'] = true;
                            break;
                        case 'Espagnol':
                            $groupesWhatsapp['espagnol'] = true;
                            break;
                    }
                    break;
                }
            }

            if ($detail['type'] === 'Carte') {
                foreach ($user->getCarteSouscrites() as $carteSouscrite) {
                    if ($carteSouscrite->getId() != $detail['id']) {
                        continue;
                    }

                    foreach ($carteSouscrite->getCarte()->getDisciplines() as $discipline) {
                        switch ($discipline->getNom()) {
                            case 'Bachata':
                                $groupesWhatsapp['bachata'] = true;
                                break;
                            case 'Salsa':
                                $groupesWhatsapp['salsa'] = true;
                                break;
                            case 'Anglais':
                                $groupesWhatsapp['anglais'] = true;
                                break;
                            case 'Espagnol':
                                $groupesWhatsapp['espagnol'] = true;
                                break;
                        }
                    }
                    break;
                }
            }
        }

        $email = (new TemplatedEmail())
            ->from(new Address('contact@stras4water.org', 'Stras4Water'))
            ->to($user->getEmail())
            ->subject('Confirmation de votre commande Stras4Water')
            ->htmlTemplate('emails/confirmation_commande.html.twig')
            ->context([
                'user' => $user,
                'withAdhesionCard' => $withAdhesionCard,
                'details' => $detailsCommande,
                'groupesWhatsapp' => $groupesWhatsapp,
            ]);

        if ($withAdhesionCard && $pdfCard) {
            $email->attachFromPath($pdfCard, 'carte-de-membre.pdf', 'application/pdf');
        }
        
        $this->mailer->send($email);
    }

    public function sendComptabiliteCsv(string $emailDestinataire, string $csv): void
    {
        $email = (new Email())
            ->from(new Address('contact@stras4water.org', 'Stras4Water'))
            ->to($emailDestinataire)
            ->subject('Export comptabilité')
            ->text('Veuillez trouver le CSV en pièce jointe.')
            ->attach(
                $csv,
                'comptabilite_' . date('Y-m-d') . '.csv',
                'text/csv'
            );

        $this->mailer->send($email);
    }

    public function sendMembershipCard(User $user): void
    {
        $pdf = $this->parameterBag->get('kernel.project_dir')
            . '/public/cartesMembre/' . $user->getId() . '.pdf';

        $email = (new TemplatedEmail())
            ->from(new Address('contact@stras4water.org', 'Stras4Water'))
            ->to($user->getEmail())
            ->subject('Votre carte de membre Stras4Water')
            ->htmlTemplate('emails/carte_membre.html.twig')
            ->attachFromPath(
                $pdf,
                'carte-de-membre.pdf',
                'application/pdf'
            )
            ->context([
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }
}