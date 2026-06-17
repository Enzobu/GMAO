import { defineConfig } from "vitepress"

export default defineConfig({
  title: "GMAO",
  description: "Documentation utilisateur, administrateur et technique du projet GMAO.",
  lang: "fr-FR",
  cleanUrls: true,
  themeConfig: {
    logo: "/images/logo.svg",
    nav: [
      { text: "Utilisateur", link: "/guide-utilisateur/" },
      { text: "Administrateur", link: "/guide-administrateur/" },
      { text: "Technique", link: "/guide-technique/" },
      { text: "Référence", link: "/reference/commandes" },
    ],
    sidebar: [
      {
        text: "Guide utilisateur",
        items: [
          { text: "Vue d'ensemble", link: "/guide-utilisateur/" },
          { text: "Démarrage", link: "/guide-utilisateur/demarrage" },
          { text: "Connexion", link: "/guide-utilisateur/connexion" },
          { text: "Navigation", link: "/guide-utilisateur/navigation" },
          { text: "Tableau de bord", link: "/guide-utilisateur/tableau-de-bord" },
          { text: "Véhicules", link: "/guide-utilisateur/vehicules" },
          { text: "Assurances", link: "/guide-utilisateur/assurances" },
          { text: "Contrôles techniques", link: "/guide-utilisateur/controles-techniques" },
          { text: "Interventions", link: "/guide-utilisateur/interventions" },
          { text: "Stock", link: "/guide-utilisateur/stock" },
          { text: "Utilisateurs", link: "/guide-utilisateur/utilisateurs" },
          { text: "Documents", link: "/guide-utilisateur/documents" },
          { text: "Recherche et filtres", link: "/guide-utilisateur/recherche-et-filtres" },
          { text: "Notifications", link: "/guide-utilisateur/notifications" },
          { text: "Compte utilisateur", link: "/guide-utilisateur/compte-utilisateur" },
          { text: "Problèmes fréquents", link: "/guide-utilisateur/problemes-frequents" },
        ],
      },
      {
        text: "Guide administrateur",
        items: [
          { text: "Vue d'ensemble", link: "/guide-administrateur/" },
          { text: "Rôles et droits", link: "/guide-administrateur/roles-et-droits" },
          { text: "Paramètres", link: "/guide-administrateur/parametres" },
          { text: "Données et suppression", link: "/guide-administrateur/donnees-et-suppression" },
          { text: "Maintenance", link: "/guide-administrateur/maintenance" },
        ],
      },
      {
        text: "Guide technique",
        items: [
          { text: "Vue d'ensemble", link: "/guide-technique/" },
          { text: "Architecture", link: "/guide-technique/architecture" },
          { text: "Installation locale", link: "/guide-technique/installation-locale" },
          { text: "Environnement", link: "/guide-technique/environnement" },
          { text: "Docker", link: "/guide-technique/docker" },
          { text: "API", link: "/guide-technique/api" },
          { text: "Frontend", link: "/guide-technique/frontend" },
          { text: "Authentification", link: "/guide-technique/authentification" },
          { text: "Uploads documents", link: "/guide-technique/uploads-documents" },
          { text: "Tests", link: "/guide-technique/tests" },
          { text: "Qualité et CI", link: "/guide-technique/qualite-et-ci" },
          { text: "Déploiement", link: "/guide-technique/deploiement" },
        ],
      },
      {
        text: "Référence",
        items: [
          { text: "Commandes", link: "/reference/commandes" },
          { text: "Variables d'environnement", link: "/reference/variables-environnement" },
          { text: "Routes API", link: "/reference/routes-api" },
          { text: "Statuts et workflows", link: "/reference/statuts-et-workflows" },
          { text: "Glossaire", link: "/reference/glossaire" },
        ],
      },
      {
        text: "Changelog",
        items: [{ text: "Versions", link: "/changelog/" }],
      },
    ],
    search: {
      provider: "local",
    },
    footer: {
      message: "Documentation du projet GMAO.",
    },
  },
})
