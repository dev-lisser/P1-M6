<?php
// ==========================
// rso.php
// ==========================

// --- Connexion base de données ---
$servername = "localhost";
$username   = "NOM_USER_MARIADB";
$password   = "MDP_USER";
$dbname     = "NOM_BASE_DONNES";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}
$conn->set_charset("utf8");

// --- Requête ---
$sql = "SELECT nom, ip, mac FROM element ORDER BY ip";
$result = $conn->query($sql);

$machines = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $machines[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Topologie Réseau</title>
  <link rel="stylesheet" href="style/style.css">
</head>
<body>
  <h1>Topologie du réseau</h1>

  <?php
  // =======================================================
  // === ICI les étudiants collent leur propre code HTML ===
  // =======================================================
  ?>

  <section>
    <h2>Ordinateurs</h2>
    <div class="grid">
      <?php foreach ($machines as $m): ?>
        <?php if (!in_array($m['nom'], ["pve.tech945.local", "latech945_local.home"]) 
                  && stripos($m['nom'], "canon") === false 
                  && stripos($m['nom'], "_gateway") === false): ?>
          <div class="item">
            <img src="images/pc.png" alt="PC">
            <p><?= htmlspecialchars($m['nom']) ?><br>
               <span><?= htmlspecialchars($m['ip']) ?></span></p>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </section>

  <section>
    <h2>Serveurs</h2>
    <div class="grid">
      <?php foreach ($machines as $m): ?>
        <?php if ($m['nom'] === "pve.tech945.local" || $m['nom'] === "latech945_local.home"): ?>
          <div class="item">
            <img src="images/serveur.png" alt="Serveur">
            <p><?= htmlspecialchars($m['nom']) ?><br>
               <span><?= htmlspecialchars($m['ip']) ?></span></p>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </section>

  <section>
    <h2>Imprimantes</h2>
    <div class="grid">
      <?php foreach ($machines as $m): ?>
        <?php if (stripos($m['nom'], "canon") !== false): ?>
          <div class="item">
            <img src="images/imp.png" alt="Imprimante">
            <p><?= htmlspecialchars($m['nom']) ?><br>
               <span><?= htmlspecialchars($m['ip']) ?></span></p>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </section>

  <section>
    <h2>Routeur</h2>
    <div class="grid">
      <?php foreach ($machines as $m): ?>
        <?php if ($m['nom'] === "_gateway"): ?>
          <div class="item">
            <img src="images/routeur.png" alt="Routeur">
            <p><?= htmlspecialchars($m['nom']) ?><br>
               <span><?= htmlspecialchars($m['ip']) ?></span></p>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </section>

</body>
</html>
