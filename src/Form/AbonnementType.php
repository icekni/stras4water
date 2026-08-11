<?php

namespace App\Form;

use App\Entity\Abonnement;
use App\Entity\Discipline;
use App\Entity\Saison;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbonnementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('tarif', MoneyType::class, [
                'label' => 'Tarif normal (€)',
                'currency' => 'EUR',
            ])
            ->add('tarifReduit', MoneyType::class, [
                'label' => 'Tarif réduit (€)',
                'currency' => 'EUR',
                'required' => false,
            ])
            ->add('discipline', EntityType::class, [
                'class' => Discipline::class,
                'choice_label' => 'nom',
                'label' => 'Discipline',
                'placeholder' => 'Sélectionner une discipline',
            ])
            ->add('saison', EntityType::class, [
                'class' => Saison::class,
                'choice_label' => 'nom',
                'label' => 'Saison',
                'placeholder' => 'Sélectionner une saison',
            ])
            ->add('whatsappUrl', TextType::class, [
                'label' => 'URL du groupe Whatsapp',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Abonnement::class,
        ]);
    }
}
