<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\InspectionCenter;
use App\Entity\Maintenance;
use App\Entity\MaintenancePart;
use App\Entity\MaintenanceType;
use App\Entity\Part;
use App\Entity\PartType;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use App\Enum\InspectionResultEnum;
use App\Enum\InsurancePaymentFrequencyEnum;
use App\Enum\MaintenanceStatusEnum;
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
        private readonly ContainerBagInterface $params,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $mode = $this->params->get('laod_datafixtures');

        if ($mode !== 'all' && $mode !== 'user_only') {
            return;
        }

        $users = $this->loadUsers($manager);

        if ($mode === 'user_only') {
            return;
        }

        $vehicles = $this->loadVehicles($manager, $users['admin']);
        $inspectionCenters = $this->loadInspectionCenters($manager);
        $partTypes = $this->loadPartTypes($manager);
        $parts = $this->loadParts($manager, $partTypes, $vehicles);
        $maintenanceTypes = $this->loadMaintenanceTypes($manager);

        $this->loadInsurances($manager, $vehicles);
        $this->loadInspections($manager, $vehicles, $inspectionCenters);
        $this->loadMaintenances($manager, $vehicles, $maintenanceTypes, $parts);
    }

    /**
     * @return array{admin:User,user:User}
     */
    private function loadUsers(ObjectManager $manager): array
    {
        $admin = (new User())
            ->setFirstname('enzo')
            ->setLastname('palermo')
            ->setEmail('gmao@gmail.com')
            ->setPassword('$2y$13$GKJo1Sdw4/FhIL821Nz3bujHBv3mz/VsiRRLPU.H0B6PCxonDR9w2')
            ->setRoles(['ROLE_USER', 'ROLE_ADMIN'])
            ->setAddress(
                (new Address())
                    ->setLine1('1 rue du Dev')
                    ->setPostalCode('11100')
                    ->setCity('Narbonne')
                    ->setCountry('FR')
            );

        $user = (new User())
            ->setFirstname('alexandra')
            ->setLastname('palermo')
            ->setEmail('gio.alex.pa@gmail.com')
            ->setPassword('$2y$13$GKJo1Sdw4/FhIL821Nz3bujHBv3mz/VsiRRLPU.H0B6PCxonDR9w2')
            ->setRoles(['ROLE_USER'])
            ->setAddress(
                (new Address())
                    ->setLine1('2 avenue du Soleil')
                    ->setPostalCode('34000')
                    ->setCity('Montpellier')
                    ->setCountry('FR')
            );

        $manager->persist($admin);
        $manager->persist($user);
        $manager->flush();

        return [
            'admin' => $admin,
            'user' => $user,
        ];
    }

    /**
     * @return array<string, Vehicle>
     */
    private function loadVehicles(ObjectManager $manager, User $owner): array
    {
        $vehiclesData = [
            [
                'key' => 'porsche-911',
                'name' => '911 Carrera S',
                'registration' => 'GT-911-CS',
                'brand' => 'Porsche',
                'model' => '911 Carrera S 991.2',
                'type' => VehicleTypeEnum::Car,
                'year' => 2017,
                'vin' => 'WP0ZZZ99ZHS123456',
                'engine' => '3.0 flat-six biturbo',
                'fuelType' => VehicleFuelTypeEnum::Petrol,
                'transmission' => VehicleTransmissionTypeEnum::DualClutch,
                'lastMileage' => 68400,
                'color' => VehicleColorEnum::Gray,
                'purchaseDate' => '2017-09-18',
                'purchasePrice' => '87500.00',
            ],
            [
                'key' => 'bmw-m3',
                'name' => 'M3 Competition',
                'registration' => 'BM-003-M3',
                'brand' => 'BMW',
                'model' => 'M3 Competition G80',
                'type' => VehicleTypeEnum::Car,
                'year' => 2021,
                'vin' => 'WBS33AY090FM12345',
                'engine' => '3.0 S58 biturbo',
                'fuelType' => VehicleFuelTypeEnum::Petrol,
                'transmission' => VehicleTransmissionTypeEnum::Automatic,
                'lastMileage' => 39200,
                'color' => VehicleColorEnum::Green,
                'purchaseDate' => '2021-04-12',
                'purchasePrice' => '78500.00',
            ],
            [
                'key' => 'audi-rs3',
                'name' => 'RS3 Sportback',
                'registration' => 'RS-003-AU',
                'brand' => 'Audi',
                'model' => 'RS3 8Y',
                'type' => VehicleTypeEnum::Car,
                'year' => 2022,
                'vin' => 'WUAZZZ8Y5NA123456',
                'engine' => '2.5 TFSI',
                'fuelType' => VehicleFuelTypeEnum::Petrol,
                'transmission' => VehicleTransmissionTypeEnum::DualClutch,
                'lastMileage' => 27800,
                'color' => VehicleColorEnum::Black,
                'purchaseDate' => '2022-07-28',
                'purchasePrice' => '68200.00',
            ],
            [
                'key' => 'alpine-a110',
                'name' => 'A110 S',
                'registration' => 'AL-110-AS',
                'brand' => 'Alpine',
                'model' => 'A110 S',
                'type' => VehicleTypeEnum::Car,
                'year' => 2019,
                'vin' => 'VF1AEFDZZK0123456',
                'engine' => '1.8 turbo',
                'fuelType' => VehicleFuelTypeEnum::Petrol,
                'transmission' => VehicleTransmissionTypeEnum::DualClutch,
                'lastMileage' => 45200,
                'color' => VehicleColorEnum::Blue,
                'purchaseDate' => '2019-11-05',
                'purchasePrice' => '62500.00',
            ],
            [
                'key' => 'nissan-gtr',
                'name' => 'GT-R',
                'registration' => 'GT-035-RR',
                'brand' => 'Nissan',
                'model' => 'GT-R R35',
                'type' => VehicleTypeEnum::Car,
                'year' => 2016,
                'vin' => 'JN1GANR35U0123456',
                'engine' => '3.8 V6 biturbo',
                'fuelType' => VehicleFuelTypeEnum::Petrol,
                'transmission' => VehicleTransmissionTypeEnum::DualClutch,
                'lastMileage' => 91200,
                'color' => VehicleColorEnum::white,
                'purchaseDate' => '2016-03-22',
                'purchasePrice' => '74200.00',
            ],
            [
                'key' => 's1000rr',
                'name' => 'S1000RR',
                'registration' => 'RR-1000-BM',
                'brand' => 'BMW Motorrad',
                'model' => 'S1000RR',
                'type' => VehicleTypeEnum::Motorcycle,
                'year' => 2020,
                'vin' => 'WB10E2100LZ123456',
                'engine' => '999cc 4 cylindres',
                'fuelType' => VehicleFuelTypeEnum::Petrol,
                'transmission' => VehicleTransmissionTypeEnum::Manual,
                'lastMileage' => 18400,
                'color' => VehicleColorEnum::Red,
                'purchaseDate' => '2020-06-16',
                'purchasePrice' => '19800.00',
            ],
            [
                'key' => 'zx6r',
                'name' => 'ZX-6R',
                'registration' => 'ZX-636-KW',
                'brand' => 'Kawasaki',
                'model' => 'Ninja ZX-6R 636',
                'type' => VehicleTypeEnum::Motorcycle,
                'year' => 2024,
                'vin' => 'JKBZX636PPA123456',
                'engine' => '636cc 4 cylindres',
                'fuelType' => VehicleFuelTypeEnum::Petrol,
                'transmission' => VehicleTransmissionTypeEnum::Manual,
                'lastMileage' => 6200,
                'color' => VehicleColorEnum::Green,
                'purchaseDate' => '2024-02-09',
                'purchasePrice' => '12990.00',
            ],
        ];

        $vehicles = [];

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
                ->setFuelType($data['fuelType'])
                ->setTransmission($data['transmission'])
                ->setLastMileage($data['lastMileage'])
                ->setColor($data['color'])
                ->setPurchaseDate(new DateTimeImmutable($data['purchaseDate']))
                ->setPurchasePrice($data['purchasePrice'])
                ->setStatus(VehicleStatusEnum::Active)
                ->setUser($owner);

            $manager->persist($vehicle);
            $vehicles[$data['key']] = $vehicle;
        }

        $manager->flush();

        return $vehicles;
    }

    /**
     * @return array<string, InspectionCenter>
     */
    private function loadInspectionCenters(ObjectManager $manager): array
    {
        $centers = [
            'narbonne' => (new InspectionCenter())
                ->setName('Autosur Narbonne Performance')
                ->setPhone('0468900000')
                ->setEmail('narbonne-performance@autosur.example')
                ->setAddress(
                    (new Address())
                        ->setLine1('14 avenue des Ateliers')
                        ->setPostalCode('11100')
                        ->setCity('Narbonne')
                        ->setCountry('FR')
                ),
            'montpellier' => (new InspectionCenter())
                ->setName('Dekra Montpellier Sud')
                ->setPhone('0467000000')
                ->setEmail('montpellier-sud@dekra.example')
                ->setAddress(
                    (new Address())
                        ->setLine1('5 rue du Controle')
                        ->setPostalCode('34000')
                        ->setCity('Montpellier')
                        ->setCountry('FR')
                ),
        ];

        foreach ($centers as $center) {
            $manager->persist($center);
        }

        $manager->flush();

        return $centers;
    }

    /**
     * @return array<string, PartType>
     */
    private function loadPartTypes(ObjectManager $manager): array
    {
        $names = [
            'Huile moteur 5W40',
            'Huile moteur 10W50',
            'Filtre a huile',
            'Filtre a air sport',
            'Plaquettes frein sport avant',
            'Plaquettes frein sport arriere',
            'Liquide de frein RBF660',
            'Pneus Michelin Pilot Sport 4S',
            'Pneus Pirelli Diablo Rosso IV',
            'Bougies iridium',
            'Kit chaine renforcé',
            'Batterie lithium',
        ];

        $partTypes = [];

        foreach ($names as $name) {
            $partType = (new PartType())->setName($name);
            $manager->persist($partType);
            $partTypes[$name] = $partType;
        }

        $manager->flush();

        return $partTypes;
    }

    /**
     * @param array<string, PartType> $partTypes
     * @param array<string, Vehicle> $vehicles
     * @return array<string, Part>
     */
    private function loadParts(ObjectManager $manager, array $partTypes, array $vehicles): array
    {
        $partsData = [
            ['type' => 'Huile moteur 5W40', 'quantity' => 8, 'vehicles' => ['porsche-911', 'bmw-m3', 'audi-rs3', 'alpine-a110', 'nissan-gtr'], 'note' => 'Bidons 1L pour appoints et vidanges voitures sportives'],
            ['type' => 'Huile moteur 10W50', 'quantity' => 5, 'vehicles' => ['s1000rr', 'zx6r'], 'note' => 'Usage motos sportives'],
            ['type' => 'Filtre a huile', 'quantity' => 7, 'vehicles' => ['porsche-911', 'bmw-m3', 'audi-rs3', 'alpine-a110', 'nissan-gtr', 's1000rr', 'zx6r'], 'note' => null],
            ['type' => 'Filtre a air sport', 'quantity' => 3, 'vehicles' => ['bmw-m3', 'audi-rs3', 'alpine-a110'], 'note' => 'Filtres performance nettoyables'],
            ['type' => 'Plaquettes frein sport avant', 'quantity' => 4, 'vehicles' => ['porsche-911', 'bmw-m3', 'audi-rs3', 'nissan-gtr'], 'note' => 'Jeux avant route/trackday'],
            ['type' => 'Plaquettes frein sport arriere', 'quantity' => 3, 'vehicles' => ['porsche-911', 'bmw-m3', 'nissan-gtr'], 'note' => null],
            ['type' => 'Liquide de frein RBF660', 'quantity' => 6, 'vehicles' => ['porsche-911', 'bmw-m3', 'audi-rs3', 'alpine-a110', 'nissan-gtr', 's1000rr', 'zx6r'], 'note' => 'Flacons haute température'],
            ['type' => 'Pneus Michelin Pilot Sport 4S', 'quantity' => 4, 'vehicles' => ['porsche-911', 'bmw-m3', 'audi-rs3', 'alpine-a110'], 'note' => 'Train complet mixte route'],
            ['type' => 'Pneus Pirelli Diablo Rosso IV', 'quantity' => 2, 'vehicles' => ['s1000rr', 'zx6r'], 'note' => 'Train moto'],
            ['type' => 'Bougies iridium', 'quantity' => 12, 'vehicles' => ['audi-rs3', 'nissan-gtr', 's1000rr', 'zx6r'], 'note' => null],
            ['type' => 'Kit chaine renforcé', 'quantity' => 1, 'vehicles' => ['s1000rr', 'zx6r'], 'note' => 'Kit 525 renforcé'],
            ['type' => 'Batterie lithium', 'quantity' => 2, 'vehicles' => ['s1000rr', 'zx6r', 'alpine-a110'], 'note' => null],
        ];

        $parts = [];

        foreach ($partsData as $data) {
            $part = (new Part())
                ->setPartType($partTypes[$data['type']])
                ->setQuantity($data['quantity'])
                ->setNote($data['note']);

            foreach ($data['vehicles'] as $vehicleKey) {
                $part->addVehicle($vehicles[$vehicleKey]);
            }

            $manager->persist($part);
            $parts[$data['type']] = $part;
        }

        $manager->flush();

        return $parts;
    }

    /**
     * @return array<string, MaintenanceType>
     */
    private function loadMaintenanceTypes(ObjectManager $manager): array
    {
        $types = [
            'Vidange annuelle',
            'Freinage',
            'Pneumatiques',
            'Controle piste',
            'Transmission',
            'Diagnostic moteur',
            'Reparation carrosserie',
            'Reparation mecanique',
            'Controle technique',
        ];

        $maintenanceTypes = [];

        foreach ($types as $type) {
            $maintenanceType = (new MaintenanceType())->setName($type);
            $manager->persist($maintenanceType);
            $maintenanceTypes[$type] = $maintenanceType;
        }

        $manager->flush();

        return $maintenanceTypes;
    }

    /**
     * @param array<string, Vehicle> $vehicles
     */
    private function loadInsurances(ObjectManager $manager, array $vehicles): void
    {
        $providers = ['AXA Passion', 'Allianz Performance', 'MAIF Collection', 'AMV Racing', 'Mutuelle des Motards'];
        $index = 0;

        foreach ($vehicles as $key => $vehicle) {
            $purchaseYear = (int) $vehicle->getPurchaseDate()?->format('Y');
            $provider = $providers[$index % count($providers)];
            $index++;

            if ($purchaseYear < 2025) {
                $oldInsurance = (new VehicleInsurance())
                    ->setVehicle($vehicle)
                    ->setProviderName($provider)
                    ->setPolicyNumber(sprintf('OLD-%s-%d', strtoupper($key), $purchaseYear))
                    ->setStartDate(new DateTimeImmutable(sprintf('%d-01-01', max($purchaseYear, 2016))))
                    ->setEndDate(new DateTimeImmutable('2025-12-31'))
                    ->setPaymentFrequency(InsurancePaymentFrequencyEnum::Yearly)
                    ->setIsActive(false);

                $manager->persist($oldInsurance);
            }

            $activeEndDate = match ($key) {
                'porsche-911' => '2026-06-12',
                'zx6r' => '2026-06-18',
                default => '2026-12-31',
            };

            $activeInsurance = (new VehicleInsurance())
                ->setVehicle($vehicle)
                ->setProviderName($provider)
                ->setPolicyNumber(sprintf('ACT-%s-2026', strtoupper($key)))
                ->setStartDate(new DateTimeImmutable('2026-01-01'))
                ->setEndDate(new DateTimeImmutable($activeEndDate))
                ->setPaymentFrequency(InsurancePaymentFrequencyEnum::Monthly)
                ->setIsActive(true);

            $manager->persist($activeInsurance);
        }

        $manager->flush();
    }

    /**
     * @param array<string, Vehicle> $vehicles
     * @param array<string, InspectionCenter> $centers
     */
    private function loadInspections(ObjectManager $manager, array $vehicles, array $centers): void
    {
        $inspectionsData = [
            ['vehicle' => 'porsche-911', 'date' => '2024-06-10', 'validUntil' => '2026-06-10', 'mileage' => 61000, 'center' => 'narbonne', 'result' => InspectionResultEnum::Pass],
            ['vehicle' => 'bmw-m3', 'date' => '2025-04-12', 'validUntil' => '2027-04-12', 'mileage' => 34100, 'center' => 'montpellier', 'result' => InspectionResultEnum::Pass],
            ['vehicle' => 'audi-rs3', 'date' => '2025-08-02', 'validUntil' => '2027-08-02', 'mileage' => 22800, 'center' => 'narbonne', 'result' => InspectionResultEnum::Pass],
            ['vehicle' => 'alpine-a110', 'date' => '2025-11-20', 'validUntil' => '2027-11-20', 'mileage' => 41100, 'center' => 'montpellier', 'result' => InspectionResultEnum::Pass],
            ['vehicle' => 'nissan-gtr', 'date' => '2023-12-05', 'validUntil' => '2025-12-05', 'mileage' => 83500, 'center' => 'narbonne', 'result' => InspectionResultEnum::CounterVisit],
        ];

        foreach ($inspectionsData as $data) {
            $inspection = (new VehicleInspection())
                ->setVehicle($vehicles[$data['vehicle']])
                ->setCenter($centers[$data['center']])
                ->setInspectionDate(new DateTimeImmutable($data['date']))
                ->setValidUntil(new DateTimeImmutable($data['validUntil']))
                ->setMileage($data['mileage'])
                ->setResult($data['result'])
                ->setCounterVisitRequired($data['result'] === InspectionResultEnum::CounterVisit)
                ->setCounterVisitDueAt($data['result'] === InspectionResultEnum::CounterVisit ? new DateTimeImmutable('2026-01-05') : null)
                ->setNotes($data['result'] === InspectionResultEnum::CounterVisit ? 'Contre-visite ancienne, points de freinage a reprendre.' : 'RAS');

            $manager->persist($inspection);
        }

        $manager->flush();
    }

    /**
     * @param array<string, Vehicle> $vehicles
     * @param array<string, MaintenanceType> $maintenanceTypes
     * @param array<string, Part> $parts
     */
    private function loadMaintenances(ObjectManager $manager, array $vehicles, array $maintenanceTypes, array $parts): void
    {
        $annualTypes = ['Vidange annuelle', 'Freinage', 'Pneumatiques', 'Controle piste', 'Transmission', 'Diagnostic moteur'];
        $partRotation = ['Filtre a huile', 'Huile moteur 5W40', 'Liquide de frein RBF660', 'Plaquettes frein sport avant', 'Pneus Michelin Pilot Sport 4S'];
        $currentYear = 2026;

        foreach ($vehicles as $vehicleIndex => $vehicle) {
            $purchaseYear = (int) $vehicle->getPurchaseDate()?->format('Y');
            $yearOffset = 0;

            for ($year = max($purchaseYear, 2016); $year <= $currentYear; $year++) {
                $month = (($yearOffset * 3) + crc32($vehicleIndex)) % 12 + 1;
                $day = (($yearOffset * 7) + 8) % 22 + 1;
                $performedAt = new DateTimeImmutable(sprintf('%d-%02d-%02d', $year, $month, $day));

                if ($performedAt > new DateTimeImmutable('2026-05-23')) {
                    continue;
                }

                $typeName = $annualTypes[$yearOffset % count($annualTypes)];
                $mileage = max(1000, ((int) $vehicle->getLastMileage()) - (($currentYear - $year) * ($vehicle->getType() === VehicleTypeEnum::Motorcycle ? 3200 : 7800)));
                $partName = $vehicle->getType() === VehicleTypeEnum::Motorcycle && $typeName === 'Transmission'
                    ? 'Kit chaine renforcé'
                    : $partRotation[$yearOffset % count($partRotation)];

                $maintenance = (new Maintenance())
                    ->setVehicle($vehicle)
                    ->setMaintenanceType($maintenanceTypes[$typeName])
                    ->setMileage($mileage)
                    ->setPerformedAt($performedAt)
                    ->setPlannedAt(null)
                    ->setStatus(MaintenanceStatusEnum::Completed)
                    ->setIsExternal($yearOffset % 3 === 0)
                    ->setNotes(sprintf('%s realisee sur %s.', $typeName, $vehicle->displayName()))
                    ->setNextDueMileage($mileage + ($vehicle->getType() === VehicleTypeEnum::Motorcycle ? 6000 : 10000))
                    ->setNextDueAt($performedAt->modify('+1 year'));

                $manager->persist($maintenance);

                if (isset($parts[$partName])) {
                    $maintenancePart = (new MaintenancePart())
                        ->setMaintenance($maintenance)
                        ->setPart($parts[$partName])
                        ->setQuantity($partName === 'Huile moteur 5W40' ? 5 : 1)
                        ->setNotes(null);

                    $maintenance->addMaintenancePart($maintenancePart);
                    $manager->persist($maintenancePart);
                }

                $yearOffset++;
            }
        }

        $repairs = [
            ['vehicle' => 'nissan-gtr', 'type' => 'Reparation mecanique', 'date' => '2024-10-18', 'mileage' => 87200, 'notes' => 'Remplacement capteur pression turbo.'],
            ['vehicle' => 'porsche-911', 'type' => 'Reparation carrosserie', 'date' => '2025-03-04', 'mileage' => 64200, 'notes' => 'Correction impact bas de caisse.'],
            ['vehicle' => 'bmw-m3', 'type' => 'Diagnostic moteur', 'date' => '2025-12-12', 'mileage' => 36500, 'notes' => 'Diagnostic voyant moteur intermittent.'],
            ['vehicle' => 's1000rr', 'type' => 'Reparation mecanique', 'date' => '2026-02-22', 'mileage' => 17600, 'notes' => 'Remplacement levier embrayage et purge.'],
        ];

        foreach ($repairs as $data) {
            $maintenance = (new Maintenance())
                ->setVehicle($vehicles[$data['vehicle']])
                ->setMaintenanceType($maintenanceTypes[$data['type']])
                ->setMileage($data['mileage'])
                ->setPerformedAt(new DateTimeImmutable($data['date']))
                ->setPlannedAt(null)
                ->setStatus(MaintenanceStatusEnum::Completed)
                ->setIsExternal(true)
                ->setNotes($data['notes'])
                ->setNextDueMileage(null)
                ->setNextDueAt(null);

            $manager->persist($maintenance);
        }

        $planned = [
            ['vehicle' => 'porsche-911', 'type' => 'Freinage', 'date' => '2026-06-08', 'mileage' => 69000, 'notes' => 'Prevoir plaquettes avant avant sortie circuit.'],
            ['vehicle' => 'zx6r', 'type' => 'Transmission', 'date' => '2026-06-16', 'mileage' => 6800, 'notes' => 'Controle tension chaine et kit transmission.'],
            ['vehicle' => 'nissan-gtr', 'type' => 'Controle technique', 'date' => '2026-04-12', 'mileage' => 91000, 'notes' => 'Preparation CT en retard.'],
        ];

        foreach ($planned as $data) {
            $maintenance = (new Maintenance())
                ->setVehicle($vehicles[$data['vehicle']])
                ->setMaintenanceType($maintenanceTypes[$data['type']])
                ->setMileage($data['mileage'])
                ->setPerformedAt(null)
                ->setPlannedAt(new DateTimeImmutable($data['date']))
                ->setStatus(MaintenanceStatusEnum::ToDo)
                ->setIsExternal(false)
                ->setNotes($data['notes'])
                ->setNextDueMileage(null)
                ->setNextDueAt(null);

            $manager->persist($maintenance);
        }

        $manager->flush();
    }
}
