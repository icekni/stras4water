<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, IntegerType, TextType, EmailType, TextareaType, HiddenType};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Don;

class DonationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant', IntegerType::class, [
                'label' => 'Montant du don (en €)',
                'required' => true,
            ])
            ->add('recuFiscal', CheckboxType::class, [
                'label' => 'Je souhaite un reçu fiscal pour pouvoir déduire ce don de mes impots',
                'required' => false,
            ])
            ->add('nom', TextType::class, ['required' => false])
            ->add('prenom', TextType::class, ['required' => false])
            ->add('email', EmailType::class, [
                'required' => false,
                'help' => 'Le recu fiscal vous sera envoyé par mail à cette adresse.',
            ])
            ->add('adresse', TextareaType::class, [
                'required' => false,
                'label' => 'Recherche de l\'adresse',
                ])
            ->add('numero', TextType::class, ['required' => false])
            ->add('rue', TextType::class, ['required' => false])
            ->add('code_postal', TextType::class, ['required' => false])
            ->add('ville', TextType::class, ['required' => false])
            ->add('pays', TextType::class, ['required' => false]);

        // ✅ Ajout de l'EventListener ici
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (!empty($data['recuFiscal'])) {
                $form->add('nom', TextType::class, [
                    'constraints' => [new Assert\NotBlank()],
                ]);
                $form->add('prenom', TextType::class, [
                    'constraints' => [new Assert\NotBlank()],
                ]);
                $form->add('email', EmailType::class, [
                    'constraints' => [new Assert\NotBlank(), new Assert\Email()],
                ]);
                $form->add('adresse', TextareaType::class, [
                    'constraints' => [new Assert\NotBlank()],
                ]);
                $form->add('numero', HiddenType::class, [
                    'constraints' => [new Assert\NotBlank()],
                ]);
                $form->add('rue', HiddenType::class, [
                    'constraints' => [new Assert\NotBlank()],
                ]);
                $form->add('code_postal', HiddenType::class, [
                    'constraints' => [new Assert\NotBlank()],
                ]);
                $form->add('ville', HiddenType::class, [
                    'constraints' => [new Assert\NotBlank()],
                ]);
                $form->add('pays', HiddenType::class, [
                    'constraints' => [new Assert\NotBlank()],
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Don::class,
        ]);
    }
}
