<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Address;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class AddressTest extends TestCase
{
    public function testAccessors(): void
    {
        $emptyAddress = new Address();
        self::assertNull($emptyAddress->getId());

        $address = (new Address())
            ->setLine1('1 rue de Paris')
            ->setLine2('Batiment A')
            ->setPostalCode('75000')
            ->setCity('Paris')
            ->setCountry('France');

        self::assertSame('1 rue de Paris', $address->getLine1());
        self::assertSame('Batiment A', $address->getLine2());
        self::assertSame('75000', $address->getPostalCode());
        self::assertSame('Paris', $address->getCity());
        self::assertSame('France', $address->getCountry());
    }

    public function testPostalCodeMustContainFiveDigits(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        self::assertCount(0, $validator->validate((new Address())->setPostalCode('34890')));

        $violations = $validator->validate((new Address())->setPostalCode('3489A'));

        self::assertCount(1, $violations);
        self::assertSame('postalCode', $violations[0]->getPropertyPath());
        self::assertSame('Le code postal doit contenir 5 chiffres.', $violations[0]->getMessage());
    }
}
