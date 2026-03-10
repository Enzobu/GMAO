<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdateProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', null, [
                'attr' => [
                    'class' => 'form-control w-100 border-0',
                    'style' => 'outline: 0; box-shadow: none;',
                ],
                'row_attr' => [
                    'class' => 'w-100',
                ],
                'label' => false,
                'mapped' => false,
                'disabled' => true,
            ])
            ->add('old_password', PasswordType::class, [
                'attr' => [
                    'class' => 'form-control w-100 border-0',
                    'style' => 'outline: 0; box-shadow: none;',
                ],
                'row_attr' => [
                    'class' => 'w-100',
                ],
                'label' => false,
                'mapped' => false,
                'required' => false,
            ])
            ->add('new_password', PasswordType::class, [
                'attr' => [
                    'class' => 'form-control w-100 border-0',
                    'style' => 'outline: 0; box-shadow: none;',
                ],
                'row_attr' => [
                    'class' => 'w-100',
                ],
                'label' => false,
                'mapped' => false,
                'required' => false,
            ])
            ->add('new_password_retry', PasswordType::class, [
                'attr' => [
                    'class' => 'form-control w-100 border-0',
                    'style' => 'outline: 0; box-shadow: none;',
                ],
                'row_attr' => [
                    'class' => 'w-100',
                ],
                'label' => false,
                'mapped' => false,
                'required' => false,
            ])
            ->add('firstname', null, [
                'attr' => [
                    'class' => 'form-control w-100 border-0',
                    'style' => 'outline: 0; box-shadow: none;',
                ],
                'row_attr' => [
                    'class' => 'w-100',
                ],
                'mapped' => false,
                'label' => false,
            ])
            ->add('lastname', null, [
                'attr' => [
                    'class' => 'form-control w-100 border-0',
                    'style' => 'outline: 0; box-shadow: none;',
                ],
                'row_attr' => [
                    'class' => 'w-100',
                ],
                'mapped' => false,
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
