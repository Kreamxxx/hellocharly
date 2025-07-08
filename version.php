<?php
// version.php – Déclaration du plugin Hello Charly
defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2024070800;            // ✅ Format : YYYYMMDDXX (XX = incrément de build dans la journée)
$plugin->requires  = 2022112800;            // Moodle 4.1 minimum
$plugin->component = 'block_hellocharly';   // Nom complet du composant (block_[nom])
$plugin->maturity  = MATURITY_STABLE;       // Peut aussi être MATURITY_ALPHA ou BETA selon le stade du dev
$plugin->release   = '1.0.0';               // Version fonctionnelle visible
$plugin->dependencies = [];                 // Aucune dépendance