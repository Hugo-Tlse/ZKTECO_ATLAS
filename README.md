# ZKTECO_ATLAS
Projet pour interfacer ou documenter le système ZKTeco TECO Atlas 400 pour une "API" (il n'en existe pas officiellement)

Contrôleurs d’accès Atlas-100/200/400 -- Les Atlas Bio-160/260/460 devraient fonctionner également.
Atlas peut stocker jusqu'à 10 000 transactions seulement ; objectif de les exporter régulièrement vers une base de données.


Endpoints découverts : 
/authenticate
/credHolder/list
/evt/list

Arbre Analytique
 │
 ├── Authentication
 │     └── /authenticate
 ├── Users
 │     └── /credHolder/list
 └── Events
       └── /evt/list

Structure utilisateur : 
$user['unid']
$user['creds'][0]['name']
$user['first']
$user['last']
$user['idNum']
$user['privBindings']

Structure évènements :
evtCategoryRestriction.evtCategories
hwTimeRestriction.beforeDate_year
hwTimeRestriction.beforeDate_month
hwTimeRestriction.beforeDate_day
hwTimeRestriction.beforeDate_hour
hwTimeRestriction.afterDate_year
hwTimeRestriction.afterDate_month
hwTimeRestriction.afterDate_day
hwTimeRestriction.afterDate_hour
