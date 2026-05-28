<?php

namespace App\Tests\Unit\Form;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class FormTypeTestCase extends TestCase
{
    protected function assertFormTypeBuildsAndConfigures(AbstractType $type, array $buildOptions = []): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')->willReturnSelf();

        $type->buildForm($builder, $buildOptions);

        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);
        try {
            $resolvedOptions = $resolver->resolve($buildOptions);
        } catch (UndefinedOptionsException) {
            $resolvedOptions = $resolver->resolve();
        }

        self::assertIsArray($resolvedOptions);
    }
}
