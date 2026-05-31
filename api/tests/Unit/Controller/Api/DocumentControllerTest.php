<?php

namespace App\Tests\Unit\Controller\Api;

use App\Controller\Api\DocumentController;
use App\Entity\Document;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Repository\DocumentRepository;
use App\Service\DocumentManager;
use App\Service\DocumentParentResolver;
use App\Tests\Unit\Controller\ControllerTestContainer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class DocumentControllerTest extends TestCase
{
    public function testIndexListsParentDocuments(): void
    {
        $user = $this->withId(new User(), 10);
        $document = (new Document())->setUser($user)->setName('Carte grise');
        $entityManager = $this->entityManager(User::class, $user);
        $repository = $this->createMock(DocumentRepository::class);
        $repository->expects(self::once())->method('findByUser')->with($user)->willReturn([$document]);

        $response = $this->controller($entityManager, $repository, $user)->index('users', 10);
        $payload = json_decode($response->getContent(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Carte grise', $payload[0]['name']);
    }

    public function testCreateRejectsUnauthorizedUser(): void
    {
        $owner = $this->withId(new User(), 10);
        $currentUser = $this->withId(new User(), 11);
        $response = $this->controller($this->entityManager(User::class, $owner), user: $currentUser, admin: false)->create('users', 10, new Request(), $this->createMock(DocumentManager::class));

        self::assertSame(403, $response->getStatusCode());
    }

    public function testCreateRejectsUnauthenticatedUser(): void
    {
        $owner = $this->withId(new User(), 10);

        $response = $this->controller($this->entityManager(User::class, $owner), user: null, admin: false)->create('users', 10, new Request(), $this->createMock(DocumentManager::class));

        self::assertSame(401, $response->getStatusCode());
    }

    public function testCreateRejectsMissingFile(): void
    {
        $user = $this->withId(new User(), 10);
        $response = $this->controller($this->entityManager(User::class, $user), user: $user)->create('users', 10, new Request(), $this->createMock(DocumentManager::class));

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('obligatoire', (string) $response->getContent());
    }

    public function testCreateRejectsOversizedFile(): void
    {
        $user = $this->withId(new User(), 10);
        $file = $this->createMock(UploadedFile::class);
        $file->method('getSize')->willReturn(9 * 1024 * 1024);
        $request = new Request(files: ['file' => $file]);

        $response = $this->controller($this->entityManager(User::class, $user), user: $user)->create('users', 10, $request, $this->createMock(DocumentManager::class));

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('Max 8 Mo', (string) $response->getContent());
    }

    public function testCreateStoresDocumentWithFallbackName(): void
    {
        $user = $this->withId(new User(), 10);
        $file = $this->uploadedFile('facture.pdf');
        $request = new Request(request: ['description' => ' Note '], files: ['file' => $file]);
        $manager = $this->createMock(DocumentManager::class);
        $manager->expects(self::once())
            ->method('createDocument')
            ->with($user, $file, 'facture', 'Note')
            ->willReturn((new Document())->setUser($user)->setName('facture'));

        $response = $this->controller($this->entityManager(User::class, $user), user: $user)->create('users', 10, $request, $manager);

        self::assertSame(201, $response->getStatusCode());
    }

    public function testCreateUsesDefaultNameWhenOriginalFilenameIsEmpty(): void
    {
        $user = $this->withId(new User(), 10);
        $file = $this->createMock(UploadedFile::class);
        $file->method('getSize')->willReturn(false);
        $file->method('getClientOriginalName')->willReturn('');
        $manager = $this->createMock(DocumentManager::class);
        $manager->expects(self::once())
            ->method('createDocument')
            ->with($user, $file, 'Document', null)
            ->willReturn((new Document())->setUser($user)->setName('Document'));

        $response = $this->controller($this->entityManager(User::class, $user), user: $user)->create('users', 10, new Request(files: ['file' => $file]), $manager);

        self::assertSame(201, $response->getStatusCode());
    }

    public function testUpdateRejectsDocumentFromAnotherParent(): void
    {
        $user = $this->withId(new User(), 10);
        $document = (new Document())->setUser($this->withId(new User(), 11));

        $response = $this->controller($this->entityManager(User::class, $user), user: $user)->update('users', 10, new Request(content: '{}'), $document);

        self::assertSame(404, $response->getStatusCode());
    }

    public function testUpdateRejectsUnauthorizedUser(): void
    {
        $owner = $this->withId(new User(), 10);
        $currentUser = $this->withId(new User(), 11);
        $document = (new Document())->setUser($owner);

        $response = $this->controller($this->entityManager(User::class, $owner), user: $currentUser, admin: false)->update('users', 10, new Request(content: '{}'), $document);

        self::assertSame(403, $response->getStatusCode());
    }

    public function testUpdateRejectsInvalidPayload(): void
    {
        $user = $this->withId(new User(), 10);
        $document = (new Document())->setUser($user);

        $response = $this->controller($this->entityManager(User::class, $user), user: $user)->update('users', 10, new Request(content: '{invalid'), $document);

        self::assertSame(400, $response->getStatusCode());
    }

    public function testUpdateRejectsMissingName(): void
    {
        $user = $this->withId(new User(), 10);
        $document = (new Document())->setUser($user);

        $response = $this->controller($this->entityManager(User::class, $user), user: $user)->update('users', 10, new Request(content: json_encode(['name' => ' '])), $document);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testUpdatePersistsMetadata(): void
    {
        $user = $this->withId(new User(), 10);
        $document = (new Document())->setUser($user);
        $entityManager = $this->entityManager(User::class, $user);
        $entityManager->expects(self::once())->method('flush');

        $response = $this->controller($entityManager, user: $user)->update('users', 10, new Request(content: json_encode(['name' => ' Facture ', 'description' => ' '])), $document);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Facture', $document->getName());
        self::assertNull($document->getDescription());
    }

    public function testDeleteRequiresAdmin(): void
    {
        $user = $this->withId(new User(), 10);
        $document = (new Document())->setUser($user);

        $response = $this->controller($this->entityManager(User::class, $user), user: $user, admin: false)->delete('users', 10, $document, $this->createMock(DocumentManager::class));

        self::assertSame(403, $response->getStatusCode());
    }

    public function testDeleteSoftDeletesDocument(): void
    {
        $user = $this->withId(new User(), 10);
        $document = (new Document())->setUser($user);
        $manager = $this->createMock(DocumentManager::class);
        $manager->expects(self::once())->method('softDelete')->with($document);

        $response = $this->controller($this->entityManager(User::class, $user), user: $user)->delete('users', 10, $document, $manager);

        self::assertSame(204, $response->getStatusCode());
    }

    public function testDeleteIgnoresAlreadyDeletedDocument(): void
    {
        $user = $this->withId(new User(), 10);
        $document = (new Document())->setUser($user)->setIsDeleted(true);
        $manager = $this->createMock(DocumentManager::class);
        $manager->expects(self::never())->method('softDelete');

        $response = $this->controller($this->entityManager(User::class, $user), user: $user)->delete('users', 10, $document, $manager);

        self::assertSame(204, $response->getStatusCode());
    }

    public function testFileReturnsInlineResponse(): void
    {
        $user = $this->withId(new User(), 10);
        $document = (new Document())->setUser($user)->setName('Facture')->setMimeType('application/pdf');
        $manager = $this->documentManagerWithExistingFile($document);

        $response = $this->controller($this->entityManager(User::class, $user), user: $user)->showDocumentFile('users', 10, $document, $manager);

        self::assertSame('application/pdf', $response->headers->get('Content-Type'));
        self::assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    public function testDownloadReturnsAttachmentResponse(): void
    {
        $user = $this->withId(new User(), 10);
        $document = (new Document())->setUser($user)->setName('Facture');
        $manager = $this->documentManagerWithExistingFile($document);

        $response = $this->controller($this->entityManager(User::class, $user), user: $user)->download('users', 10, $document, $manager);

        self::assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    public function testFileRejectsMissingFile(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $user = $this->withId(new User(), 10);
        $document = (new Document())->setUser($user);
        $manager = $this->createMock(DocumentManager::class);
        $manager->method('fileExists')->with($document)->willReturn(false);

        $this->controller($this->entityManager(User::class, $user), user: $user)->showDocumentFile('users', 10, $document, $manager);
    }

    public function testFileRejectsDocumentFromAnotherParent(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $user = $this->withId(new User(), 10);
        $document = (new Document())->setUser($this->withId(new User(), 11));

        $this->controller($this->entityManager(User::class, $user), user: $user)->showDocumentFile('users', 10, $document, $this->createMock(DocumentManager::class));
    }

    public function testUnknownParentThrowsNotFound(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->controller($this->entityManager(User::class, null), user: new User())->index('users', 404);
    }

    public function testDeletedParentThrowsNotFound(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $user = $this->withId((new User())->setIsDeleted(true), 10);

        $this->controller($this->entityManager(User::class, $user), user: $user)->index('users', 10);
    }

    public function testVehicleOwnerCanCreateDocument(): void
    {
        $owner = $this->withId(new User(), 5);
        $vehicle = $this->withId((new Vehicle())->setUser($owner), 10);
        $file = $this->uploadedFile('vehicle.pdf');
        $manager = $this->createMock(DocumentManager::class);
        $manager->expects(self::once())->method('createDocument')->willReturn((new Document())->setVehicle($vehicle)->setName('vehicle'));

        $response = $this->controller($this->entityManager(Vehicle::class, $vehicle), user: $owner, admin: false)->create('vehicles', 10, new Request(files: ['file' => $file]), $manager);

        self::assertSame(201, $response->getStatusCode());
    }

    private function controller(
        EntityManagerInterface $entityManager,
        ?DocumentRepository $repository = null,
        ?User $user = null,
        bool $admin = true,
    ): DocumentController {
        $controller = new DocumentController(
            new DocumentParentResolver($entityManager, $repository ?? $this->createMock(DocumentRepository::class)),
            $entityManager,
        );
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->willReturnCallback(static fn (string $role): bool => match ($role) {
            'ROLE_ADMIN' => $admin,
            'ROLE_USER' => $user instanceof User,
            default => false,
        });
        $controller->setContainer(new ControllerTestContainer([
            'security.authorization_checker' => $authorizationChecker,
            'security.token_storage' => $this->tokenStorage($user),
        ]));

        return $controller;
    }

    /** @param class-string $class */
    private function entityManager(string $class, ?object $entity): EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('find')->with($class, self::anything())->willReturn($entity);

        return $entityManager;
    }

    private function tokenStorage(?User $user): TokenStorageInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($token);

        return $storage;
    }

    private function uploadedFile(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'document-controller-');
        self::assertIsString($path);
        file_put_contents($path, 'content');

        return new UploadedFile($path, $name, 'application/pdf', null, true);
    }

    private function documentManagerWithExistingFile(Document $document): DocumentManager&\PHPUnit\Framework\MockObject\MockObject
    {
        $path = tempnam(sys_get_temp_dir(), 'document-response-');
        self::assertIsString($path);
        file_put_contents($path, 'content');
        $manager = $this->createMock(DocumentManager::class);
        $manager->method('fileExists')->with($document)->willReturn(true);
        $manager->method('getAbsolutePath')->with($document)->willReturn($path);
        $manager->method('getDownloadFilename')->with($document)->willReturn('document.pdf');

        return $manager;
    }

    private function withId(object $entity, int $id): object
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);

        return $entity;
    }
}
