# ZKTECO_ATLAS

Projet de **reverse engineering, documentation et exploitation de l'API HTTP interne** des contrôleurs d'accès **ZKTeco TECO Atlas**.

> ⚠️ **Projet non officiel** — ZKTeco ne semble pas fournir d'API publique documentée pour les contrôleurs Atlas. Les endpoints présentés ici ont été identifiés par analyse et expérimentation sur un contrôleur Atlas.

L'objectif est de permettre l'extraction automatisée des données d'un contrôleur Atlas vers une base de données externe, notamment pour conserver l'historique des événements au-delà de la capacité de stockage du contrôleur.

---

## 🎯 Objectifs

Le projet a actuellement plusieurs objectifs :

* identifier et documenter les endpoints HTTP utilisés par l'Atlas ;
* authentifier un client auprès du contrôleur ;
* récupérer les utilisateurs et leurs badges ;
* récupérer les événements d'accès ;
* comprendre les structures JSON retournées par l'Atlas ;
* documenter les codes et sous-codes d'événements ;
* permettre une synchronisation régulière vers une base de données externe ;
* éviter la perte d'historique lorsque la capacité maximale de stockage des événements est atteinte.

### Capacité de stockage

Les contrôleurs Atlas peuvent stocker jusqu'à **10 000 transactions**. Une extraction régulière vers une base de données externe permet donc de conserver un historique beaucoup plus important.

---

# 🏢 Matériel concerné

Le développement a été réalisé principalement autour du :

* **Atlas 400**

La compatibilité avec les modèles suivants reste à confirmer :

* Atlas 100
* Atlas 200
* Atlas Bio 160
* Atlas Bio 260
* Atlas Bio 460

> Les modèles partageant la même application Web/API pourraient être compatibles, mais cela doit être vérifié sur chaque firmware et chaque gamme de contrôleur.

---

# 🔌 API découverte

L'Atlas expose une interface HTTP/HTTPS utilisée notamment par son interface Web.

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
├── Users / Credential Holders
│   └── /credHolder/list
│
└── Events
    └── /evt/list
```

---

# 🔐 Authentication

Endpoint :

```http
/authenticate
```

L'authentification permet d'obtenir un `sessionToken`.

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

Le mécanisme exact d'authentification et les paramètres utilisés sont documentés dans les scripts présents dans ce dépôt.

---

# 👤 Utilisateurs

Endpoint :

```http
/credHolder/list
```

Cet endpoint permet de récupérer la liste des utilisateurs / titulaires de credentials enregistrés dans l'Atlas.

## Structure identifiée

Les champs actuellement identifiés comprennent notamment :

```text
user
├── unid
├── first
├── last
├── idNum
├── creds
│   └── name
└── privBindings
```

### Exemple d'accès aux données en PHP

```php
$user['unid'];
$user['first'];
$user['last'];
$user['idNum'];
$user['creds'][0]['name'];
$user['privBindings'];
```

### Correspondance supposée

| Champ          | Description                          |
| -------------- | ------------------------------------ |
| `unid`         | Identifiant interne de l'utilisateur |
| `first`        | Prénom                               |
| `last`         | Nom                                  |
| `idNum`        | Numéro d'identification              |
| `creds`        | Credentials associés à l'utilisateur |
| `creds[].name` | Identifiant du badge / credential    |
| `privBindings` | Privilèges ou droits associés        |

> Cette documentation est basée sur les observations réalisées sur un contrôleur Atlas. Certains champs peuvent varier selon le modèle, le firmware ou la configuration.

---

# 🚪 Événements

Endpoint :

```http
/evt/list
```

L'endpoint permet de récupérer les événements enregistrés par le contrôleur.

L'API accepte notamment des paramètres permettant de limiter les événements selon une période temporelle.

## Filtrage temporel

Les paramètres identifiés sont :

```text
hwTimeRestriction
│
├── beforeDate_year
├── beforeDate_month
├── beforeDate_day
└── beforeDate_hour

hwTimeRestriction
│
├── afterDate_year
├── afterDate_month
├── afterDate_day
└── afterDate_hour
```

Cela permet notamment de rechercher les événements compris dans une période donnée :

```text
afterDate
    ↓
événements Atlas
    ↓
beforeDate
```

Cette fonctionnalité est particulièrement intéressante pour mettre en place une synchronisation incrémentale.

---

# 🔄 Synchronisation

L'objectif à terme est de ne pas télécharger l'intégralité des transactions à chaque exécution.

Une synchronisation pourrait fonctionner ainsi :

```text
┌────────────────────┐
│ Base de données    │
│ externe            │
│                    │
│ dernier événement  │
└─────────┬──────────┘
          │
          │ timestamp
          ▼
┌────────────────────┐
│ ZKTeco Atlas       │
│                    │
│ /evt/list          │
│                    │
│ afterDate = last   │
└─────────┬──────────┘
          │
          │ nouveaux événements
          ▼
┌────────────────────┐
│ Base de données    │
│ externe            │
└────────────────────┘
```

Cette méthode permettrait notamment de limiter :

* le volume des requêtes ;
* le temps de synchronisation ;
* le risque de perdre les transactions lorsque la capacité du contrôleur est atteinte.

---

# 📊 Codes d'événements

Une partie du travail consiste également à identifier les codes et sous-codes retournés par l'Atlas.

Exemples actuellement identifiés :

| Code    | Signification              |
| ------- | -------------------------- |
| `48.0`  | Accès autorisé             |
| `49.0`  | Accès refusé               |
| `49.1`  | Carte expirée              |
| `49.2`  | Badge désactivé            |
| `49.3`  | Tentative d'usurpation     |
| `49.11` | En dehors de l'horaire     |
| `49.12` | Verrouillage manuel        |
| `49.13` | Limite d'entrée dépassée   |
| `49.14` | Numéro de carte inconnu    |
| `50.0`  | Porte verrouillée          |
| `51.0`  | Alarme active              |
| `52.0`  | Alarme activée             |
| `53.0`  | Alarme désactivée          |
| `54.0`  | Connexion au portail Web   |
| `55.0`  | Déconnexion du portail Web |
| `56.0`  | Carte non valide           |
| `128.0` | Horaire inactif            |

> La liste des codes n'est pas encore considérée comme exhaustive. Les significations sont issues des observations réalisées sur le système Atlas et peuvent nécessiter une validation sur différents firmwares.

---

# 🧪 État actuel du projet

### Endpoints

| Fonction         | Endpoint           | État            |
| ---------------- | ------------------ | --------------- |
| Authentification | `/authenticate`    | ✅ Identifié     |
| Utilisateurs     | `/credHolder/list` | ✅ Identifié     |
| Événements       | `/evt/list`        | ✅ Identifié     |
| Portes           | —                  | 🔎 À rechercher |
| Contrôleurs      | —                  | 🔎 À rechercher |
| Credentials      | —                  | 🔎 À rechercher |
| Privilèges       | —                  | 🔎 À rechercher |
| Alarmes          | —                  | 🔎 À rechercher |
| Configuration    | —                  | 🔎 À rechercher |

### Données

* [x] Authentification
* [x] Récupération des utilisateurs
* [x] Récupération des badges
* [x] Récupération des événements
* [x] Filtrage des événements par date
* [x] Première documentation des codes d'événements
* [ ] Identifier l'identifiant unique des événements
* [ ] Documenter l'ensemble des champs JSON
* [ ] Identifier les endpoints supplémentaires
* [ ] Synchronisation incrémentale robuste
* [ ] Support de plusieurs modèles Atlas
* [ ] Documentation des différences entre firmwares

---

# 📁 Contenu du dépôt

```text
ZKTECO_ATLAS/
│
├── README.md
│
├── ATLAS_BADGES_UTILISATEURS.php
│   └── Extraction des utilisateurs et badges
│
├── ATLAS_EVENEMENTS_UTILISATEURS.php
│   └── Extraction des événements
│
└── ATLAS_config.sql
    └── Configuration / correspondance des codes d'événements
```

---

# 🛠️ Utilisation

Les scripts PHP permettent actuellement d'interroger directement le contrôleur Atlas.

Configuration typique :

```text
Adresse IP Atlas
      │
      ▼
┌──────────────────┐
│ Script PHP       │
│                  │
│ /authenticate    │
│ /credHolder/list │
│ /evt/list        │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Base de données  │
│ externe          │
└──────────────────┘
```

> Les paramètres de connexion et identifiants doivent être configurés localement et ne doivent pas être stockés dans le dépôt.

---

# ⚠️ Sécurité

Ce projet interagit directement avec un système de contrôle d'accès.

Il est recommandé de :

* ne jamais publier les identifiants Atlas ;
* ne pas exposer l'interface Web de l'Atlas directement sur Internet ;
* utiliser un réseau interne ou un VPN ;
* protéger les identifiants utilisés par les scripts ;
* utiliser HTTPS lorsque celui-ci est correctement configuré ;
* éviter de désactiver la vérification TLS dans un environnement de production ;
* limiter les droits du compte utilisé pour l'extraction lorsque cela est possible.

---

# 🔬 Reverse engineering

Ce projet a pour objectif de documenter une API non officiellement documentée.

Les informations présentes dans ce dépôt sont issues de l'observation du comportement du système Atlas et peuvent évoluer selon :

* le modèle du contrôleur ;
* la version du firmware ;
* la version de l'application Web ;
* la configuration du système.

Toute nouvelle information permettant d'identifier un endpoint, un champ JSON, un code événement ou un comportement particulier est la bienvenue.

---

# 🚧 Prochaines étapes

Les prochaines recherches devraient notamment porter sur :

```text
API Atlas
│
├── Authentication
│   └── /authenticate
│
├── Users
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

L'objectif final serait de disposer d'une documentation suffisamment complète pour permettre la création d'une véritable bibliothèque ou API permettant d'interagir avec les contrôleurs **ZKTeco Atlas**.

---

## 📜 Licence

Projet communautaire / expérimental.

**ZKTeco** et **Atlas** sont des marques et produits de leurs propriétaires respectifs.

Ce projet n'est pas affilié, approuvé ou officiellement supporté par ZKTeco.
