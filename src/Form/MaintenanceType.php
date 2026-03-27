<?php

namespace App\Form;

use App\Entity\Maintenance;
use App\Entity\Vehicle;
use App\Enum\MaintenanceStatusEnum;
use App\Enum\MaintenanceTypeEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MaintenanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('vehicle', EntityType::class, [
                'class' => Vehicle::class,
                'choice_label' => function (Vehicle $vehicle): string {
                    return sprintf(
                        '%s',
                        ucfirst($vehicle->getName()),
                    );
                },
                'label' => 'Véhicule',
                'placeholder' => 'Sélectionner un véhicule',
                'row_attr' => ['class' => 'mb-3'],
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('maintenanceType', EnumType::class, [
                'class' => MaintenanceTypeEnum::class,
                'choice_label' => fn (MaintenanceTypeEnum $choice) => $choice->label(),
                'label' => 'Type d’entretien',
                'placeholder' => 'Sélectionner un type',
                'row_attr' => ['class' => 'mb-3'],
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('status', EnumType::class, [
                'class' => MaintenanceStatusEnum::class,
                'choice_label' => fn (MaintenanceStatusEnum $choice) => $choice->label(),
                'label' => 'Statut',
                'placeholder' => 'Sélectionner un statut',
                'row_attr' => ['class' => 'mb-3'],
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('mileage', IntegerType::class, [
                'label' => 'Kilométrage',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'placeholder' => 'Ex : 125000',
                ],
                'row_attr' => ['class' => 'mb-3'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('plannedAt', DateTimeType::class, [
                'label' => 'Date prévue',
                'widget' => 'single_text',
                'required' => false,
                'row_attr' => ['class' => 'mb-3'],
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('performedAt', DateTimeType::class, [
                'label' => 'Date réalisée',
                'widget' => 'single_text',
                'required' => false,
                'row_attr' => ['class' => 'mb-3'],
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('nextDueMileage', IntegerType::class, [
                'label' => 'Prochain kilométrage',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                ],
                'row_attr' => ['class' => 'mb-3'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('nextDueAt', DateTimeType::class, [
                'label' => 'Prochaine date',
                'widget' => 'single_text',
                'required' => false,
                'row_attr' => ['class' => 'mb-3'],
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('isExternal', CheckboxType::class, [
                'label' => 'Entretien réalisé en externe',
                'required' => false,
                'label_attr' => [
                    'class' => 'form-check-label',
                ],
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'row_attr' => ['class' => 'form-check form-switch mb-3'],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Informations complémentaires',
                ],
                'row_attr' => ['class' => 'mb-3'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('maintenanceParts', CollectionType::class, [
                'entry_type' => MaintenancePartType::class,
                'entry_options' => ['label' => false],
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Maintenance::class,
        ]);
    }
}