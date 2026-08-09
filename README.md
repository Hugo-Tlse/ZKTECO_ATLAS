# ZKTECO_ATLAS

Projet de **reverse engineering, documentation et exploitation de l'API HTTP interne** des contrôleurs d'accès **ZKTeco TECO Atlas**.

> ⚠️ **Projet non officiel**
> ZKTeco ne fournit pas, à ma connaissance, de documentation publique complète de cette API. Les endpoints et structures présentés dans ce projet ont été identifiés par observation et expérimentation sur un contrôleur Atlas.
L'objectif est de documenter les échanges HTTP avec les contrôleurs Atlas et de permettre l'extraction automatisée des utilisateurs, badges et événements vers une base de données externe.

---

## 🎯 Objectifs

Le projet a pour objectifs :

* identifier les endpoints HTTP utilisés par les contrôleurs Atlas ;
* documenter le mécanisme d'authentification ;
* récupérer les utilisateurs et leurs credentials ;
* récupérer les événements d'accès ;
* identifier la structure JSON retournée par l'API ;
* documenter les codes et sous-codes d'événements ;
* permettre une extraction automatisée des événements ;
* mettre en place une synchronisation régulière vers une base de données externe ;
* éviter la perte d'historique lorsque la capacité de stockage du contrôleur est atteinte.

---

# 🏢 Matériel concerné

Le développement et les tests ont principalement été réalisés autour de la gamme :

* **Atlas 400**

Les modèles suivants sont susceptibles d'utiliser une architecture similaire et restent à confirmer :

* Atlas 100
* Atlas 200
* Atlas Bio 160
* Atlas Bio 260
* Atlas Bio 460

> La compatibilité peut dépendre du modèle, du firmware et de la version de l'application Web.

---

# 💾 Capacité de stockage

Les contrôleurs Atlas étudiés peuvent stocker jusqu'à **10 000 transactions**.

L'objectif du projet est donc de récupérer régulièrement les événements afin de conserver un historique dans une base de données externe.

```text
┌─────────────────────┐
│    ZKTeco Atlas     │
│                     │
│  Transactions       │
│       ↓             │
│  capacité limitée   │
└──────────┬──────────┘
           │
           │ Extraction
           ▼
┌─────────────────────┐
│ Base de données     │
│ externe             │
│                     │
│ Historique complet  │
└─────────────────────┘
```

---

# 🔌 API découverte

Les premiers endpoints identifiés sont :

```text
/authenticate
/credHolder/list
/evt/list
```

## Arbre fonctionnel

```text
ZKTeco Atlas
│
├── Authentication
│   └── /authenticate
│
├── Credential Holders
│   └── /credHolder/list
│
└── Events
    └── /evt/list
```

---

# 🔐 Authentication

## `/authenticate`

L'endpoint `/authenticate` permet d'authentifier un utilisateur auprès du contrôleur.

Une authentification réussie retourne un :

```text
sessionToken
```

Ce token est ensuite utilisé pour les requêtes suivantes.

```text
Client
  │
  │ /authenticate
  ▼
Atlas
  │
  │ sessionToken
  ▼
Client
  │
  ├── /credHolder/list
  │
  └── /evt/list
```

Le mécanisme d'authentification et les paramètres utilisés sont actuellement implémentés dans les scripts PHP du projet.

---

# 👤 Credential Holders / Utilisateurs

## `/credHolder/list`

Cet endpoint permet de récupérer les utilisateurs enregistrés dans le contrôleur ainsi que leurs credentials et leurs privilèges.

### Structure identifiée

```text
User
│
├── unid
├── first
├── last
├── idNum
│
├── creds[]
│   └── name
│
└── privBindings[]
```

### Champs actuellement identifiés

| Champ          | Description                              |
| -------------- | ---------------------------------------- |
| `unid`         | Identifiant interne du credential holder |
| `first`        | Prénom                                   |
| `last`         | Nom                                      |
| `idNum`        | Numéro d'identification                  |
| `creds`        | Liste des credentials associés           |
| `creds[].name` | Nom / identifiant du credential          |
| `privBindings` | Informations relatives aux privilèges    |

Exemple d'accès aux données en PHP :

```php
$user['unid'];
$user['first'];
$user['last'];
$user['idNum'];
$user['creds'][0]['name'];
$user['privBindings'];
```

> La structure complète du JSON reste à documenter. Certains champs peuvent varier selon le modèle ou le firmware.

---

# 🚪 Events / Événements

## `/evt/list`

L'endpoint `/evt/list` permet de récupérer les événements enregistrés par le contrôleur.

La réponse contient notamment une liste d'événements :

```text
instanceList[]
```

Chaque événement identifié contient notamment les informations suivantes.

### Structure actuellement identifiée

```text
Event
│
├── hwTime
├── evtCode
├── evtSubCode
│
├── evtCredHolderRef
│   ├── first
│   ├── last
│   └── idNum
│
├── evtCredRef
│   ├── credNum
│   └── name
│
├── evtDevRef
│   └── name
│
└── evtControllerRef
    └── name
```

---

# ⏱️ Filtrage temporel

L'API permet de filtrer les événements selon une période.

Les paramètres identifiés sont :

```text
hwTimeRestriction
│
├── afterDate_year
├── afterDate_month
├── afterDate_day
├── afterDate_hour
│
├── beforeDate_year
├── beforeDate_month
├── beforeDate_day
└── beforeDate_hour
```

Exemple conceptuel :

```text
afterDate
    │
    ▼
┌──────────────────────┐
│      /evt/list       │
│                      │
│   événements compris │
│   dans la période    │
│                      │
└──────────┬───────────┘
           │
           ▼
       beforeDate
```

Cette fonctionnalité permet d'effectuer des extractions limitées à une période donnée.

---

# 🗂️ Catégories d'événements

L'API accepte également un paramètre :

```text
evtCategoryRestriction.evtCategories
```

Les catégories actuellement utilisées dans les scripts sont :

```text
[0, 1, 2, 3]
```


# 📊 Codes et sous-codes d'événements

Les codes suivants ont actuellement été identifiés :

|    Code | Description                                     |
| ------: | ----------------------------------------------- |
|   `6.0` | Connexion via application                       |
|   `7.0` | Déconnexion                                     |
|   `8.0` | Connexion réussie                               |
|  `48.0` | Accès autorisé (Carte)                          |
|  `49.0` | Accès refusé (général)                          |
|  `49.1` | Accès refusé (Carte expirée)                    |
|  `49.2` | Accès refusé (Badge désactivé)                  |
|  `49.3` | Accès refusé (Tentative usurpation)             |
| `49.11` | Accès refusé (En dehors de l'horaire)           |
| `49.12` | Accès refusé (Verrouillage manuel)              |
| `49.13` | Accès refusé (Limite d'entrée dépassée)         |
| `49.14` | Accès refusé (Numéro de carte inconnu)          |
|  `50.0` | Accès refusé (Porte verrouillée)                |
|  `51.0` | Accès refusé (Alarme active)                    |
|  `52.0` | Alarme activée                                  |
|  `53.0` | Alarme désactivée                               |
|  `54.0` | Connexion (portail Web)                         |
|  `55.0` | Déconnexion (portail Web)                       |
|  `56.0` | Tentative d'accès (carte non valide)            |
| `127.0` | Horaire inactif                                 |
| `128.0` | Horaire inactif                                 |
| `212.0` | Connexion infructueuse (mot de passe incorrect) |

> Cette liste n'est pas considérée comme exhaustive. 

---

# 🔄 Extraction des événements

Le projet contient actuellement deux approches.

## Extraction brute

Le script d'extraction brute permet de récupérer les événements sous leur forme JSON originale.

Cette approche est utilisée pour :

* observer les champs retournés par l'API ;
* identifier de nouveaux attributs ;
* analyser les structures imbriquées ;
* découvrir de nouveaux codes d'événements ;
* documenter le fonctionnement de l'API.

### Principe

```text
Atlas
  │
  │ /evt/list
  ▼
JSON brut
  │
  ▼
Analyse / Reverse Engineering
```

Il est recommandé de conserver cette extraction brute pendant toute la phase de documentation de l'API.

---

# ⚙️ Extraction automatique

Le script CRON permet actuellement d'effectuer une extraction automatique des événements.

Le fonctionnement actuel est :

```text
CRON
 │
 ├── Authentification
 │
 ├── Sélection de la veille
 │
 ├── /evt/list
 │
 ├── Récupération des événements
 │
 └── Insertion en base
```

La récupération actuelle utilise une période correspondant à la journée précédente :

```text
00:00 → 23:00
```

Cette méthode constitue une première version fonctionnelle de synchronisation.

---

# 🛡️ Gestion des doublons

L'import actuel effectue une vérification avant insertion basée sur :

```text
ATLAS_dateh
ATLAS_user
ATLAS_Badge
```

L'objectif est d'éviter l'insertion répétée d'un même événement lors d'une nouvelle synchronisation.

```text
Événement Atlas
      │
      ▼
Recherche d'un événement similaire
      │
      ├── Existe → pas d'insertion
      │
      └── Absent → insertion
```

---

# 🧩 Modèle de données actuellement exploité

Les informations actuellement extraites des événements sont principalement :

```text
Date / heure
   └── hwTime

Événement
   ├── evtCode
   └── evtSubCode

Utilisateur
   ├── first
   ├── last
   └── idNum

Credential
   ├── credNum
   └── name

Périphérique
   └── evtDevRef.name

Contrôleur
   └── evtControllerRef.name
```

---

# 🔬 Reverse Engineering

Le projet a pour but de documenter progressivement l'API utilisée par les contrôleurs Atlas.
Les informations sont obtenues par observation des requêtes et réponses du système.
L'objectif est notamment d'identifier :

```text
API Atlas
│
├── Authentication
│   └── /authenticate
│
├── Credential Holders
│   └── /credHolder/list
│
├── Events
│   └── /evt/list
│
├── Doors
│   └── ?
│
├── Controllers
│   └── ?
│
├── Credentials
│   └── ?
│
├── Privileges
│   └── ?
│
├── Alarms
│   └── ?
│
└── Configuration
    └── ?
```

Les endpoints marqués `?` n'ont pas encore été identifiés.

---

# 🧪 État du projet

## Endpoints

| Fonction           | Endpoint           | État            |
| ------------------ | ------------------ | --------------- |
| Authentification   | `/authenticate`    | ✅ Identifié     |
| Credential Holders | `/credHolder/list` | ✅ Identifié     |
| Événements         | `/evt/list`        | ✅ Identifié     |
| Portes             | —                  | 🔎 À rechercher |
| Contrôleurs        | —                  | 🔎 À rechercher |
| Credentials        | —                  | 🔎 À rechercher |
| Privilèges         | —                  | 🔎 À rechercher |
| Alarmes            | —                  | 🔎 À rechercher |
| Configuration      | —                  | 🔎 À rechercher |

## Reverse engineering

* [x] Authentification
* [x] `sessionToken`
* [x] Récupération des credential holders
* [x] Récupération des credentials
* [x] Récupération des événements
* [x] Filtrage temporel des événements
* [x] Filtrage par catégories
* [x] Identification de plusieurs références d'objets
* [x] Identification de plusieurs codes d'événements
* [ ] Identifier l'identifiant unique d'un événement
* [ ] Documenter tous les champs de `/evt/list`
* [ ] Documenter tous les champs de `/credHolder/list`
* [ ] Identifier les autres endpoints
* [ ] Identifier la signification des catégories `0`, `1`, `2`, `3`
* [ ] Tester plusieurs modèles Atlas
* [ ] Tester plusieurs versions de firmware
* [ ] Mettre en place une synchronisation incrémentale

---

# 📁 Contenu du dépôt

```text
ZKTECO_ATLAS/
│
├── README.md
│
├── ATLAS_BADGES_UTILISATEURS.php
│   └── Extraction des utilisateurs / credentials
│
├── ATLAS_EVENEMENTS_UTILISATEURS.php
│   └── Extraction des événements
│
└── ATLAS_config.sql
    └── Configuration des codes d'événements
```

---



# 📜 Avertissement

Ce projet est **non officiel** et destiné à la recherche, à la documentation et à l'interopérabilité avec les contrôleurs ZKTeco Atlas.
Il n'est pas affilié à ZKTeco et ne constitue pas une documentation officielle de leurs produits.

Les structures et comportements documentés peuvent varier selon :

* le modèle du contrôleur ;
* la version du firmware ;
* la version de l'application Web ;
* la configuration du système.

Toute contribution permettant d'identifier un nouvel endpoint, un nouveau champ JSON, un code d'événement ou un comportement particulier est la bienvenue.
