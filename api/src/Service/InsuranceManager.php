<?php

namespace App\Service;

use App\Entity\Vehicle;
use App\Entity\VehicleInsurance;
use App\Repository\VehicleInsuranceRepository;
use Doctrine\ORM\EntityManagerInterface;

class InsuranceManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private VehicleInsuranceRepository $insuranceRepository
    ) {}

    public function createInsurance(
        Vehicle $vehicle,
        VehicleInsurance $insurance,
        bool $activate = true
    ): void {
        $this->em->wrapInTransaction(function () use ($vehicle, $insurance, $activate) {

            $insurance->setVehicle($vehicle);

            if ($activate) {
                $this->insuranceRepository->deactivateAllForVehicle($vehicle);
                $insurance->setIsActive(true);
            }

            $this->em->persist($insurance);
        });
    }

    public function activateInsurance(VehicleInsurance $insurance): void
    {
        $this->em->wrapInTransaction(function () use ($insurance) {

            $vehicle = $insurance->getVehicle();

            $this->insuranceRepository->deactivateAllForVehicle($vehicle);
            $insurance->setIsActive(true);

            $this->em->persist($insurance);
        });
    }
}
