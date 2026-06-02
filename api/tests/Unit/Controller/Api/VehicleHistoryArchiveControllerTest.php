<?php

namespace App\Tests\Unit\Controller\Api;

use App\Controller\Api\VehicleHistoryArchiveController;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Repository\DocumentRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\VehicleInspectionRepository;
use App\Repository\VehicleInsuranceRepository;
use App\Repository\VehicleRepository;
use App\Service\DocumentAccessChecker;
use App\Service\DocumentManager;
use App\Service\DocumentParentResolver;
use App\Service\VehicleHistoryArchiveBuilder;
use App\Service\VehicleHistoryArchiveFormatter;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class VehicleHistoryArchiveControllerTest extends TestCase
{
    public function testDownloadReturnsZipAttachment(): void
    {
        $vehicle = (new Vehicle())
            ->setName('clio')
            ->setRegistration('AA-123-AA')
            ->setBrand('renault')
            ->setModel('clio');
        $vehicles = $this->createMock(VehicleRepository::class);
        $vehicles->expects(self::once())->method('find')->with(10)->willReturn($vehicle);
        $controller = new VehicleHistoryArchiveController(
            $vehicles,
            $this->accessChecker(),
            $this->archiveBuilder(),
        );

        $response = $controller->download(10);

        self::assertSame('application/zip', $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        self::assertStringContainsString('historique_Clio_AA-123-AA.zip', (string) $response->headers->get('Content-Disposition'));

        $path = $response->getFile()->getPathname();
        if (is_file($path)) {
            unlink($path);
        }
    }

    public function testDownloadRejectsMissingVehicle(): void
    {
        $vehicles = $this->createMock(VehicleRepository::class);
        $vehicles->method('find')->with(10)->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        (new VehicleHistoryArchiveController(
            $vehicles,
            $this->accessChecker(),
            $this->archiveBuilder(),
        ))->download(10);
    }

    private function accessChecker(): DocumentAccessChecker
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(new User());
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);

        return new DocumentAccessChecker(
            new DocumentParentResolver(
                $this->createMock(EntityManagerInterface::class),
                $this->createMock(DocumentRepository::class),
            ),
            $tokenStorage,
            $authorizationChecker,
        );
    }

    private function archiveBuilder(): VehicleHistoryArchiveBuilder
    {
        $insuranceRepository = $this->createMock(VehicleInsuranceRepository::class);
        $insuranceRepository->method('findByVehicle')->willReturn([]);
        $inspectionRepository = $this->createMock(VehicleInspectionRepository::class);
        $inspectionRepository->method('findByVehicle')->willReturn([]);
        $maintenanceRepository = $this->createMock(MaintenanceRepository::class);
        $maintenanceRepository->method('findForVehicle')->willReturn([]);

        return new VehicleHistoryArchiveBuilder(
            $insuranceRepository,
            $inspectionRepository,
            $maintenanceRepository,
            $this->createMock(DocumentRepository::class),
            $this->createMock(DocumentManager::class),
            new VehicleHistoryArchiveFormatter(),
        );
    }
}
