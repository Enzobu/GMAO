<?php

namespace App\Tests\Unit\Controller;

use App\Controller\DocumentController;
use App\Entity\Document;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use App\Service\DocumentManager;
use App\Service\VehicleManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class DocumentControllerTest extends TestCase
{
    public function testShowRejectsDeletedDocument(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->controller()->show(
            (new Document())->setIsDeleted(true),
            $this->createMock(VehicleManager::class),
            $this->createMock(DocumentManager::class),
        );
    }

    public function testShowRejectsAnonymousUser(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->controller(null)->show(
            new Document(),
            $this->createMock(VehicleManager::class),
            $this->createMock(DocumentManager::class),
        );
    }

    public function testDownloadReturnsFileResponseForAuthenticatedUser(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'document-');
        file_put_contents($file, 'content');
        $document = (new Document())
            ->setStoredFilename(basename($file))
            ->setOriginalFilename('original.txt')
            ->setMimeType('text/plain');
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('fileExists')->with($document)->willReturn(true);
        $documentManager->method('getAbsolutePath')->with($document)->willReturn($file);
        $documentManager->method('getDownloadFilename')->with($document)->willReturn('original.txt');

        $response = $this->controller(new User(), true)->download(
            $document,
            $this->createMock(VehicleManager::class),
            $documentManager,
        );

        self::assertSame(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $response->headers->get('Content-Disposition') ? 'attachment' : null);
        self::assertSame('text/plain', $response->headers->get('Content-Type'));

        unlink($file);
    }

    public function testShowRejectsMissingFile(): void
    {
        $document = new Document();
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('fileExists')->with($document)->willReturn(false);

        $this->expectException(NotFoundHttpException::class);

        $this->controller(new User(), true)->show(
            $document,
            $this->createMock(VehicleManager::class),
            $documentManager,
        );
    }

    public function testShowAllowsAuthorizedLinkedVehicle(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'document-');
        file_put_contents($file, 'content');
        $user = $this->userWithId(1);
        $vehicle = new Vehicle();
        $document = (new Document())->setVehicle($vehicle);
        $vehicleManager = $this->createMock(VehicleManager::class);
        $vehicleManager->expects(self::once())->method('isAuthorized')->with($user, $vehicle)->willReturn(true);
        $documentManager = $this->documentManagerFor($document, $file, 'document.pdf');

        $response = $this->controller($user)->show($document, $vehicleManager, $documentManager);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));

        unlink($file);
    }

    public function testShowRejectsDeletedLinkedVehicle(): void
    {
        $document = (new Document())->setVehicle((new Vehicle())->setIsDeleted(true));

        $this->expectException(NotFoundHttpException::class);

        $this->controller(new User())->show(
            $document,
            $this->createMock(VehicleManager::class),
            $this->createMock(DocumentManager::class),
        );
    }

    public function testShowAllowsDocumentOwnerWhenVehicleUnauthorized(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'document-');
        file_put_contents($file, 'content');
        $user = $this->userWithId(10);
        $document = (new Document())
            ->setVehicle(new Vehicle())
            ->setUser($user);
        $vehicleManager = $this->createMock(VehicleManager::class);
        $vehicleManager->method('isAuthorized')->willReturn(false);
        $documentManager = $this->documentManagerFor($document, $file, 'owner.pdf');

        $response = $this->controller($user)->show($document, $vehicleManager, $documentManager);

        self::assertSame(200, $response->getStatusCode());

        unlink($file);
    }

    public function testShowRejectsUnauthorizedUser(): void
    {
        $document = (new Document())
            ->setVehicle(new Vehicle())
            ->setUser($this->userWithId(2));
        $vehicleManager = $this->createMock(VehicleManager::class);
        $vehicleManager->method('isAuthorized')->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->controller($this->userWithId(1))->show(
            $document,
            $vehicleManager,
            $this->createMock(DocumentManager::class),
        );
    }

    public function testShowRejectsUserWithoutLinkedVehicleOrOwnership(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->controller($this->userWithId(1))->show(
            new Document(),
            $this->createMock(VehicleManager::class),
            $this->createMock(DocumentManager::class),
        );
    }

    public function testShowResolvesVehicleFromInsurance(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'document-');
        file_put_contents($file, 'content');
        $user = $this->userWithId(1);
        $vehicle = new Vehicle();
        $insurance = (new VehicleInsurance())->setVehicle($vehicle);
        $document = (new Document())->setVehicleInsurance($insurance);
        $vehicleManager = $this->createMock(VehicleManager::class);
        $vehicleManager->expects(self::once())->method('isAuthorized')->with($user, $vehicle)->willReturn(true);

        $response = $this->controller($user)->show($document, $vehicleManager, $this->documentManagerFor($document, $file, 'insurance.pdf'));

        self::assertSame(200, $response->getStatusCode());

        unlink($file);
    }

    public function testShowResolvesVehicleFromInspection(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'document-');
        file_put_contents($file, 'content');
        $user = $this->userWithId(1);
        $vehicle = new Vehicle();
        $inspection = (new VehicleInspection())->setVehicle($vehicle);
        $document = (new Document())->setVehicleInspection($inspection);
        $vehicleManager = $this->createMock(VehicleManager::class);
        $vehicleManager->expects(self::once())->method('isAuthorized')->with($user, $vehicle)->willReturn(true);

        $response = $this->controller($user)->show($document, $vehicleManager, $this->documentManagerFor($document, $file, 'inspection.pdf'));

        self::assertSame(200, $response->getStatusCode());

        unlink($file);
    }

    private function documentManagerFor(Document $document, string $file, string $filename): DocumentManager
    {
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('fileExists')->with($document)->willReturn(true);
        $documentManager->method('getAbsolutePath')->with($document)->willReturn($file);
        $documentManager->method('getDownloadFilename')->with($document)->willReturn($filename);

        return $documentManager;
    }

    private function userWithId(int $id): User
    {
        $user = new User();
        $reflection = new \ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, $id);

        return $user;
    }

    private function controller(?User $user = null, bool $isGranted = false): DocumentController
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($token);
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->with('ROLE_USER', null)->willReturn($isGranted);
        $controller = new DocumentController();
        $controller->setContainer(new ControllerTestContainer([
            'security.token_storage' => $storage,
            'security.authorization_checker' => $auth,
        ]));

        return $controller;
    }
}
