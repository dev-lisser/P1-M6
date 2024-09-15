<?php

// Connexion à la base de données
$servername = "localhost";
$username = "superviseur";
$password = "azerty";
$dbname = "RESEAU_LISSER";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Récupération des données depuis la base de données
$sql = "SELECT * FROM element";
$result = $conn->query($sql);

// Catégories pour les affichages
$categories = [
    'PC' => [],
    'Routeur' => [],
    'Passerelles' => [],
    'Imprimantes' => [] // Ajout de la catégorie imprimantes
];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ip = $row['ip'];
        $nom = $row['nom'];

        // Détermine la catégorie et l'image basée sur le nom du périphérique et l'adresse IP
        if (substr($ip, -2) == '.1' && strpos($nom, '_gateway') !== false) {
            $icon = 'routeur.png';
            $categorie = 'Routeur';
        } elseif (strpos($nom, 'DC-') === 0 || preg_match('/^LATECH[1-6]$/', $nom)) {
            $icon = 'poste.png';  // Affiche poste.png pour les noms spécifiques
            $categorie = 'PC';
        } elseif (strpos($nom, 'EPSON') !== false) {
            $icon = 'imprimante.png';  // Affiche imprimante.png pour les imprimantes EPSON
            $categorie = 'Imprimantes';
        } else {
            $icon = 'unknown.png';  // Utilise une image par défaut
            $categorie = 'PC'; // Par défaut on met dans PC pour les inconnus
        }

        // Détermine si c'est une passerelle
        if (substr($ip, -4) == '.1' && $categorie !== 'Routeur') {
            $categorie = 'Passerelles';
        }

        $categories[$categorie][] = [
            'nom' => $nom,
            'ip' => $ip,
            'mac' => $row['mac'],
            'icon' => $icon
        ];
    }
}

// ======= Emplacement pour le code des élèves =======
// Les élèves peuvent insérer ici leur vue (HTML, CSS, etc.) pour afficher les données récupérées dans $categories.

$conn->close();
?>
