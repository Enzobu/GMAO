<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Vehicle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('registration')
            ->add('brand')
            ->add('model')
            ->add('type')
            ->add('year')
            ->add('vin')
            ->add('engine')
            ->add('fuelType')
            ->add('transmission')
            ->add('lastMileage')
            ->add('color')
            ->add('purchaseDate', null, [
                'widget' => 'single_text',
            ])
            ->add('purchasePrice')
            ->add('status')
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
        ]);
    }
}
