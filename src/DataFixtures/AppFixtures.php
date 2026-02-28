<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();

        $user
            ->setFirstname('enzo')
            ->setLastname('palermo')
            ->setEmail('gmao@gmail.com')
            ->setPassword('$2y$13$GKJo1Sdw4/FhIL821Nz3bujHBv3mz/VsiRRLPU.H0B6PCxonDR9w2')
            ->setRoles(["ROLE_USER","ROLE_ADMIN"])
        ;

        $manager->persist($user);

        $manager->flush();
    }
}
