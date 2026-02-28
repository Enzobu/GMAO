<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInsurance;
use App\Enum\InsurancePaymentFrequencyEnum;
use App\Enum\VehicleFuelTypeEnum;
use App\Enum\VehicleStatusEnum;
use App\Enum\VehicleTransmissionTypeEnum;
use App\Enum\VehicleTypeEnum;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $userAdmin = new User();
        $user = new User();

        $userAdmin
            ->setFirstname('enzo')
            ->setLastname('palermo')
            ->setEmail('gmao@gmail.com')
            ->setPassword('$2y$13$GKJo1Sdw4/FhIL821Nz3bujHBv3mz/VsiRRLPU.H0B6PCxonDR9w2')
            ->setRoles(["ROLE_USER","ROLE_ADMIN"])
        ;

        $user
            ->setFirstname('alexandra')
            ->setLastname('palermo')
            ->setEmail('gio.alex.pa@gmail.com')
            ->setPassword('$2y$13$GKJo1Sdw4/FhIL821Nz3bujHBv3mz/VsiRRLPU.H0B6PCxonDR9w2')
            ->setRoles(["ROLE_USER"])
        ;

        $manager->persist($userAdmin);
        $manager->persist($user);

        $manager->flush();

        // VEHICLE

        $vehiclesData = [
            [
                'name' => 'Focus',
                'registration' => 'AB-123-CD',
                'brand' => 'Ford',
                'model' => 'Focus 2',
                'type' => VehicleTypeEnum::Car,
                'year' => 2006,
                'vin' => 'WF0WXXGCDW6A12345',
                'engine' => '1.6 TDCI 90',
                'fuel' => VehicleFuelTypeEnum::Diesel,
                'transmission' => VehicleTransmissionTypeEnum::Manual,
                'km' => 187500,
                'color' => 'Noir',
                'status' => VehicleStatusEnum::Active,
                'purchaseDate' => new DateTimeImmutable('12-06-2023'),
                'purchasePrice' => 2499,
                'user' => $userAdmin,
            ],
            [
                'name' => 'Almera',
                'registration' => 'EF-456-GH',
                'brand' => 'Nissan',
                'model' => 'Almera 1.5',
                'type' => VehicleTypeEnum::Car,
                'year' => 1998,
                'vin' => 'JN1BBAN16U0123456',
                'engine' => '1.5 Essence',
                'fuel' => VehicleFuelTypeEnum::Petrol,
                'transmission' => VehicleTransmissionTypeEnum::Automatic,
                'km' => 243000,
                'color' => 'Gris',
                'status' => VehicleStatusEnum::Active,
                'purchaseDate' => null,
                'purchasePrice' => null,
                'user' => $userAdmin,
            ],
            [
                'name' => 'Ninja',
                'registration' => 'IJ-789-KL',
                'brand' => 'Kawasaki',
                'model' => 'Ninja 400',
                'type' => VehicleTypeEnum::Motorcycle,
                'year' => 2023,
                'vin' => 'JKAEX400AAA123456',
                'engine' => '399cc',
                'fuel' => VehicleFuelTypeEnum::Petrol,
                'transmission' => VehicleTransmissionTypeEnum::Manual,
                'km' => 8200,
                'color' => 'Vert',
                'status' => VehicleStatusEnum::Active,
                'purchaseDate' => new DateTimeImmutable('10-12-2025'),
                'purchasePrice' => 5000,
                'user' => $userAdmin,
            ],
            [
                'name' => 'Partner',
                'registration' => 'MN-321-OP',
                'brand' => 'Peugeot',
                'model' => 'Partner',
                'type' => VehicleTypeEnum::Utility,
                'year' => 2015,
                'vin' => 'VF3GJKHZ6FJ123456',
                'engine' => '1.6 HDI',
                'fuel' => VehicleFuelTypeEnum::Diesel,
                'transmission' => VehicleTransmissionTypeEnum::Manual,
                'km' => 156000,
                'color' => 'Blanc',
                'status' => VehicleStatusEnum::Inactive,
                'purchaseDate' => null,
                'purchasePrice' => null,
                'user' => $user,
            ],
            [
                'name' => 'Clio',
                'registration' => 'QR-654-ST',
                'brand' => 'Renault',
                'model' => 'Clio 4',
                'type' => VehicleTypeEnum::Car,
                'year' => 2018,
                'vin' => 'VF1RBB00659012345',
                'engine' => '0.9 TCe',
                'fuel' => VehicleFuelTypeEnum::Petrol,
                'transmission' => VehicleTransmissionTypeEnum::Manual,
                'km' => 94500,
                'color' => 'Rouge',
                'status' => VehicleStatusEnum::Active,
                'purchaseDate' => null,
                'purchasePrice' => null,
                'user' => $user,
            ],
        ];

        $vehiclesByRegistration = [];

        foreach ($vehiclesData as $data) {
            $vehicle = new Vehicle();
            $vehicle->setName($data['name']);
            $vehicle->setRegistration($data['registration']);
            $vehicle->setBrand($data['brand']);
            $vehicle->setModel($data['model']);
            $vehicle->setType($data['type']);
            $vehicle->setYear($data['year']);
            $vehicle->setVin($data['vin']);
            $vehicle->setEngine($data['engine']);
            $vehicle->setFuelType($data['fuel']);
            $vehicle->setTransmission($data['transmission']);
            $vehicle->setLastMileage($data['km']);
            $vehicle->setColor($data['color']);
            $vehicle->setStatus($data['status']);
            $vehicle->setPurchaseDate($data['purchaseDate']);
            $vehicle->setPurchasePrice($data['purchasePrice']);
            $vehicle->setUser($data['user']);

            $manager->persist($vehicle);

            $vehiclesByRegistration[$data['registration']] = $vehicle;
        }

        $manager->flush();

        
        // INSURANCES
        $insurancesData = [
            [
                'vehicleRegistration' => 'AB-123-CD', // Focus
                'providerName' => 'MAIF',
                'policyNumber' => 'MAIF-FOCUS-2026-001',
                'startDate' => new DateTimeImmutable('2026-01-01'),
                'endDate' => new DateTimeImmutable('2026-12-31'),
                'paymentFrequency' => InsurancePaymentFrequencyEnum::Monthly,
                'isActive' => true,
            ],
            [
                'vehicleRegistration' => 'EF-456-GH', // Almera
                'providerName' => 'AXA',
                'policyNumber' => 'AXA-ALMERA-2026-014',
                'startDate' => new DateTimeImmutable('2026-02-15'),
                'endDate' => new DateTimeImmutable('2027-02-14'),
                'paymentFrequency' => InsurancePaymentFrequencyEnum::Yearly,
                'isActive' => true,
            ],
            [
                'vehicleRegistration' => 'IJ-789-KL', // Ninja
                'providerName' => 'AMV',
                'policyNumber' => 'AMV-NINJA-2026-777',
                'startDate' => new DateTimeImmutable('2026-03-01'),
                'endDate' => new DateTimeImmutable('2027-02-28'),
                'paymentFrequency' => InsurancePaymentFrequencyEnum::Monthly,
                'isActive' => true,
            ],
            [
                'vehicleRegistration' => 'MN-321-OP', // Partner
                'providerName' => 'GMF',
                'policyNumber' => 'GMF-PARTNER-2025-332',
                'startDate' => new DateTimeImmutable('2025-09-01'),
                'endDate' => new DateTimeImmutable('2026-08-31'),
                'paymentFrequency' => InsurancePaymentFrequencyEnum::Monthly,
                'isActive' => true,
            ],
            [
                'vehicleRegistration' => 'QR-654-ST', // Clio
                'providerName' => 'Allianz',
                'policyNumber' => 'ALLIANZ-CLIO-2026-090',
                'startDate' => new DateTimeImmutable('2026-02-01'),
                'endDate' => new DateTimeImmutable('2027-01-31'),
                'paymentFrequency' => InsurancePaymentFrequencyEnum::Yearly,
                'isActive' => true,
            ],
        ];

        // (Optionnel) exemple d'historique : ancienne assurance inactive pour la Focus
        $insurancesData[] = [
            'vehicleRegistration' => 'AB-123-CD',
            'providerName' => 'Direct Assurance',
            'policyNumber' => 'DA-FOCUS-2025-099',
            'startDate' => new DateTimeImmutable('2025-01-01'),
            'endDate' => new DateTimeImmutable('2025-12-31'),
            'paymentFrequency' => InsurancePaymentFrequencyEnum::Monthly,
            'isActive' => false,
        ];

        foreach ($insurancesData as $data) {
            $vehicle = $vehiclesByRegistration[$data['vehicleRegistration']] ?? null;

            if (!$vehicle) {
                throw new \RuntimeException(sprintf(
                    'Véhicule introuvable pour la plaque "%s".',
                    $data['vehicleRegistration']
                ));
            }

            $insurance = new VehicleInsurance();
            $insurance->setVehicle($vehicle);
            $insurance->setProviderName($data['providerName']);
            $insurance->setPolicyNumber($data['policyNumber']);
            $insurance->setStartDate($data['startDate']);
            $insurance->setEndDate($data['endDate']);
            $insurance->setPaymentFrequency($data['paymentFrequency']);
            $insurance->setIsActive($data['isActive']);

            $manager->persist($insurance);
        }

        $manager->flush();
    }
}
