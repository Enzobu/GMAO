<?php

namespace App\Service;

use App\Entity\VehicleMaintenance;

class VehicleMaintenanceManager
{
    public function setupNextDue(VehicleMaintenance $maintenance): void
    {
        $type = $maintenance->getMaintenanceType();

        if (!$type) {
            return;
        }

        // --- KM ---
        if ($type->getIntervalKm() !== null && $maintenance->getMileage() !== null) {
            $maintenance->setNextDueMileage(
                $maintenance->getMileage() + $type->getIntervalKm()
            );
        } else {
            $maintenance->setNextDueMileage(null);
        }

        // --- DATE ---
        if ($type->getIntervalMonths() !== null) {
            $maintenance->setNextDueDate(
                $maintenance->getPerformedAt()
                    ->modify('+' . $type->getIntervalMonths() . ' months')
            );
        } else {
            $maintenance->setNextDueDate(null);
        }
    }
}





