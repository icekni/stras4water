<?php

namespace App\Form;

use App\Entity\Carte;
use App\Enum\MoyenPaiement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

class CarteSouscriteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('carte', EntityType::class, [
                'class' => Carte::class,
                'choice_label' => 'nom',
                'label' => 'Carte',
            ])
            ->add('seancesRestantes', IntegerType::class, [
                'label' => 'Nombre de séances',
                'required' => true,
            ])
            ->add('moyenPaiement', EnumType::class, [
                'class' => MoyenPaiement::class,
                'label' => 'Moyen de paiement',
                'choices' => [
                    MoyenPaiement::CASH,
                    MoyenPaiement::SUMUP,
                    MoyenPaiement::VIREMENT,
                    MoyenPaiement::CHEQUE,
                ],
                'choice_label' => fn (MoyenPaiement $m) => match ($m) {
                    MoyenPaiement::CASH => 'Espèces',
                    MoyenPaiement::SUMUP => 'Carte bancaire',
                    MoyenPaiement::VIREMENT => 'Virement',
                    MoyenPaiement::CHEQUE => 'Chèque',
                },
            ]);
    }
}