<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, DateType, IntegerType, TextType, EmailType, TextareaType, HiddenType};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Donation;

class DonationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant', IntegerType::class, [
                'label' => 'Montant du don (en €)',
                'required' => true,
            ])
            ->add('nom', TextType::class, ['required' => true])
            ->add('prenom', TextType::class, ['required' => true])
            ->add('date_de_naissance', DateType::class, ['required' => true])
            ->add('email', EmailType::class, [
                'required' => true,
                'help' => 'Le recu fiscal vous sera envoyé par mail à cette adresse.',
            ])
            ->add('adresse', TextareaType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Recherche de l\'adresse',
                ])
            ->add('adresse_numero', TextType::class, ['required' => true])
            ->add('adresse_rue', TextType::class, ['required' => true])
            ->add('adresse_code_postal', TextType::class, ['required' => true])
            ->add('adresse_ville', TextType::class, ['required' => true])
            ->add('adresse_pays', TextType::class, ['required' => true]);

        // ✅ Ajout de l'EventListener ici
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            $form->add('nom', TextType::class, [
                'constraints' => [new Assert\NotBlank()],
            ]);
            $form->add('prenom', TextType::class, [
                'constraints' => [new Assert\NotBlank()],
            ]);
            $form->add('date_de_naissance', DateType::class, [
                'constraints' => [new Assert\NotBlank()],
            ]);
            $form->add('email', EmailType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Email()],
            ]);
            $form->add('adresse_numero', TextType::class, [
                'constraints' => [new Assert\NotBlank()],
            ]);
            $form->add('adresse_rue', TextType::class, [
                'constraints' => [new Assert\NotBlank()],
            ]);
            $form->add('adresse_code_postal', TextType::class, [
                'constraints' => [new Assert\NotBlank()],
            ]);
            $form->add('adresse_ville', TextType::class, [
                'constraints' => [new Assert\NotBlank()],
            ]);
            $form->add('adresse_pays', TextType::class, [
                'constraints' => [new Assert\NotBlank()],
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Donation::class,
        ]);
    }
}
