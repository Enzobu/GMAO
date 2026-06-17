# API

L'API est basée sur Symfony et API Platform.

Les routes API Platform sont préfixées par `/api`. La route de connexion JWT est
`/api/login`.

Les entités exposées utilisent des groupes de sérialisation et peuvent être
complétées par des state processors pour gérer les règles métier.
