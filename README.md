# Archery Manager

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](http://www.gnu.org/licenses/agpl-3.0)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=dehy_archery-manager&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=dehy_archery-manager)
[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Fdehy%2Farchery-manager.svg?type=shield)](https://app.fossa.com/projects/git%2Bgithub.com%2Fdehy%2Farchery-manager?ref=badge_shield)

Un outil de gestion pour les clubs de tir à l'arc

- Gestion d'un roster d'archers
- Gestion de l'équipement
- Gestion des évènements : entraînements, compétitions, autre

Cet outil est réalisé pour [Les Archers de Bordeaux Guyenne](https://archersdebordeaux-guyenne.com).

## Exécution locale

### Docker

```shell
# Construit (si nécessaire) et démarre les conteneurs docker
make start
# Install les dépendances PHP et JavaScript
make deps
```

Les différents services sont ensuite accessibles sur :

- Application : http://localhost:8080
- Adminer : http://localhost:8081
- Mailcatcher : http://localhost:1080

### Accès à la bdd

Une interface web de gestion de la base de donnée MariaDB (adminer) est disponible sur http://localhost:8081

- Type : MySQL
- Serveur : `database`
- Identifiant : `symfony`
- Mot de passe : `ChangeMe`
- Base de donnée : `app`

### Envie de participer ?

Quel que soit votre profil (informaticien ou non), vous pouvez participer à l'élaboration de cet outil.
En développement bien sûr, mais également en faisant de précieux retours sur son utilisation, ses fonctionnalités,
son ergonomie, etc., en tant qu'entraîneuse ou entraîneur, ou comme archère ou archer !

N'hésitez pas à soit me contacter directement à [archery-manager@admds.net](mailto:archery-manager@admds.net), soit
si vous êtes familier avec GitHub, en créant des [issues](https://github.com/dehy/archery-manager/issues).