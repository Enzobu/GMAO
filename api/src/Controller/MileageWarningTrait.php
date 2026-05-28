<?php

namespace App\Controller;

use App\Entity\Maintenance;
use App\Service\VehicleManager;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

trait MileageWarningTrait
{
    protected function getMaintenanceMileageContribution(Maintenance $maintenance): ?int
    {
        return $maintenance->getFinishedAt() !== null ? $maintenance->getMileage() : null;
    }

    protected function shouldStopForMileageWarning(
        Request $request,
        FormInterface $form,
        ?array $warning,
        ?array &$mileageWarning,
        string $fieldName = 'mileage',
    ): bool {
        $mileageWarning = null;

        if ($warning === null) {
            return false;
        }

        if ($this->isGranted('ROLE_ADMIN') && $request->request->get(VehicleManager::FORCE_MILEAGE_FIELD) === '1') {
            return false;
        }

        $form->get($fieldName)->addError(new FormError($warning['fieldError']));

        if ($this->isGranted('ROLE_ADMIN')) {
            $mileageWarning = $warning;
        }

        return true;
    }
}
