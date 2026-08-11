<?php

namespace App\Form;

use App\Entity\Carte;
use App\Entity\Discipline;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CarteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la carte',
            ])
            ->add('nombreSeances', IntegerType::class, [
                'label' => 'Nombre de séances',
            ])
            ->add('tarif', MoneyType::class, [
                'label' => 'Tarif normal',
                'currency' => 'EUR',
            ])
            ->add('hasTarifReduit', CheckboxType::class, [
                'label' => 'Tarif réduit disponible',
                'required' => false,
            ])
            ->add('tarifReduit', MoneyType::class, [
                'label' => 'Tarif réduit',
                'currency' => 'EUR',
                'required' => false,
            ])
            ->add('disciplines', EntityType::class, [
                'class' => Discipline::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Disciplines concernées',
            ])
            ->add('whatsappUrl', TextType::class, [
                'label' => 'URL du groupe Whatsapp',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Carte::class,
        ]);
    }
}
