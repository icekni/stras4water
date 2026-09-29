<?php

namespace App\Form;

use App\Dto\InscriptionData;
use App\Entity\Abonnement;
use App\Entity\Carte;
use App\Entity\User;
use App\Enum\MoyenPaiement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InscriptionType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        /*
         * Informations personnelles
         *
         * Uniquement pour une nouvelle personne.
         */
        if ($options['existing_user'] === null) {
            $builder
                ->add('nom', TextType::class, [
                    'label' => 'Nom',
                    'required' => true,
                ])
                ->add('prenom', TextType::class, [
                    'label' => 'Prénom',
                    'required' => true,
                ])
                ->add('email', EmailType::class, [
                    'label' => 'Email',
                    'required' => true,
                ]);
        }

        /*
         * Adhésion
         */
        if (
            $options['existing_user'] === null
            || !$options['existing_user']->getAdhesion()
        ) {
            $builder->add('adhesion', CheckboxType::class, [
                'label' => 'Ajouter l’adhésion annuelle',
                'required' => false,
            ]);
        }

        /*
         * Abonnements
         */
        $builder->add('abonnements', EntityType::class, [
            'class' => Abonnement::class,
            'choices' => $options['abonnements'],

            'choice_label' => function (Abonnement $abonnement): string {
                return $abonnement->getNom();
            },
            'choice_translation_domain' => false,

            'group_by' => function (Abonnement $abonnement): string {
                return $abonnement->getSaison()->getNom();
            },

            'choice_attr' => function (Abonnement $abonnement): array {
                return [
                    'data-prix' => $abonnement->getTarif(),
                    'data-prix-reduit' => $abonnement->getTarifReduit(),
                    'data-tarif-reduit' => $abonnement->HasTarifReduit()
                        ? '1'
                        : '0',
                ];
            },

            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'label' => 'Abonnements',
        ]);

        /*
         * Cartes
         */
        $builder->add('cartes', EntityType::class, [
            'class' => Carte::class,
            'choices' => $options['cartes'],

            'choice_label' => function (Carte $carte): string {
                return $carte->getNom();
            },
            'choice_translation_domain' => false,

            'choice_attr' => function (Carte $carte): array {
                return [
                    'data-prix' => $carte->getTarif(),
                    'data-prix-reduit' => $carte->getTarifReduit(),
                    'data-tarif-reduit' => $carte->HasTarifReduit()
                        ? '1'
                        : '0',
                ];
            },

            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'label' => 'Cartes',
        ]);

        /*
         * Tarif réduit
         *
         * Le champ sera masqué côté interface s'il n'y a
         * aucune activité sélectionnée.
         */
        $builder->add('tarifReduit', ChoiceType::class, [
            'label' => 'Tarif',
            'choices' => [
                'Plein tarif' => false,
                'Tarif réduit' => true,
            ],
            'expanded' => true,
            'multiple' => false,
            'required' => true,
        ]);

        /*
         * Justificatif
         *
         * Ce champ sert uniquement au bénévole.
         * Il n'est pas enregistré en base.
         */
        $builder->add('justificatif', ChoiceType::class, [
            'label' => 'Justificatif',
            'choices' => [
                'Fourni maintenant' => 'fourni',
                'À fournir plus tard' => 'plus_tard',
            ],
            'expanded' => true,
            'multiple' => false,
            'required' => false,
            'placeholder' => false,
        ]);

        /*
         * Moyen de paiement
         */
        $builder->add('moyenPaiement', EnumType::class, [
            'class' => MoyenPaiement::class,
            'label' => 'Moyen de paiement',
            'choices' => [
                MoyenPaiement::CASH,
                MoyenPaiement::SUMUP,
                MoyenPaiement::VIREMENT,
                MoyenPaiement::CHEQUE,
                MoyenPaiement::BENEVOLE,
            ],
            'choice_label' => fn (MoyenPaiement $m) => match ($m) {
                MoyenPaiement::CASH => 'Espèces',
                MoyenPaiement::SUMUP => 'Terminal SumUp',
                MoyenPaiement::VIREMENT => 'Virement',
                MoyenPaiement::CHEQUE => 'Chèque',
                MoyenPaiement::BENEVOLE => 'Bénévole',
                MoyenPaiement::STRIPE => 'Paiement en ligne',
            },
            'expanded' => true,
            'multiple' => false,
            'required' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InscriptionData::class,

            'abonnements' => [],
            'cartes' => [],

            /*
             * null = nouvelle personne
             * User = ajout d'activités à un utilisateur existant
             */
            'existing_user' => null,
        ]);

        $resolver->setAllowedTypes('abonnements', 'array');
        $resolver->setAllowedTypes('cartes', 'array');
        $resolver->setAllowedTypes(
            'existing_user',
            ['null', User::class]
        );
    }
}