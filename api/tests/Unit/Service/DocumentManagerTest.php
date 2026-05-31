<?php

namespace App\Tests\Unit\Service;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use App\Service\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class DocumentManagerTest extends TestCase
{
    private string $uploadDirectory;

    protected function setUp(): void
    {
        $this->uploadDirectory = sys_get_temp_dir().'/gmao-documents-'.bin2hex(random_bytes(6));
        mkdir($this->uploadDirectory, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->uploadDirectory)) {
            $this->removeDirectory($this->uploadDirectory);
        }
    }

    public function testConstructorRejectsMissingUploadDirectory(): void
    {
        $params = $this->createMock(ContainerBagInterface::class);
        $params->method('get')->with('documents_directory')->willReturn($this->uploadDirectory.'/missing');

        $this->expectException(\RuntimeException::class);

        new DocumentManager($this->createMock(EntityManagerInterface::class), $params);
    }

    public function testCreateDocumentMovesFileAndPersistsMetadata(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'upload-');
        file_put_contents($source, 'content');
        $file = new UploadedFile($source, 'facture.pdf', 'application/pdf', null, true);
        $vehicle = new Vehicle();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(Document::class));
        $em->expects(self::once())->method('flush');

        $document = $this->manager($em)->createDocument($vehicle, $file, 'Facture', 'Description');

        self::assertSame('Facture', $document->getName());
        self::assertSame('facture.pdf', $document->getOriginalFilename());
        self::assertSame('Description', $document->getDescription());
        self::assertSame($vehicle, $document->getVehicle());
        self::assertFileExists($this->uploadDirectory.'/'.$document->getStoredFilename());
    }

    public function testCreateDocumentSupportsOtherParents(): void
    {
        $manager = $this->manager();
        $insurance = new VehicleInsurance();
        $inspection = new VehicleInspection();
        $user = new User();
        $part = new Part();
        $maintenance = new Maintenance();

        self::assertSame($insurance, $manager->createDocument($insurance, $this->uploadedFile('insurance.pdf'), 'Insurance')->getVehicleInsurance());
        self::assertSame($inspection, $manager->createDocument($inspection, $this->uploadedFile('inspection.pdf'), 'Inspection')->getVehicleInspection());
        self::assertSame($user, $manager->createDocument($user, $this->uploadedFile('user.pdf'), 'User')->getUser());
        self::assertSame($part, $manager->createDocument($part, $this->uploadedFile('part.pdf'), 'Part')->getPart());
        self::assertSame($maintenance, $manager->createDocument($maintenance, $this->uploadedFile('maintenance.pdf'), 'Maintenance')->getMaintenance());
    }

    public function testSoftDeleteMovesExistingFileAndFlushes(): void
    {
        file_put_contents($this->uploadDirectory.'/stored.txt', 'content');
        $document = (new Document())->setStoredFilename('stored.txt');
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $this->manager($em)->softDelete($document);

        self::assertTrue($document->isDeleted());
        self::assertSame('deleted/stored.txt', $document->getDeletedStoredFilename());
        self::assertFileDoesNotExist($this->uploadDirectory.'/stored.txt');
        self::assertFileExists($this->uploadDirectory.'/deleted/stored.txt');
    }

    public function testSoftDeleteDoesNothingWhenDocumentIsAlreadyDeleted(): void
    {
        $document = (new Document())
            ->setStoredFilename('stored.txt')
            ->setIsDeleted(true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $this->manager($em)->softDelete($document);

        self::assertSame('stored.txt', $document->getStoredFilename());
    }

    public function testSoftDeleteDoesNothingWhenDocumentHasNoStoredFilename(): void
    {
        $document = new Document();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $this->manager($em)->softDelete($document);

        self::assertFalse($document->isDeleted());
    }

    public function testSoftDeleteMarksDeletedWhenStoredFileIsMissing(): void
    {
        $document = (new Document())->setStoredFilename('missing.txt');
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $this->manager($em)->softDelete($document);

        self::assertTrue($document->isDeleted());
        self::assertSame('deleted/missing.txt', $document->getDeletedStoredFilename());
    }

    public function testGetAbsolutePathRejectsDocumentWithoutStoredFilename(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->manager()->getAbsolutePath(new Document());
    }

    public function testDownloadFilenameFallsBackToStoredFilenameThenGenericName(): void
    {
        $manager = $this->manager();

        self::assertSame('stored.txt', $manager->getDownloadFilename((new Document())->setStoredFilename('stored.txt')));
        self::assertSame('document', $manager->getDownloadFilename(new Document()));
    }

    public function testPathHelpersUseStoredAndOriginalFilenames(): void
    {
        file_put_contents($this->uploadDirectory.'/stored.txt', 'content');
        $document = (new Document())
            ->setStoredFilename('stored.txt')
            ->setOriginalFilename('original.txt');
        $manager = $this->manager();

        self::assertSame($this->uploadDirectory.'/stored.txt', $manager->getAbsolutePath($document));
        self::assertTrue($manager->fileExists($document));
        self::assertSame('original.txt', $manager->getDownloadFilename($document));
    }

    private function manager(?EntityManagerInterface $em = null): DocumentManager
    {
        $params = $this->createMock(ContainerBagInterface::class);
        $params->method('get')->with('documents_directory')->willReturn($this->uploadDirectory);

        return new DocumentManager($em ?? $this->createMock(EntityManagerInterface::class), $params);
    }

    private function uploadedFile(string $name): UploadedFile
    {
        $source = tempnam(sys_get_temp_dir(), 'upload-');
        self::assertIsString($source);
        file_put_contents($source, 'content');

        return new UploadedFile($source, $name, 'application/pdf', null, true);
    }

    private function removeDirectory(string $directory): void
    {
        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.'/'.$entry;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
