<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class FiscalDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, ['mapped' => false])
            ->add('nom', TextType::class, ['mapped' => false])
            ->add('adresse_search', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Adresse (avec suggestions)',
            ])
            ->add('numero_rue', TextType::class, ['mapped' => false, 'required' => false])
            ->add('rue', TextType::class, ['mapped' => false, 'required' => false])
            ->add('code_postal', TextType::class, ['mapped' => false, 'required' => false])
            ->add('ville', TextType::class, ['mapped' => false, 'required' => false])
            ->add('pays', TextType::class, ['mapped' => false, 'required' => false, 'data' => 'France']);
    }
}