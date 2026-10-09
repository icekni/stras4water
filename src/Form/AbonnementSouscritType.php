<?php

namespace App\Form;

use App\Entity\Abonnement;
use App\Enum\MoyenPaiement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class AbonnementSouscritType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('abonnement', EntityType::class, [
                'class' => Abonnement::class,
                'choice_label' => 'nom',
                'label' => 'Abonnement',
            ])
            ->add('isTarifReduit', CheckboxType::class, [
                'label' => 'Tarif réduit',
                'required' => false,
            ])
            ->add('tarifReduitVerifie', CheckboxType::class, [
                'label' => 'Justificatif fourni',
                'required' => false,
            ])
            ->add('moyenPaiement', EnumType::class, [
                'class' => MoyenPaiement::class,
                'label' => 'Moyen de paiement',
                'choice_label' => fn (MoyenPaiement $m) => match ($m) {
                    MoyenPaiement::CASH => 'Espèces',
                    MoyenPaiement::STRIPE => 'Paiement en ligne',
                    MoyenPaiement::SUMUP => 'Terminal SumUp',
                    MoyenPaiement::VIREMENT => 'Virement',
                    MoyenPaiement::CHEQUE => 'Chèque',
                    MoyenPaiement::BENEVOLE => 'Bénévole',
                },
            ]);
    }
}