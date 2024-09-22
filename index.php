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

$sql = "SELECT * FROM element";
$result = $conn->query($sql);

$categories = [
    'PC' => [],
    'Routeur' => [],
    'Passerelles' => [],
    'Imprimantes' => [] 
];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ip = $row['ip'];
        $nom = $row['nom'];

        if (substr($ip, -2) == '.1' && strpos($nom, '_gateway') !== false) {
            $icon = 'routeur.png';
            $categorie = 'Routeur';
        } elseif (strpos($nom, 'DC-') === 0 || preg_match('/^LATECH[1-6]$/', $nom) || strpos(strtolower($nom), 'mac') !== false)
) {
            $icon = 'poste.png';  
            $categorie = 'PC';
        } elseif (strpos($nom, 'ET') !== false) {
            $icon = 'imprimante.png';  
            $categorie = 'Imprimantes';
        } else {
            $icon = 'unknown.png';  
            $categorie = 'PC'; 
        }

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

// ======= Emplacement pour le code  =======


$conn->close();
?>
