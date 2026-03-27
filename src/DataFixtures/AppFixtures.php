<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\Document;
use App\Entity\InspectionCenter;
use App\Entity\MaintenancePart;
use App\Entity\Part;
use App\Entity\PartType;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use App\Enum\InsurancePaymentFrequencyEnum;
use App\Enum\InspectionResultEnum;
use App\Enum\MaintenanceStatusEnum;
use App\Enum\MaintenanceTypeEnum;
use App\Enum\VehicleColorEnum;
use App\Enum\VehicleFuelTypeEnum;
use App\Enum\VehicleStatusEnum;
use App\Enum\VehicleTransmissionTypeEnum;
use App\Enum\VehicleTypeEnum;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private ContainerBagInterface $params,
    ) {}

    public function load(ObjectManager $manager): void
    {
        if ($this->params->get('laod_datafixtures') === 'all' || $this->params->get('laod_datafixtures') === 'user_only') {
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
    
            $manager->flush();
        }

        if ($this->params->get('laod_datafixtures') === 'all') {
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
                    'color' => VehicleColorEnum::Black,
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
                    'color' => VehicleColorEnum::Gray,
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
                    'color' => VehicleColorEnum::Green,
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
                    'color' => VehicleColorEnum::white,
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
                    'color' => VehicleColorEnum::Red,
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
    
            $manager->flush();
    
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
    
            $manager->flush();
    
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
    
            $manager->flush();
    
            // --------------------
            // VEHICLE INSPECTIONS (CT)
            // --------------------
            $inspectionsData = [
                [
                    'vehicleRegistration' => 'AB-123-CD',
                    'center' => $centerNarbonne,
                    'inspectionDate' => new DateTimeImmutable('2023-06-10'),
                    'validUntil' => new DateTimeImmutable('2025-06-09'),
                    'mileage' => 172000,
                    'result' => InspectionResultEnum::Pass,
                    'counterVisitRequired' => false,
                    'counterVisitDueAt' => null,
                    'notes' => 'RAS',
                ],
                [
                    'vehicleRegistration' => 'AB-123-CD',
                    'center' => $centerNarbonne,
                    'inspectionDate' => new DateTimeImmutable('2025-06-02'),
                    'validUntil' => new DateTimeImmutable('2027-06-03'),
                    'mileage' => 206000,
                    'result' => InspectionResultEnum::Pass,
                    'counterVisitRequired' => false,
                    'counterVisitDueAt' => null,
                    'notes' => 'Parre boue AVD endomagé',
                ],
                [
                    'vehicleRegistration' => 'EF-456-GH',
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
                    'vehicleRegistration' => 'QR-654-ST',
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
    
            // --------------------
            // DOCUMENT
            // --------------------
            $document01 = new Document();
            $document02 = new Document();
    
            $document01
                ->setName('Carte grise')
                ->setOriginalFilename('carte_grise_focus.pdf')
                ->setStoredFilename('carte_grise_focus.pdf')
                ->setMimeType('application/pdf')
                ->setExtension('pdf')
                ->setSize(245000)
                ->setDescription('Carte grise')
                ->setVehicle($vehiclesByRegistration['AB-123-CD'])
            ;
    
            $document02
                ->setName('Certificat de cession')
                ->setOriginalFilename('cetificat_session.pdf')
                ->setStoredFilename('cetificat_session.pdf')
                ->setMimeType('application/pdf')
                ->setExtension('pdf')
                ->setSize(245000)
                ->setDescription('Certificat de cession')
                ->setVehicle($vehiclesByRegistration['AB-123-CD'])
            ;
    
            $manager->persist($document01);
            $manager->persist($document02);
    
            $manager->flush();

            // --------------------
            // PART TYPES
            // --------------------
            $partTypesData = [
                ['name' => 'Filtre à huile'],
                ['name' => 'Filtre à air'],
                ['name' => 'Filtre habitacle'],
                ['name' => 'Bougie'],
                ['name' => 'Plaquettes de frein avant'],
                ['name' => 'Plaquettes de frein arrière'],
                ['name' => 'Kit distribution'],
                ['name' => 'Joint de bouchon de vidange'],
            ];

            $partTypes = [];

            foreach ($partTypesData as $data) {
                $partType = (new PartType())
                    ->setName($data['name']);

                $manager->persist($partType);

                $partTypes[$data['name']] = $partType;
            }

            $manager->flush();


            // --------------------
            // PARTS (STOCK)
            // --------------------
            $partsData = [
                [
                    'type' => 'Filtre à huile',
                    'quantity' => 2,
                    'vehicles' => ['AB-123-CD', 'EF-456-GH'],
                    'note' => 'Compatible moteurs essence Nissan/Ford',
                ],
                [
                    'type' => 'Filtre à air',
                    'quantity' => 1,
                    'vehicles' => ['AB-123-CD'],
                    'note' => null,
                ],
                [
                    'type' => 'Plaquettes de frein avant',
                    'quantity' => 0,
                    'vehicles' => ['AB-123-CD', 'MN-321-OP'],
                    'note' => 'À utiliser en priorité',
                ],
                [
                    'type' => 'Bougie',
                    'quantity' => 4,
                    'vehicles' => ['EF-456-GH'],
                    'note' => null,
                ],
                [
                    'type' => 'Joint de bouchon de vidange',
                    'quantity' => 5,
                    'vehicles' => ['AB-123-CD', 'EF-456-GH', 'QR-654-ST'],
                    'note' => null,
                ],
            ];

            foreach ($partsData as $data) {

                $part = (new Part())
                    ->setPartType($partTypes[$data['type']])
                    ->setQuantity($data['quantity'])
                    ->setNote($data['note']);

                foreach ($data['vehicles'] as $registration) {
                    $vehicle = $vehiclesByRegistration[$registration] ?? null;

                    if (!$vehicle) {
                        throw new \RuntimeException(sprintf(
                            'Véhicule introuvable pour la plaque "%s".',
                            $registration
                        ));
                    }

                    $part->addVehicle($vehicle);
                }

                $manager->persist($part);
            }

            $manager->flush();

            // --------------------
            // MAINTENANCES
            // --------------------
            $maintenancesData = [
                [
                    'vehicleRegistration' => 'AB-123-CD',
                    'type' => MaintenanceTypeEnum::OIL_CHANGE,
                    'mileage' => 180000,
                    'performedAt' => new DateTimeImmutable('2025-01-10'),
                    'plannedAt' => null,
                    'status' => MaintenanceStatusEnum::Completed,
                    'isExternal' => false,
                    'notes' => 'Vidange + filtre à huile',
                    'nextDueMileage' => 190000,
                    'nextDueAt' => null,
                    'parts' => [
                        [
                            'type' => 'Filtre à huile',
                            'quantity' => 1,
                            'notes' => null,
                        ],
                        [
                            'type' => 'Joint de bouchon de vidange',
                            'quantity' => 1,
                            'notes' => null,
                        ],
                    ],
                ],
                [
                    'vehicleRegistration' => 'EF-456-GH',
                    'type' => MaintenanceTypeEnum::SPARK_PLUGS,
                    'mileage' => 240000,
                    'performedAt' => new DateTimeImmutable('2025-02-20'),
                    'plannedAt' => null,
                    'status' => MaintenanceStatusEnum::Completed,
                    'isExternal' => true,
                    'notes' => 'Changement bougies',
                    'nextDueMileage' => 260000,
                    'nextDueAt' => null,
                    'parts' => [
                        [
                            'type' => 'Bougie',
                            'quantity' => 4,
                            'notes' => null,
                        ],
                    ],
                ],
                [
                    'vehicleRegistration' => 'QR-654-ST',
                    'type' => MaintenanceTypeEnum::BRAKE_PADS,
                    'mileage' => 210000,
                    'performedAt' => null,
                    'plannedAt' => new DateTimeImmutable('2026-04-01'),
                    'status' => MaintenanceStatusEnum::ToDo,
                    'isExternal' => false,
                    'notes' => 'Prévoir remplacement plaquettes avant',
                    'nextDueMileage' => null,
                    'nextDueAt' => null,
                    'parts' => [
                        [
                            'type' => 'Plaquettes de frein avant',
                            'quantity' => 1,
                            'notes' => null,
                        ],
                    ],
                ],
            ];

            foreach ($maintenancesData as $data) {
                $vehicle = $vehiclesByRegistration[$data['vehicleRegistration']] ?? null;

                if (!$vehicle) {
                    throw new \RuntimeException(sprintf(
                        'Véhicule introuvable pour la plaque "%s".',
                        $data['vehicleRegistration']
                    ));
                }

                $maintenance = (new \App\Entity\Maintenance())
                    ->setVehicle($vehicle)
                    ->setMaintenanceType($data['type'])
                    ->setMileage($data['mileage'])
                    ->setPerformedAt($data['performedAt'])
                    ->setPlannedAt($data['plannedAt'])
                    ->setStatus($data['status'])
                    ->setIsExternal($data['isExternal'])
                    ->setNotes($data['notes'])
                    ->setNextDueMileage($data['nextDueMileage'])
                    ->setNextDueAt($data['nextDueAt']);

                // --------------------
                // MAINTENANCE PARTS
                // --------------------
                foreach ($data['parts'] as $partData) {

                    $part = null;

                    // on récupère une part existante via son type
                    foreach ($partTypes as $name => $partType) {
                        if ($name === $partData['type']) {
                            // on cherche une Part liée à ce type
                            foreach ($manager->getRepository(Part::class)->findAll() as $existingPart) {
                                if ($existingPart->getPartType() === $partType) {
                                    $part = $existingPart;
                                    break;
                                }
                            }
                        }
                    }

                    if (!$part) {
                        throw new \RuntimeException(sprintf(
                            'Part introuvable pour le type "%s".',
                            $partData['type']
                        ));
                    }

                    $maintenancePart = (new MaintenancePart())
                        ->setMaintenance($maintenance)
                        ->setPart($part)
                        ->setQuantity($partData['quantity'])
                        ->setNotes($partData['notes']);

                    $maintenance->addMaintenancePart($maintenancePart);

                    $manager->persist($maintenancePart);
                }

                $manager->persist($maintenance);
            }

            $manager->flush();
        }
    }
}