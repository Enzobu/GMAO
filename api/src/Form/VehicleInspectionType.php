<?php

namespace App\Form;

use App\Entity\InspectionCenter;
use App\Entity\VehicleInspection;
use App\Enum\InspectionResultEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class VehicleInspectionType extends AbstractType
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $auth,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('inspectionDate', DateType::class, [
                'label' => 'Date du contrôle',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])

            ->add('validUntil', DateType::class, [
                'label' => 'Valide jusqu’au',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])

            ->add('mileage', IntegerType::class, [
                'label' => 'Kilométrage',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: 154230',
                    'class' => 'form-control',
                    'min' => 0,
                    'disabled' => $this->auth->isGranted('ROLE_ADMIN') ? false : $options['edit'],
                ],
            ])

            ->add('result', ChoiceType::class, [
                'label' => 'Résultat',
                'required' => false,
                'choices' => InspectionResultEnum::cases(),
                'choice_label' => fn (InspectionResultEnum $result) => $result->label(),
                'choice_value' => fn (?InspectionResultEnum $result) => $result?->value ?? '',
                'placeholder' => 'Sélectionner un résultat',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])

            ->add('counterVisitRequired', CheckboxType::class, [
                'label' => 'Contre-visite requise',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])

            ->add('counterVisitDueAt', DateType::class, [
                'label' => 'Date limite contre-visite',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])

            ->add('center', EntityType::class, [
                'class' => InspectionCenter::class,
                'label' => 'Centre de contrôle',
                'required' => false,
                'placeholder' => 'Sélectionner un centre',
                'choice_label' => static function (InspectionCenter $center): string {
                    if (method_exists($center, 'getName') && $center->getName()) {
                        return $center->getName();
                    }

                    return 'Centre #' . $center->getId();
                },
                'attr' => [
                    'class' => 'form-select',
                ],
            ])

            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Observations, défauts relevés, remarques du centre…',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VehicleInspection::class,
            'edit' => false,
        ]);
    }
}