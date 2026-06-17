# Documents

Les documents peuvent être associés aux principales ressources de l'application.

Ils servent à conserver les justificatifs, factures, attestations, rapports,
photos ou tout autre fichier utile au suivi.

## Ressources compatibles

Un panneau **Documents** est disponible sur :

- les véhicules ;
- les assurances ;
- les contrôles techniques ;
- les interventions ;
- les lignes de stock ;
- le profil utilisateur.

## Ajouter un document depuis une fiche

Si vous avez les droits, cliquez sur **Ajouter un document** dans le panneau
Documents.

![Panneau documents](/images/captures/documents-panneau.png)

Champs disponibles :

- fichier, obligatoire ;
- nom affiché, obligatoire ;
- description.

Le nom est prérempli à partir du nom du fichier, mais il peut être modifié avant
l'enregistrement.

## Ajouter des documents à la création

Certains formulaires permettent d'ajouter des documents directement à la
création :

- assurance ;
- contrôle technique ;
- intervention ;
- ligne de stock.

Dans ce cas, les documents sélectionnés sont attachés à la ressource après son
enregistrement.

La modification d'une ressource existante ne réaffiche pas toujours les champs
d'ajout initial. Utilisez alors le panneau **Documents** de la fiche.

## Limite de taille

La taille maximale d'un document est de **8 Mo**.

Si un PDF est trop volumineux, l'application propose un lien vers un outil de
compression PDF.

## Consulter un document

Chaque document propose :

- **Voir** : ouvre l'aperçu dans une fenêtre ;
- **Télécharger** : télécharge le fichier original ;
- **Modifier** : modifie le nom ou la description si vous avez les droits ;
- **Archiver** : masque le document si vous avez les droits de suppression.

Les PDF et images sont affichés directement dans l'aperçu. Les autres types de
fichiers peuvent être ouverts dans un nouvel onglet ou téléchargés.

![Aperçu document](/images/captures/document-apercu.png)

## Accès sécurisé

L'accès aux fichiers est authentifié.

Les fichiers ne sont pas servis comme des URL publiques : l'application les
récupère via une requête sécurisée puis les affiche ou les télécharge.

Si un document ne s'ouvre pas, reconnectez-vous puis réessayez.
