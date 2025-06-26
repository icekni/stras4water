<?php

namespace App\Form;
use App\Enum\MoyenPaiement;
use App\Enum\TypeDon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class DonManuelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('montant', MoneyType::class, [
                'label' => 'Montant (€)',
                'currency' => 'EUR',
            ])
            ->add('wantsRecuFiscal', CheckboxType::class, [
                'label' => 'Le donateur souhaite un reçu fiscal',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'required' => false,
                'label' => 'Email du donateur (pour le reçu fiscal)',
            ])
            ->add('createdAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date du don',
                'data' => new \DateTimeImmutable(),
            ])
            ->add('moyenPaiement', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices' => array_combine(
                    array_map(fn($c) => ucfirst(strtolower($c->name)), MoyenPaiement::cases()),
                    MoyenPaiement::cases()
                ),
                'choice_value' => fn ($choice) => $choice?->value,
                'data' => MoyenPaiement::CASH,
            ])
            ->add('typeDon', ChoiceType::class, [
                'label' => 'Type de don',
                'choices' => array_combine(
                    array_map(fn($c) => ucfirst(strtolower(str_replace('_', ' ', $c->name))), TypeDon::cases()),
                    TypeDon::cases()
                ),
                'choice_value' => fn ($choice) => $choice?->value,
                'data' => TypeDon::NUMERAIRE,
            ])
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer le don']);
    }
}