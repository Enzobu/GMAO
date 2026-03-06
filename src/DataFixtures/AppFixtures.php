<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\Document;
use App\Entity\InspectionCenter;
use App\Entity\InventoryItem;
use App\Entity\MaintenanceType;
use App\Entity\MaintenanceTypePartRequirement;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use App\Entity\VehicleMaintenance;
use App\Entity\VehicleMaintenancePart;
use App\Enum\InsurancePaymentFrequencyEnum;
use App\Enum\InspectionResultEnum;
use App\Enum\MaintenanceStatusEnum;
use App\Enum\VehicleFuelTypeEnum;
use App\Enum\VehicleStatusEnum;
use App\Enum\VehicleTransmissionTypeEnum;
use App\Enum\VehicleTypeEnum;
use App\Repository\VehicleRepository;
use ArrayObject;
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

        $manager->flush();

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

        // ============================================================
        // =====================  PARTS / STOCK  =======================
        // ============================================================

        // --------------------
        // PARTS
        // --------------------
        $partsData = [
            // Voiture
            ['name' => 'Filtre à huile', 'category' => 'Filtration', 'unit' => 'pcs', 'brand' => 'Mann', 'reference' => 'W712/95', 'barcode' => null, 'notes' => null],
            ['name' => 'Huile moteur 5W30', 'category' => 'Fluide', 'unit' => 'L', 'brand' => 'Total', 'reference' => 'INEO-5W30', 'barcode' => null, 'notes' => 'Bidon 5L'],
            ['name' => 'Filtre à air', 'category' => 'Filtration', 'unit' => 'pcs', 'brand' => 'Bosch', 'reference' => 'F026400', 'barcode' => null, 'notes' => null],
            ['name' => 'Liquide de frein DOT4', 'category' => 'Fluide', 'unit' => 'L', 'brand' => 'Motul', 'reference' => 'DOT4', 'barcode' => null, 'notes' => null],

            // Moto
            ['name' => 'Filtre à huile moto', 'category' => 'Filtration', 'unit' => 'pcs', 'brand' => 'Hiflo', 'reference' => 'HF303', 'barcode' => null, 'notes' => null],
            ['name' => 'Huile moto 10W40', 'category' => 'Fluide', 'unit' => 'L', 'brand' => 'Motul', 'reference' => '7100-10W40', 'barcode' => null, 'notes' => 'Bidon 4L'],
            ['name' => 'Kit chaîne', 'category' => 'Transmission', 'unit' => 'pcs', 'brand' => 'DID', 'reference' => 'KIT-520', 'barcode' => null, 'notes' => 'Chaîne + pignon + couronne'],
            ['name' => 'Plaquettes de frein avant', 'category' => 'Freinage', 'unit' => 'set', 'brand' => 'Brembo', 'reference' => '07BB04', 'barcode' => null, 'notes' => null],
        ];

        $partsByKey = [];
        foreach ($partsData as $p) {
            $part = (new Part())
                ->setName($p['name'])
                ->setCategory($p['category'])
                ->setUnit($p['unit'])
                ->setBrand($p['brand'])
                ->setReference($p['reference'])
                ->setBarcode($p['barcode'])
                ->setNotes($p['notes'])
            ;

            $manager->persist($part);

            // clé simple pour retrouver
            $partsByKey[$p['brand'].'|'.$p['reference']] = $part;
        }

        $manager->flush();

        // --------------------
        // INVENTORY (stock)
        // (unique par part_id)
        // --------------------
        $inventoryData = [
            ['key' => 'Mann|W712/95', 'qty' => '2.00', 'min' => '1.00', 'loc' => 'Garage - étagère A', 'avg' => '8.50', 'last' => '8.90'],
            ['key' => 'Total|INEO-5W30', 'qty' => '5.00', 'min' => '5.00', 'loc' => 'Garage - sol', 'avg' => '7.99', 'last' => '7.99'],
            ['key' => 'Bosch|F026400', 'qty' => '1.00', 'min' => '1.00', 'loc' => 'Garage - étagère A', 'avg' => '18.00', 'last' => '18.00'],
            ['key' => 'Motul|DOT4', 'qty' => '1.00', 'min' => '1.00', 'loc' => 'Garage - étagère B', 'avg' => '12.00', 'last' => '12.00'],
            ['key' => 'Hiflo|HF303', 'qty' => '2.00', 'min' => '1.00', 'loc' => 'Garage - étagère A', 'avg' => '9.90', 'last' => '9.90'],
            ['key' => 'Motul|7100-10W40', 'qty' => '4.00', 'min' => '4.00', 'loc' => 'Garage - sol', 'avg' => '10.50', 'last' => '10.50'],
        ];

        foreach ($inventoryData as $i) {
            $part = $partsByKey[$i['key']] ?? null;
            if (!$part) {
                throw new \RuntimeException(sprintf('Part introuvable pour key "%s".', $i['key']));
            }

            $inv = (new InventoryItem())
                ->setPart($part)
                ->setQuantity($i['qty'])
                ->setMinQuantity($i['min'])
                ->setLocation($i['loc'])
                ->setAverageUnitCost($i['avg'])
                ->setLastPurchaseUnitCost($i['last'])
            ;

            $manager->persist($inv);
        }

        $manager->flush();

        // --------------------
        // MAINTENANCE TYPES
        // --------------------
        $maintenanceTypesData = [
            ['name' => 'Vidange + filtre', 'km' => 15000, 'months' => 12, 'desc' => 'Vidange moteur + remplacement filtre à huile'],
            ['name' => 'Filtre à air', 'km' => 30000, 'months' => 24, 'desc' => 'Remplacement filtre à air'],
            ['name' => 'Liquide de frein', 'km' => null, 'months' => 24, 'desc' => 'Purge + remplacement liquide de frein'],
            ['name' => 'Kit chaîne', 'km' => 20000, 'months' => null, 'desc' => 'Remplacement kit chaîne (moto)'],
            ['name' => 'Plaquettes avant', 'km' => null, 'months' => null, 'desc' => 'Remplacement plaquettes avant (moto/auto selon modèle)'],
        ];

        $maintenanceTypesByName = [];
        foreach ($maintenanceTypesData as $t) {
            $mt = (new MaintenanceType())
                ->setName($t['name'])
                ->setIntervalKm($t['km'])
                ->setIntervalMonths($t['months'])
                ->setDescription($t['desc'])
            ;

            $manager->persist($mt);
            $maintenanceTypesByName[$t['name']] = $mt;
        }

        $manager->flush();

        // --------------------
        // MAINTENANCE TYPE -> PART REQUIREMENTS (unique maintenance_type_id + part_id)
        // --------------------
        $requirementsData = [
            // Vidange voiture
            ['type' => 'Vidange + filtre', 'partKey' => 'Mann|W712/95', 'qty' => '1.00', 'optional' => false, 'notes' => null],
            ['type' => 'Vidange + filtre', 'partKey' => 'Total|INEO-5W30', 'qty' => '4.50', 'optional' => false, 'notes' => 'approx'],

            // Vidange moto
            ['type' => 'Vidange + filtre', 'partKey' => 'Hiflo|HF303', 'qty' => '1.00', 'optional' => false, 'notes' => null],
            ['type' => 'Vidange + filtre', 'partKey' => 'Motul|7100-10W40', 'qty' => '2.10', 'optional' => false, 'notes' => 'Kawasaki 400 approx'],

            // Filtre à air
            ['type' => 'Filtre à air', 'partKey' => 'Bosch|F026400', 'qty' => '1.00', 'optional' => false, 'notes' => null],

            // Liquide frein
            ['type' => 'Liquide de frein', 'partKey' => 'Motul|DOT4', 'qty' => '1.00', 'optional' => false, 'notes' => null],

            // Kit chaine
            ['type' => 'Kit chaîne', 'partKey' => 'DID|KIT-520', 'qty' => '1.00', 'optional' => false, 'notes' => null],

            // Plaquettes
            ['type' => 'Plaquettes avant', 'partKey' => 'Brembo|07BB04', 'qty' => '1.00', 'optional' => false, 'notes' => null],
        ];

        foreach ($requirementsData as $r) {
            $mt = $maintenanceTypesByName[$r['type']] ?? null;
            $part = $partsByKey[$r['partKey']] ?? null;

            if (!$mt || !$part) {
                throw new \RuntimeException(sprintf('Requirement invalide type="%s" part="%s"', $r['type'], $r['partKey']));
            }

            $req = (new MaintenanceTypePartRequirement())
                ->setMaintenanceType($mt)
                ->setPart($part)
                ->setQuantityRequired($r['qty'])
                ->setIsOptional($r['optional'])
                ->setNotes($r['notes'])
            ;

            $manager->persist($req);
        }

        $manager->flush();

        // --------------------
        // VEHICLE MAINTENANCES
        // --------------------
        $focus = $vehiclesByRegistration['AB-123-CD'];
        $almera = $vehiclesByRegistration['EF-456-GH'];
        $ninja = $vehiclesByRegistration['IJ-789-KL'];

        $vm1 = (new VehicleMaintenance())
            ->setVehicle($focus)
            ->setMaintenanceType($maintenanceTypesByName['Vidange + filtre'])
            ->setPerformedAt(null)
            ->setIsPlanned(true)
            ->setNextDueDate(new DateTimeImmutable('2026-04-20'))
            ->setMileage(183200)
            ->setCost('89.90')
            ->setNotes('Vidange + filtre à huile')
            ->setStatus(MaintenanceStatusEnum::ToDo)
        ;
        $manager->persist($vm1);

        $vm2 = (new VehicleMaintenance())
            ->setVehicle($focus)
            ->setMaintenanceType($maintenanceTypesByName['Vidange + filtre'])
            ->setPerformedAt(null)
            ->setIsPlanned(true)
            ->setNextDueDate(new DateTimeImmutable('2026-05-20'))
            ->setMileage(183200)
            ->setCost('89.90')
            ->setNotes('Vidange + filtre à huile')
            ->setStatus(MaintenanceStatusEnum::ToDo)
        ;
        $manager->persist($vm2);

        $vm3 = (new VehicleMaintenance())
            ->setVehicle($ninja)
            ->setMaintenanceType($maintenanceTypesByName['Vidange + filtre'])
            ->setPerformedAt(new DateTimeImmutable('2026-02-10'))
            ->setMileage(7800)
            ->setCost('55.00')
            ->setNotes('Vidange moto')
            ->setStatus(MaintenanceStatusEnum::Completed)
        ;
        $manager->persist($vm3);

        $vm4 = (new VehicleMaintenance())
            ->setVehicle($almera)
            ->setMaintenanceType($maintenanceTypesByName['Liquide de frein'])
            ->setPerformedAt(new DateTimeImmutable('now')) // planned
            ->setMileage(184638)
            ->setCost(null)
            ->setNotes('À planifier')
            ->setStatus(MaintenanceStatusEnum::ToDo)
        ;
        $manager->persist($vm4);

        $manager->flush();

        // --------------------
        // VEHICLE MAINTENANCE PARTS (unique vehicle_maintenance_id + part_id)
        // --------------------
        $vmpData = [
            // Focus vidange
            ['vm' => $vm1, 'partKey' => 'Mann|W712/95', 'qty' => '1.00', 'price' => '8.90', 'fromStock' => true],
            ['vm' => $vm1, 'partKey' => 'Total|INEO-5W30', 'qty' => '4.50', 'price' => '7.99', 'fromStock' => true],

            // Ninja vidange
            ['vm' => $vm2, 'partKey' => 'Hiflo|HF303', 'qty' => '1.00', 'price' => '9.90', 'fromStock' => true],
            ['vm' => $vm2, 'partKey' => 'Motul|7100-10W40', 'qty' => '2.10', 'price' => '10.50', 'fromStock' => true],
        ];

        foreach ($vmpData as $x) {
            $part = $partsByKey[$x['partKey']] ?? null;
            if (!$part) {
                throw new \RuntimeException(sprintf('Part introuvable pour key "%s" (VMP).', $x['partKey']));
            }

            $vmp = (new VehicleMaintenancePart())
                ->setVehicleMaintenance($x['vm'])
                ->setPart($part)
                ->setQuantityUsed($x['qty'])
                ->setUnitPrice($x['price'])
                ->setFromStock($x['fromStock'])
                ->setNotes(null)
            ;

            $manager->persist($vmp);
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

    }
}