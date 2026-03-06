<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\Vehicle;
use App\Entity\VehicleInsurance;
use App\Entity\VehicleInspection;
use App\Entity\VehicleMaintenance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocumentManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $uploadDirectory,
    ) {
    }

    public function createDocument(
        Vehicle|VehicleInsurance|VehicleInspection|VehicleMaintenance $parent,
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
            VehicleMaintenance::class => $document->setVehicleMaintenance($parent),
        };

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return $document;
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