<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Vehicle;
use App\Enum\VehicleFuelTypeEnum;
use App\Enum\VehicleStatusEnum;
use App\Enum\VehicleTransmissionTypeEnum;
use App\Enum\VehicleTypeEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class VehicleType extends AbstractType
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $auth,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Ex: Focus, Ninja 400, Clio…',
                    'class' => 'form-control',
                ],
            ])
            ->add('registration', TextType::class, [
                'label' => 'Immatriculation',
                'attr' => [
                    'placeholder' => 'AB-123-CD',
                    'style' => 'text-transform: uppercase;',
                    'autocomplete' => 'off',
                    'class' => 'form-control',
                ],
            ])
            ->add('brand', TextType::class, [
                'label' => 'Marque',
                'attr' => [
                    'placeholder' => 'Ex: Ford, Kawasaki…',
                    'class' => 'form-control',
                ],
            ])
            ->add('model', TextType::class, [
                'label' => 'Modèle',
                'attr' => [
                    'placeholder' => 'Ex: Focus 2, Ninja 400…',
                    'class' => 'form-control',
                ],
            ])

            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => VehicleTypeEnum::cases(),
                'choice_label' => fn (?VehicleTypeEnum $e) => $e?->label() ?? '',
                'choice_value' => fn (?VehicleTypeEnum $e) => $e?->value ?? '',
                'placeholder' => '—',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ]
            ])

            ->add('year', IntegerType::class, [
                'label' => 'Année',
                'required' => false,
                'attr' => [
                    'min' => 1900,
                    'max' => (int) (new \DateTimeImmutable())->format('Y') + 1,
                    'placeholder' => 'Ex: 2006',
                    'class' => 'form-control',
                ],
            ])

            ->add('vin', TextType::class, [
                'label' => 'VIN',
                'required' => false,
                'attr' => [
                    'placeholder' => '17 caractères',
                    'autocomplete' => 'off',
                    'class' => 'form-control',
                ],
            ])

            ->add('engine', TextType::class, [
                'label' => 'Moteur',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: 1.6 TDCi 90, 400cc…',
                    'class' => 'form-control',
                ],
            ])

            ->add('fuelType', ChoiceType::class, [
                'label' => 'Carburant',
                'choices' => VehicleFuelTypeEnum::cases(),
                'choice_label' => fn (?VehicleFuelTypeEnum $e) => $e?->label() ?? '',
                'choice_value' => fn (?VehicleFuelTypeEnum $e) => $e?->value ?? '',
                'placeholder' => '—',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ]
            ])

            ->add('transmission', ChoiceType::class, [
                'label' => 'Transmission',
                'choices' => VehicleTransmissionTypeEnum::cases(),
                'choice_label' => fn (?VehicleTransmissionTypeEnum $e) => $e?->label() ?? '',
                'choice_value' => fn (?VehicleTransmissionTypeEnum $e) => $e?->value ?? '',
                'placeholder' => '—',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ]
            ])

            ->add('lastMileage', IntegerType::class, [
                'label' => 'Kilométrage',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'step' => 1,
                    'placeholder' => 'Ex: 187500',
                    'class' => 'form-control',
                    'disabled' => true,
                ],
            ])

            ->add('color', TextType::class, [
                'label' => 'Couleur',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: Noir',
                    'class' => 'form-control',
                ],
            ])

            ->add('purchaseDate', DateType::class, [
                'label' => 'Date d’achat',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ]
            ])

            ->add('purchasePrice', MoneyType::class, [
                'label' => 'Prix d’achat',
                'required' => false,
                'currency' => 'EUR',
                'html5' => true,
                'attr' => [
                    'min' => 0,
                    'step' => '0.01',
                    'placeholder' => 'Ex: 2499',
                    'class' => 'form-control',
                ],
            ])

            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => VehicleStatusEnum::cases(),
                'choice_label' => fn (VehicleStatusEnum $e) => $e->label(),
                'choice_value' => fn (?VehicleStatusEnum $e) => $e?->value ?? '',
                'attr' => [
                    'class' => 'form-control',
                ]
            ])
        ;

        if ($this->auth->isGranted('ROLE_ADMIN')) {
            $builder->add('user', EntityType::class, [
                'label' => 'Propriétaire',
                'class' => User::class,
                'choice_label' => fn (User $u) => trim(($u->getFirstname() ?? '') . ' ' . ($u->getLastname() ?? '')) . ' - ' . $u->getEmail(),
                'placeholder' => '—',
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
        ]);
    }
}