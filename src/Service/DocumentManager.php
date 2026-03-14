<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\Vehicle;
use App\Entity\VehicleInsurance;
use App\Entity\VehicleInspection;
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
    }

    public function createDocument(
        Vehicle|VehicleInsurance|VehicleInspection $parent,
        UploadedFile $file,
        string $name,
        ?string $description = null,
    ): Document {
        $originalFilename = $file->getClientOriginalName();
        $extension = $file->guessExtension() ?? $file->getClientOriginalExtension() ?? 'bin';
        $storedFilename = uniqid('doc_', true).'.'.$extension;

        $file->move($this->uploadDirectory, $storedFilename);

        $document = (new Document())
            ->setName($name)
            ->setOriginalFilename($originalFilename)
            ->setStoredFilename($storedFilename)
            ->setExtension($extension)
            ->setMimeType($file->getMimeType() ?? 'application/octet-stream')
            ->setSize($file->getSize() ?? 0)
            ->setDescription($description);

        match ($parent::class) {
            Vehicle::class => $document->setVehicle($parent),
            VehicleInsurance::class => $document->setVehicleInsurance($parent),
            VehicleInspection::class => $document->setVehicleInspection($parent),
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
}

// Utilisations :

// -------- Vehicle --------
// $documentManager->createDocument(
//     parent: $vehicle,
//     file: $form->get('file')->getData(),
//     name: 'Carte grise',
// );

// -------- Vehicle --------
// $documentManager->createDocument(
//     parent: $insurance,
//     file: $form->get('file')->getData(),
//     name: 'Attestation assurance',
// );

// -------- Vehicle --------
// $documentManager->createDocument(
//     parent: $inspection,
//     file: $form->get('file')->getData(),
//     name: 'Rapport CT',
// );

// -------- Vehicle --------
// $documentManager->createDocument(
//     parent: $maintenance,
//     file: $form->get('file')->getData(),
//     name: 'Facture vidange',
// );