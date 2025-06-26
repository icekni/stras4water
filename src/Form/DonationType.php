<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, DateType, IntegerType, TextType, EmailType, TextareaType, HiddenType, MoneyType};
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Donation;

class DonationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('montant', MoneyType::class, [
                'label' => 'Montant du don',
                'currency' => 'EUR',
            ])
            ->add('wantsRecuFiscal', CheckboxType::class, [
                'label'    => 'Je souhaite un reçu fiscal',
                'required' => false,
                'mapped' => true,
            ]);

        // Ajout dynamique de l'email si wantsReceipt est coché
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (!empty($data['wantsRecuFiscal'])) {
                $form->add('email', EmailType::class, ['required' => true]);
            }
        });

        // Ajouter l'email (non requis par défaut) pour affichage conditionnel
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $form->add('email', EmailType::class, ['required' => false]);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Donation::class,
        ]);
    }
}
