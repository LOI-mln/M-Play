<div align="center">

# 🎬 M-Play - Modern Desktop IPTV Player

![Build](https://img.shields.io/badge/build-passing-success?style=flat&logo=github) ![Version](https://img.shields.io/badge/version-0.5.0-blue?style=flat) ![License](https://img.shields.io/badge/license-ISC-green?style=flat) ![Platform](https://img.shields.io/badge/platform-macOS%20%7C%20Windows-lightgrey?style=flat)

![Electron](https://img.shields.io/badge/Electron-2B2E3A?style=flat&logo=electron&logoColor=9FEAF9) ![NodeJS](https://img.shields.io/badge/Node.js-43853D?style=flat&logo=node.js&logoColor=white) ![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white) ![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=flat&logo=tailwind-css&logoColor=white)

</div>

**M-Play** est une application de bureau haute performance conçue pour transformer l'expérience de streaming IPTV. Elle combine une interface utilisateur moderne avec une architecture technique robuste capable de gérer d'immenses catalogues de VOD (Films & Séries) et de TV en direct.

![M-Play Screenshot](/ressources/logo.png)

## 🚀 Fonctionnalités Clés

### 🎨 Expérience Utilisateur Premium
- **Interface Immersive** : Design "Dark Mode" moderne avec effets de survol, animations fluides et Hero Headers dynamiques.
- **Navigation Intuitive** : Accès rapide aux Films, Séries et Live TV via une sidebar latérale.
- **Badges Intelligents** : Affichage clair des s-aisons et épisodes (ex: `S01 E05`) directement sur les cartes.

### 🎥 Lecteur Vidéo Avancé
- **Streaming Hybride** : Supporte la lecture directe (MKV/MP4) et le transcodage à la volée via FFmpeg pour une compatibilité maximale.
- **Contrôle Total** : Gestion précise du *seeking* (avance/retour), choix des pistes audio et sous-titres.
- **Performance** : Optimisé pour une lecture fluide même avec des fichiers lourds.

### ⏱️ Reprendre la lecture (Continue Watching)
- **Suivi Cross-Type** : Une section unifiée fusionnant Films et Séries, triée par date de visionnage.
- **Sauvegarde Précise** : La progression est enregistrée automatiquement à la seconde près.
- **Métadonnées Intelligentes** : Récupération automatique du contexte (Saison/Épisode) pour une reprise sans friction.

### 🌟 Enrichissement TMDB
- **Métadonnées Complètes** : Utilisation de l'[API TMDB](https://www.themoviedb.org/) pour récupérer automatiquement les affiches, résumés, notes et casting.
- **Tendances** : Affichage des films et séries populaires basé sur les données mondiales de TMDB.
- **Recherche Intelligente** : Amélioration de la pertinence des résultats grâce au matching de titres.

## 🛠 Stack Technique

Une architecture hybride puissante pour le bureau :

- **Conteneur** : [Electron](https://www.electronjs.org/) (Build natif macOS/Windows)
- **Backend UI** : PHP 8.x embarqué (Logique métier, Routing, Sessions)
- **Streaming Engine** : [Node.js](https://nodejs.org/) + [Express](https://expressjs.com/) + [Fluent-FFmpeg](https://github.com/fluent-ffmpeg/node-fluent-ffmpeg)
- **Frontend** : HTML5, Vanilla JS, [TailwindCSS](https://tailwindcss.com/)
- **Data & APIs** : Xtream Codes (IPTV), [The Movie Database (TMDB)](https://developer.themoviedb.org/docs) (Metadata)

## 📦 Installation & Démarrage

### Pré-requis
- Node.js (v16+)
- PHP (CLI installé et accessible dans le PATH)
- FFmpeg (installé et accessible dans le PATH)

### Installation

```bash
# Cloner le projet
git clone https://github.com/LOI-mln/m-play.git

# Installer les dépendances Node
npm install
```

### Configuration
1. Dupliquez `config.sample.php` vers `config.php`.
2. Configurez vos accès base de données (si nécessaire) ou les paramètres par défaut.

### Lancement (Développement)

```bash
# Lance l'application Electron avec les services PHP et Node en arrière-plan
npm start
```

### Build (Production)

Pour créer un exécutable (macOS app par défaut) :

```bash
npm run build
```

## 📂 Structure du Projet

```
m-play/
├── main.js                 # Processus Principal Electron + Node Streamer
├── index.php               # Point d'entrée Backend PHP
├── public/                 # Assets statiques (JS, CSS, Images)
├── src/
│   ├── Controllers/        # Logique métier (Movies, Series, Auth...)
│   ├── Models/             # Accès données (WatchProgress, etc.)
│   └── Services/           # Services tiers (XtreamClient, FileCache...)
├── views/                  # Templates PHP (Layouts, Pages)
└── stream-config.json      # Configuration du transcodage
```

## 📝 Auteur
Développé avec ❤️ par МИЛАН.
