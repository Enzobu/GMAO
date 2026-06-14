<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInsurance;
use App\Entity\VehicleInspection;
use App\Exception\DocumentStorageException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class DocumentManager
{
    private string $uploadDirectory;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContainerBagInterface $params,
    ) {
        $this->uploadDirectory = $this->params->get('documents_directory');

        if (!is_dir($this->uploadDirectory)) {
            throw new DocumentStorageException('Le dossier de stockage des documents est introuvable.');
        }
    }

    /**
     * Crée un document lié à une entité métier.
     *
     * Le parent peut être un véhicule, une assurance, un contrôle technique ou un utilisateur.
     *
     * Exemples d’usage :
     * - carte grise liée à un véhicule ;
     * - attestation liée à une assurance ;
     * - rapport de contrôle technique lié à une inspection ;
     * - document personnel lié à un utilisateur.
     *
     * Utilisations :
     *
     * -------- Vehicle --------
     * $documentManager->createDocument(
     *     parent: $vehicle,
     *     file: $form->get('file')->getData(),
     *     name: 'Carte grise',
     * );
     *
     * -------- Vehicle --------
     * $documentManager->createDocument(
     *     parent: $insurance,
     *     file: $form->get('file')->getData(),
     *     name: 'Attestation assurance',
     * );
     *
     * -------- Vehicle --------
     * $documentManager->createDocument(
     *     parent: $inspection,
     *     file: $form->get('file')->getData(),
     *     name: 'Rapport CT',
     * );
     *
     * -------- Vehicle --------
     * $documentManager->createDocument(
     *     parent: $maintenance,
     *     file: $form->get('file')->getData(),
     *     name: 'Facture vidange',
     * );
     */
    public function createDocument(
        Vehicle|VehicleInsurance|VehicleInspection|User|Part|Maintenance $parent,
        UploadedFile $file,
        string $name,
        ?string $description = null,
    ): Document {
        $originalFilename = $this->normalizedOriginalFilename(
            $file->getClientOriginalName(),
        );
        $extension = $file->guessExtension() ?? $file->getClientOriginalExtension() ?? 'bin';
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';
        $size = $file->getSize() ?? 0;
        $storedFilename = uniqid('doc_', true).'.'.$extension;

        $file->move($this->uploadDirectory, $storedFilename);

        $document = (new Document())
            ->setName($name)
            ->setOriginalFilename($originalFilename)
            ->setStoredFilename($storedFilename)
            ->setExtension($extension)
            ->setMimeType($mimeType)
            ->setSize($size)
            ->setDescription($description);

        match ($parent::class) {
            Vehicle::class => $document->setVehicle($parent),
            VehicleInsurance::class => $document->setVehicleInsurance($parent),
            VehicleInspection::class => $document->setVehicleInspection($parent),
            User::class => $document->setUser($parent),
            Part::class => $document->setPart($parent),
            Maintenance::class => $document->setMaintenance($parent),
        };

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return $document;
    }

    public function softDelete(Document $document): void
    {
        if ($document->isDeleted()) {
            return;
        }

        $filename = $document->getStoredFilename();

        if (!$filename) {
            return;
        }

        $sourcePath = $this->uploadDirectory . '/' . $filename;

        $deletedDirectory = $this->uploadDirectory . '/deleted';
        $deletedRelativePath = 'deleted/' . $filename;
        $destinationPath = $this->uploadDirectory . '/' . $deletedRelativePath;

        if (!is_dir($deletedDirectory)) {
            mkdir($deletedDirectory, 0775, true);
        }

        if (file_exists($sourcePath)) {
            rename($sourcePath, $destinationPath);
        }

        $document
            ->setDeletedStoredFilename($deletedRelativePath)
            ->setIsDeleted(true);

        $this->entityManager->flush();
    }

    public function getAbsolutePath(Document $document): string
    {
        $filename = $document->getStoredFilename();

        if (!$filename) {
            throw new DocumentStorageException('Le document ne possède pas de fichier stocké.');
        }

        return rtrim($this->uploadDirectory, '/').'/'.$filename;
    }

    public function fileExists(Document $document): bool
    {
        return is_file($this->getAbsolutePath($document));
    }

    public function getDownloadFilename(Document $document): string
    {
        return $document->getOriginalFilename() ?: $document->getStoredFilename() ?: 'document';
    }

    private function normalizedOriginalFilename(string $filename): string
    {
        return str_replace(' ', '_', trim($filename));
    }
}
