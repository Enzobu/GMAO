<?php

namespace App\Form;

use App\Entity\InspectionCenter;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleInspectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('inspectionDate', null, [
                'widget' => 'single_text',
            ])
            ->add('validUntil', null, [
                'widget' => 'single_text',
            ])
            ->add('mileage')
            ->add('result')
            ->add('counterVisitRequired')
            ->add('counterVisitDueAt', null, [
                'widget' => 'single_text',
            ])
            ->add('notes')
            ->add('createdAt', null, [
                'widget' => 'single_text',
            ])
            ->add('updatedAt', null, [
                'widget' => 'single_text',
            ])
            ->add('vehicle', EntityType::class, [
                'class' => Vehicle::class,
                'choice_label' => 'id',
            ])
            ->add('center', EntityType::class, [
                'class' => InspectionCenter::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VehicleInspection::class,
        ]);
    }
}
