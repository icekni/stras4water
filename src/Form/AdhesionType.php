<?php

namespace App\Form;

use App\Enum\MoyenPaiement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;

class AdhesionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('moyenPaiement', EnumType::class, [
                'label' => 'Moyen de paiement',
                'class' => MoyenPaiement::class,
                'choice_label' => fn (MoyenPaiement $m) => match ($m) {
                    MoyenPaiement::CASH => 'Espèces',
                    MoyenPaiement::STRIPE => 'Paiement en ligne',
                    MoyenPaiement::SUMUP => 'Terminal SumUp',
                    MoyenPaiement::VIREMENT => 'Virement',
                    MoyenPaiement::CHEQUE => 'Chèque',
                    MoyenPaiement::BENEVOLE => 'Bénévole',
                },
            ])
        ;
    }
}