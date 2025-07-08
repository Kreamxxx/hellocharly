# Plugin Moodle - Block Hello Charly

Ce plugin permet aux utilisateurs Moodle d'accéder à Hello Charly directement depuis leur tableau de bord via une authentification SSO.

## Installation

1. Copier le dossier `hellocharly` dans le répertoire `blocks/` de votre installation Moodle
2. Se connecter en tant qu'administrateur
3. Aller dans Administration du site > Notifications pour installer le plugin
4. Configurer la clé API dans le fichier `config.php`

## Configuration

Ajouter dans votre fichier `config.php` :

```php
$CFG->hellocharly_api_key = 'votre_cle_api_hello_charly';
```

## Utilisation

1. Aller dans le tableau de bord utilisateur
2. Ajouter le bloc "Hello Charly" 
3. Cliquer sur "Accéder à Hello Charly"
4. L'utilisateur sera automatiquement connecté et redirigé vers Hello Charly

## Sécurité

- La clé API est stockée côté serveur uniquement
- Les appels sont sécurisés par sesskey et capabilities
- Vérification des permissions utilisateur

## Support

Pour toute question ou problème, contactez l'équipe de développement.