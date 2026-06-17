# Uploads documents

Les documents uploadés dépendent du paramètre Symfony `documents_directory` et
de la variable `DOCUMENT_DIRECTORY`.

Les fichiers doivent être servis via des requêtes authentifiées en `blob`.

N'utilisez pas directement un `iframe` ou une balise `img` si cela suppose que
le navigateur enverra automatiquement le jeton Bearer.
