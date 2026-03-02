<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\InspectionCenter;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use App\Enum\InsurancePaymentFrequencyEnum;
use App\Enum\InspectionResultEnum;
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
        // --------------------
        // USERS
        // --------------------
        $userAdmin = (new User())
            ->setFirstname('enzo')
            ->setLastname('palermo')
            ->setEmail('gmao@gmail.com')
            ->setPassword('$2y$13$GKJo1Sdw4/FhIL821Nz3bujHBv3mz/VsiRRLPU.H0B6PCxonDR9w2')
            ->setRoles(['ROLE_USER', 'ROLE_ADMIN'])
        ;

        $user = (new User())
            ->setFirstname('alexandra')
            ->setLastname('palermo')
            ->setEmail('gio.alex.pa@gmail.com')
            ->setPassword('$2y$13$GKJo1Sdw4/FhIL821Nz3bujHBv3mz/VsiRRLPU.H0B6PCxonDR9w2')
            ->setRoles(['ROLE_USER'])
        ;

        $userAdmin->setAddress(
            (new Address())
                ->setLine1('1 rue du Dev')
                ->setPostalCode('11100')
                ->setCity('Narbonne')
                ->setCountry('FR')
        );
        
        $user->setAddress(
            (new Address())
                ->setLine1('2 avenue du Soleil')
                ->setPostalCode('34000')
                ->setCity('Montpellier')
                ->setCountry('FR')
        );

        $manager->persist($userAdmin);
        $manager->persist($user);

        // --------------------
        // VEHICLES
        // --------------------
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
                'purchaseDate' => new DateTimeImmutable('2023-06-12'),
                'purchasePrice' => '2499.00',
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
                'purchaseDate' => new DateTimeImmutable('2025-12-10'),
                'purchasePrice' => '5000.00',
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
            $vehicle = (new Vehicle())
                ->setName($data['name'])
                ->setRegistration($data['registration'])
                ->setBrand($data['brand'])
                ->setModel($data['model'])
                ->setType($data['type'])
                ->setYear($data['year'])
                ->setVin($data['vin'])
                ->setEngine($data['engine'])
                ->setFuelType($data['fuel'])
                ->setTransmission($data['transmission'])
                ->setLastMileage($data['km'])
                ->setColor($data['color'])
                ->setStatus($data['status'])
                ->setPurchaseDate($data['purchaseDate'])
                ->setPurchasePrice($data['purchasePrice'])
                ->setUser($data['user'])
            ;

            $manager->persist($vehicle);
            $vehiclesByRegistration[$data['registration']] = $vehicle;
        }

        // --------------------
        // INSURANCE
        // --------------------
        $insurancesData = [
            [
                'vehicleRegistration' => 'AB-123-CD',
                'providerName' => 'MAIF',
                'policyNumber' => 'MAIF-FOCUS-2026-001',
                'startDate' => new DateTimeImmutable('2026-01-01'),
                'endDate' => new DateTimeImmutable('2026-12-31'),
                'paymentFrequency' => InsurancePaymentFrequencyEnum::Monthly,
                'isActive' => true,
            ],
            [
                'vehicleRegistration' => 'EF-456-GH',
                'providerName' => 'AXA',
                'policyNumber' => 'AXA-ALMERA-2026-014',
                'startDate' => new DateTimeImmutable('2026-02-15'),
                'endDate' => new DateTimeImmutable('2027-02-14'),
                'paymentFrequency' => InsurancePaymentFrequencyEnum::Yearly,
                'isActive' => true,
            ],
            [
                'vehicleRegistration' => 'IJ-789-KL',
                'providerName' => 'AMV',
                'policyNumber' => 'AMV-NINJA-2026-777',
                'startDate' => new DateTimeImmutable('2026-03-01'),
                'endDate' => new DateTimeImmutable('2027-02-28'),
                'paymentFrequency' => InsurancePaymentFrequencyEnum::Monthly,
                'isActive' => true,
            ],
            [
                'vehicleRegistration' => 'MN-321-OP',
                'providerName' => 'GMF',
                'policyNumber' => 'GMF-PARTNER-2025-332',
                'startDate' => new DateTimeImmutable('2025-09-01'),
                'endDate' => new DateTimeImmutable('2026-08-31'),
                'paymentFrequency' => InsurancePaymentFrequencyEnum::Monthly,
                'isActive' => true,
            ],
            [
                'vehicleRegistration' => 'QR-654-ST',
                'providerName' => 'Allianz',
                'policyNumber' => 'ALLIANZ-CLIO-2026-090',
                'startDate' => new DateTimeImmutable('2026-02-01'),
                'endDate' => new DateTimeImmutable('2027-01-31'),
                'paymentFrequency' => InsurancePaymentFrequencyEnum::Yearly,
                'isActive' => true,
            ],
            // historique
            [
                'vehicleRegistration' => 'AB-123-CD',
                'providerName' => 'Direct Assurance',
                'policyNumber' => 'DA-FOCUS-2025-099',
                'startDate' => new DateTimeImmutable('2025-01-01'),
                'endDate' => new DateTimeImmutable('2025-12-31'),
                'paymentFrequency' => InsurancePaymentFrequencyEnum::Monthly,
                'isActive' => false,
            ],
        ];

        foreach ($insurancesData as $data) {
            $vehicle = $vehiclesByRegistration[$data['vehicleRegistration']] ?? null;
            if (!$vehicle) {
                throw new \RuntimeException(sprintf('Véhicule introuvable pour la plaque "%s".', $data['vehicleRegistration']));
            }

            $insurance = (new VehicleInsurance())
                ->setVehicle($vehicle)
                ->setProviderName($data['providerName'])
                ->setPolicyNumber($data['policyNumber'])
                ->setStartDate($data['startDate'])
                ->setEndDate($data['endDate'])
                ->setPaymentFrequency($data['paymentFrequency'])
                ->setIsActive($data['isActive'])
            ;

            $manager->persist($insurance);
        }

        // --------------------
        // INSPECTION CENTERS + ADDRESSES
        // --------------------
        $centerNarbonne = (new InspectionCenter())
            ->setName('AutoSécurité Narbonne')
            ->setPhone('0468000000')
            ->setEmail('narbonne@autosecurite.example')
            ->setAddress(
                (new Address())
                    ->setLine1('10 Avenue du Contrôle')
                    ->setPostalCode('11100')
                    ->setCity('Narbonne')
                    ->setCountry('FR')
            )
        ;

        $centerMontpellier = (new InspectionCenter())
            ->setName('Dekra Montpellier')
            ->setPhone('0467000000')
            ->setEmail('montpellier@dekra.example')
            ->setAddress(
                (new Address())
                    ->setLine1('5 Rue des Tests')
                    ->setPostalCode('34000')
                    ->setCity('Montpellier')
                    ->setCountry('FR')
            )
        ;

        $manager->persist($centerNarbonne);
        $manager->persist($centerMontpellier);

        // --------------------
        // VEHICLE INSPECTIONS (CT)
        // --------------------
        $inspectionsData = [
            [
                'vehicleRegistration' => 'AB-123-CD', // Focus
                'center' => $centerNarbonne,
                'inspectionDate' => new DateTimeImmutable('2025-06-10'),
                'validUntil' => new DateTimeImmutable('2027-06-09'),
                'mileage' => 172000,
                'result' => InspectionResultEnum::Pass,
                'counterVisitRequired' => false,
                'counterVisitDueAt' => null,
                'notes' => 'RAS',
            ],
            [
                'vehicleRegistration' => 'EF-456-GH', // Almera
                'center' => $centerNarbonne,
                'inspectionDate' => new DateTimeImmutable('2024-11-05'),
                'validUntil' => new DateTimeImmutable('2026-11-04'),
                'mileage' => 235000,
                'result' => InspectionResultEnum::CounterVisit,
                'counterVisitRequired' => true,
                'counterVisitDueAt' => new DateTimeImmutable('2024-12-05'),
                'notes' => 'Contre-visite pour éclairage',
            ],
            [
                'vehicleRegistration' => 'QR-654-ST', // Clio
                'center' => $centerMontpellier,
                'inspectionDate' => new DateTimeImmutable('2025-02-20'),
                'validUntil' => new DateTimeImmutable('2027-02-19'),
                'mileage' => 87000,
                'result' => InspectionResultEnum::Pass,
                'counterVisitRequired' => false,
                'counterVisitDueAt' => null,
                'notes' => null,
            ],
        ];

        foreach ($inspectionsData as $data) {
            $vehicle = $vehiclesByRegistration[$data['vehicleRegistration']] ?? null;
            if (!$vehicle) {
                throw new \RuntimeException(sprintf('Véhicule introuvable pour la plaque "%s".', $data['vehicleRegistration']));
            }

            $inspection = (new VehicleInspection())
                ->setVehicle($vehicle)
                ->setCenter($data['center'])
                ->setInspectionDate($data['inspectionDate'])
                ->setValidUntil($data['validUntil'])
                ->setMileage($data['mileage'])
                ->setResult($data['result'])
                ->setCounterVisitRequired($data['counterVisitRequired'])
                ->setCounterVisitDueAt($data['counterVisitDueAt'])
                ->setNotes($data['notes'])
            ;

            $manager->persist($inspection);
        }

        $manager->flush();
    }
}
