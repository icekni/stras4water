<?php

namespace App\Form;

use App\Entity\Abonnement;
use App\Entity\Carte;
use App\Entity\GroupeControle;
use App\Entity\Saison;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupeControleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $groupe = $options['data'];

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('isActif', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
            ])
        ;

        $abonnementsActifs = [];
        $abonnementsPrecedents = [];

        foreach ($options['saisons'] as $saison) {
            foreach ($saison->getAbonnements() as $abonnement) {
                if (
                    $abonnement->isActif()
                    || $groupe->getAbonnements()->contains($abonnement)
                ) {
                    if ($saison->isActif()) {
                        $abonnementsActifs[] = $abonnement;
                    } else {
                        $abonnementsPrecedents[] = $abonnement;
                    }
                }
            }
        }

        $selectionnesActifs = array_filter(
            $abonnementsActifs,
            fn (Abonnement $abonnement) =>
                $groupe->getAbonnements()->contains($abonnement)
        );

        $selectionnesPrecedents = array_filter(
            $abonnementsPrecedents,
            fn (Abonnement $abonnement) =>
                $groupe->getAbonnements()->contains($abonnement)
        );

        $builder
            ->add('abonnementsActifs', EntityType::class, [
                'class' => Abonnement::class,
                'choices' => $abonnementsActifs,
                'choice_label' => function (Abonnement $abonnement): string {
                    return $abonnement->getNom()
                        .' - '.$abonnement->getSaison()?->getNom();
                },
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'required' => false,
                'data' => $selectionnesActifs,
                'label' => false,
            ])
            ->add('abonnementsPrecedents', EntityType::class, [
                'class' => Abonnement::class,
                'choices' => $abonnementsPrecedents,
                'choice_label' => function (Abonnement $abonnement): string {
                    return $abonnement->getNom()
                        .' - '.$abonnement->getSaison()?->getNom();
                },
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'required' => false,
                'data' => $selectionnesPrecedents,
                'label' => false,
            ]);

        $builder->add('cartes', EntityType::class, [
            'class' => Carte::class,
            'choice_label' => 'nom',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GroupeControle::class,
            'saisons' => [],
        ]);

        $resolver->setAllowedTypes('saisons', 'array');
    }
}