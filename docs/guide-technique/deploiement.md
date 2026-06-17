# Déploiement

Le workflow CI contient les étapes de déploiement pré-production et production.

La pré-production est déclenchée depuis la branche dédiée. La production est
déclenchée par tag selon la configuration GitHub Actions.

Avant un déploiement, vérifiez les migrations, les variables d'environnement et
les dépendances aux documents uploadés.
