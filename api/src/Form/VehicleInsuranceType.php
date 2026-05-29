<?php

namespace App\Form;

use App\Entity\VehicleInsurance;
use App\Enum\InsurancePaymentFrequencyEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleInsuranceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('providerName', TextType::class, [
                'label' => 'Assureur',
                'attr' => [
                    'placeholder' => 'Ex: MAIF, AXA, MACIF…',
                    'class' => 'form-control',
                ],
            ])

            ->add('policyNumber', TextType::class, [
                'label' => 'Numéro de police',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: POL-2026-0001',
                    'autocomplete' => 'off',
                    'class' => 'form-control',
                ],
            ])

            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])

            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])

            ->add('paymentFrequency', ChoiceType::class, [
                'label' => 'Fréquence de paiement',
                'choices' => InsurancePaymentFrequencyEnum::cases(),
                'choice_label' => fn (InsurancePaymentFrequencyEnum $frequency) => $frequency->label(),
                'choice_value' => fn (?InsurancePaymentFrequencyEnum $frequency) => $frequency?->value ?? '',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])

            ->add('isActive', CheckboxType::class, [
                'label' => 'Assurance active',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VehicleInsurance::class,
        ]);
    }
}
