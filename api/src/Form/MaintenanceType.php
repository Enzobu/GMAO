<?php

namespace App\Form;

use App\Entity\Maintenance;
use App\Entity\MaintenanceType as MaintenanceTypeEntity;
use App\Entity\Vehicle;
use App\Enum\MaintenanceStatusEnum;
use App\Repository\MaintenanceTypeRepository;
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
        $maintenance = $options['data'];
        $currentMaintenanceType = $maintenance instanceof Maintenance ? $maintenance->getMaintenanceType() : null;

        if (!$options['vehicle_locked']) {
            $builder->add('vehicle', EntityType::class, [
                'class' => Vehicle::class,
                'choice_label' => function (Vehicle $vehicle): string {
                    return sprintf(
                        '%s',
                        ucfirst($vehicle->getName()),
                    );
                },
                'label' => 'Véhicule',
                'placeholder' => 'Sélectionner un véhicule',
            ] + $this->selectOptions());
        }

        $builder
            ->add('maintenanceType', EntityType::class, [
                'class' => MaintenanceTypeEntity::class,
                'choice_label' => 'name',
                'query_builder' => static function (MaintenanceTypeRepository $repository) use ($currentMaintenanceType) {
                    $queryBuilder = $repository
                        ->createQueryBuilder('mt')
                        ->andWhere('mt.isDeleted = :isDeleted')
                        ->setParameter('isDeleted', false)
                        ->orderBy('mt.name', 'ASC');

                    if ($currentMaintenanceType?->getId() !== null) {
                        $queryBuilder
                            ->orWhere('mt.id = :currentMaintenanceTypeId')
                            ->setParameter('currentMaintenanceTypeId', $currentMaintenanceType->getId());
                    }

                    return $queryBuilder;
                },
                'label' => 'Type d’entretien',
                'placeholder' => 'Sélectionner un type',
            ] + $this->selectOptions())
            ->add('status', EnumType::class, [
                'class' => MaintenanceStatusEnum::class,
                'choice_label' => fn (MaintenanceStatusEnum $choice) => $choice->label(),
                'label' => 'Statut',
                'placeholder' => 'Sélectionner un statut',
            ] + $this->selectOptions())
            ->add('mileage', IntegerType::class, [
                'label' => 'Kilométrage',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'placeholder' => 'Ex : 125000',
                ],
            ] + $this->integerOptions())
            ->add('plannedAt', DateTimeType::class, $this->dateOptions('Date prévue'))
            ->add('startedAt', DateTimeType::class, $this->dateOptions('Date de début'))
            ->add('finishedAt', DateTimeType::class, $this->dateOptions('Date de fin'))
            ->add('nextDueMileage', IntegerType::class, [
                'label' => 'Prochain kilométrage',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                ],
            ] + $this->integerOptions())
            ->add('nextDueAt', DateTimeType::class, $this->dateOptions('Prochaine date'))
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
            'vehicle_locked' => false,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function selectOptions(): array
    {
        return [
            'row_attr' => ['class' => 'mb-3'],
            'label_attr' => ['class' => 'form-label'],
            'attr' => ['class' => 'form-select'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function integerOptions(): array
    {
        return [
            'required' => false,
            'row_attr' => ['class' => 'mb-3'],
            'label_attr' => ['class' => 'form-label'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dateOptions(string $label): array
    {
        return [
            'label' => $label,
            'widget' => 'single_text',
            'required' => false,
            'row_attr' => ['class' => 'mb-3'],
            'label_attr' => ['class' => 'form-label'],
            'attr' => ['class' => 'form-control'],
        ];
    }
}
