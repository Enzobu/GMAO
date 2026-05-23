<?php

namespace App\Form;

use App\Entity\Part;
use App\Entity\PartType;
use App\Entity\Vehicle;
use App\Repository\PartTypeRepository;
use App\Repository\VehicleRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PartFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('partType', EntityType::class, [
                'class' => PartType::class,
                'choice_label' => 'name',
                'label' => 'Type de pièce',
                'placeholder' => 'Sélectionner un type de pièce',
                'query_builder' => static fn (PartTypeRepository $repository) => $repository
                    ->createQueryBuilder('pt')
                    ->orderBy('pt.name', 'ASC'),
                'attr' => [
                    'class' => 'form-select',
                ],
                'row_attr' => [
                    'class' => 'col-12 col-lg-6',
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'placeholder' => 'Ex. : 2',
                ],
                'row_attr' => [
                    'class' => 'col-12 col-lg-6',
                ],
            ])
            ->add('vehicles', EntityType::class, [
                'class' => Vehicle::class,
                'choice_label' => 'displayName',
                'label' => 'Véhicules compatibles',
                'multiple' => true,
                'expanded' => false,
                'query_builder' => static fn (VehicleRepository $repository) => $repository
                    ->createQueryBuilder('v')
                    ->orderBy('v.name', 'ASC'),
                'attr' => [
                    'class' => 'form-select',
                    'size' => 6,
                ],
                'row_attr' => [
                    'class' => 'col-12',
                ],
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Note',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Informations complémentaires, remarque de stockage, compatibilité particulière, etc.',
                ],
                'row_attr' => [
                    'class' => 'col-12',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Part::class,
        ]);
    }
}