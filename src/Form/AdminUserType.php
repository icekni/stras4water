<?php

namespace App\Form;

use App\Entity\Abonnement;
use App\Entity\Carte;
use App\Entity\User;
use App\Repository\AbonnementRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class)
            ->add('nom', TextType::class)
            ->add('email', EmailType::class)
            ->add('roles', ChoiceType::class, [
                'choices'  => [
                    'Utilisateur' => 'ROLE_USER',
                    'Accueil' => 'ROLE_ACCUEIL',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'expanded' => true,
                'multiple' => true,
                'label' => 'Rôles',
            ])
            ->add('abonnementsDisponibles', EntityType::class, [
                'class' => Abonnement::class,
                'choice_label' => 'nom', 
                'multiple' => true,
                'expanded' => true, 
                'mapped' => false,  
                'data' => $options['abonnements_souscrits'],
                'query_builder' => function (AbonnementRepository $ar) {
                    return $ar->createQueryBuilder('a')
                        ->andWhere('a.isActif = :actif')
                        ->setParameter('actif', true)
                        ->orderBy('a.nom', 'ASC');
                },
            ])
            ->add('cartesDisponibles', EntityType::class, [
                'class' => Carte::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'data' => $options['cartes_souscrites'],
            ])
            ->add('isAdherent', CheckboxType::class, [
                'required' => false,
                'label' => 'Adhésion',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'abonnements_souscrits' => [],
            'cartes_souscrites' => [],
        ]);
    }
}
