# Uploads documents

Les documents uploadés dépendent du paramètre Symfony `documents_directory` et
de la variable `DOCUMENT_DIRECTORY`.

Les fichiers doivent être servis via des requêtes authentifiées en `blob`.

N'utilisez pas directement un `iframe` ou une balise `img` si cela suppose que
le navigateur enverra automatiquement le jeton Bearer.

## Ressources concernées

Les documents peuvent être attachés à plusieurs ressources métier :

- utilisateurs ;
- véhicules ;
- assurances ;
- contrôles techniques ;
- interventions ;
- stock.

## Côté frontend

Le composant `DocumentsPanel` gère :

- la liste des documents ;
- l'ajout ;
- la modification du nom et de la description ;
- l'archivage ;
- l'aperçu ;
- le téléchargement.

Les formulaires de création peuvent utiliser `FormDocumentsField` pour attacher
des fichiers directement après création de la ressource.

## Limite de taille

La limite côté frontend est de 8 Mo par fichier.

Si un document dépasse cette taille, l'interface affiche un message d'erreur et
propose un lien de compression PDF dans certains écrans.

## Aperçu et téléchargement

Pour afficher un document, le frontend demande un `Blob` authentifié à l'API.

Ensuite :

- les PDF sont affichés dans un `iframe` à partir d'une URL objet locale ;
- les images sont affichées dans une balise `img` à partir d'une URL objet ;
- les autres fichiers sont proposés en ouverture ou téléchargement.

Cette approche évite d'exposer une URL publique non authentifiée.

## Production

En production, le dossier de documents est monté dans un volume persistant du
service `apache`.

Avant une migration ou un redéploiement, vérifiez :

- le chemin `DOCUMENT_DIRECTORY` ;
- le montage Docker ;
- les permissions d'écriture ;
- les sauvegardes du répertoire.
