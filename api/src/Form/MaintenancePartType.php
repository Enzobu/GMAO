<?php

namespace App\Form;

use App\Entity\MaintenancePart;
use App\Entity\Part;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MaintenancePartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('part', EntityType::class, [
                'class' => Part::class,
                'choice_label' => function (Part $part): string {
                    $vehicleName = [];
                    foreach ($part->getVehicles() as $vehicle) {
                        $vehicleName[] = ucfirst($vehicle->getName());
                    }
                    return sprintf(
                        '%s (%s)',
                        $part->getPartType()->getName(),
                        implode(', ', $vehicleName)
                    );
                },
                'label' => 'Pièce',
                'placeholder' => 'Sélectionner une pièce',
                'row_attr' => [
                    'class' => 'mb-0',
                ],
                'label_attr' => [
                    'class' => 'form-label',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => 'Ex : 1',
                ],
                'row_attr' => [
                    'class' => 'mb-0',
                ],
                'label_attr' => [
                    'class' => 'form-label',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MaintenancePart::class,
        ]);
    }
}
