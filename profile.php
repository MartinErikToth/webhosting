<?php
session_start();

$conn = oci_connect('C##R6LBDN', 'C##R6LBDN',
    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SID=orania2)))', 'UTF8');

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === 'guest') {
    header("Location: login.php");
    exit();
}

$id = $_SESSION['user_id'];


$query = "SELECT ID, FELHASZNALONEV, EMAIL, SZEREP, BE_VAN_JELENTKEZVE, SZAMLASZAM, ADOSZAM 
          FROM FELHASZNALOK WHERE ID = :id";
$stid = oci_parse($conn, $query);
oci_bind_by_name($stid, ":id", $id);
oci_execute($stid);

$user = oci_fetch_assoc($stid);

$vevo_neve = '';
if (!empty($user['ADOSZAM'])) {
    $vevoQuery = "SELECT VEVO_NEVE FROM VEVO WHERE VEVO_ADOSZAMA = :adoszam";
    $stid = oci_parse($conn, $vevoQuery);
    oci_bind_by_name($stid, ":adoszam", $user['ADOSZAM']);
    oci_execute($stid);

    $vevoData = oci_fetch_assoc($stid);
    $vevo_neve = $vevoData['VEVO_NEVE'] ?? '';  
    oci_free_statement($stid);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['szamlazas_submit'])) {
    $szamlaszam = $_POST['szamlaszam'];
    $adoszam = $_POST['adoszam'];

    $getLastIdQuery = "SELECT MAX(ID) AS MAX_ID FROM SZAMLAK";
    $stid = oci_parse($conn, $getLastIdQuery);
    oci_execute($stid);
    $row = oci_fetch_assoc($stid);
    $lastId = $row['MAX_ID'];
    oci_free_statement($stid);

    $newId = $lastId + 1;


    $insertSzamla = "INSERT INTO SZAMLAK (ID, SZAMLASZAM, TERMEKMEGNEVEZES, VEVO_ADOSZAMA)
                     VALUES (:id, :szamlaszam, 'Alap Web Hosting csomag', :adoszam)";
    $insStid = oci_parse($conn, $insertSzamla);
    oci_bind_by_name($insStid, ":id", $newId);
    oci_bind_by_name($insStid, ":szamlaszam", $szamlaszam);
    oci_bind_by_name($insStid, ":adoszam", $adoszam);
    oci_execute($insStid);
    oci_free_statement($insStid);

    $updateQuery = "UPDATE FELHASZNALOK SET SZAMLASZAM = :szamlaszam, ADOSZAM = :adoszam WHERE ID = :id";
    $stid = oci_parse($conn, $updateQuery);
    oci_bind_by_name($stid, ":szamlaszam", $szamlaszam);
    oci_bind_by_name($stid, ":adoszam", $adoszam);
    oci_bind_by_name($stid, ":id", $id);
    oci_execute($stid);
    oci_free_statement($stid);

    header("Location: profile.php"); 
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_profile'])) {
    $deleteQuery = "DELETE FROM FELHASZNALOK WHERE ID = :id";
    $stid = oci_parse($conn, $deleteQuery);
    oci_bind_by_name($stid, ":id", $id);
    oci_execute($stid);
    oci_free_statement($stid);

    session_destroy();
    header("Location: login.php");
    exit();
}

$vevo_adoszama = '';
$vevo_neve = '';
$vevo_cime = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (isset($_POST['vevo_adoszama'])) {
        $vevo_adoszama = $_POST['vevo_adoszama'];
    }
    if (isset($_POST['vevo_neve'])) {
        $vevo_neve = $_POST['vevo_neve'];
    }
    if (isset($_POST['vevo_cime'])) {
        $vevo_cime = $_POST['vevo_cime'];
    }

    if (empty($vevo_adoszama) || empty($vevo_neve) || empty($vevo_cime)) {
        $error_message = "Minden mezőt ki kell tölteni!";
    } else {
        $checkVevo = "SELECT COUNT(*) FROM VEVO WHERE VEVO_ADOSZAMA = :vevo_adoszama";
        $stid = oci_parse($conn, $checkVevo);
        oci_bind_by_name($stid, ":vevo_adoszama", $vevo_adoszama);
        oci_execute($stid);

        $row = oci_fetch_assoc($stid);
        oci_free_statement($stid);

        if ($row['COUNT(*)'] == 0) {
            $insertVevo = "INSERT INTO VEVO (VEVO_ADOSZAMA, VEVO_CIME, VEVO_NEVE) 
                           VALUES (:vevo_adoszama, :vevo_cime, :vevo_neve)";
            $stid = oci_parse($conn, $insertVevo);
            oci_bind_by_name($stid, ":vevo_adoszama", $vevo_adoszama);
            oci_bind_by_name($stid, ":vevo_cime", $vevo_cime);
            oci_bind_by_name($stid, ":vevo_neve", $vevo_neve);      
            oci_execute($stid);
            oci_free_statement($stid);
        }
    }
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Profilom</title>
    <link rel="stylesheet" href="css/profil.css">
    <script src="js/profile.js"></script>
</head>
<body>
<?php include 'header.php'; ?>
<div class="container">
    <div class="sidebar">
        <div class="profile-header">
            <img src="assets/images/profilePic.jpg" alt="Profilkép" class="profile-picture">
            <h2 class="username"><?php echo htmlspecialchars($user['FELHASZNALONEV']); ?></h2>
        </div>
        <button onclick="showSection('info')">Profil adatok</button>
        <button onclick="showSection('settings')">Beállítások</button>
        <button onclick="showSection('orders')">Rendeléseim</button>
        <button onclick="showSection('subscriptions')">Előfizetések</button>
        <button onclick="showSection('billing')">Számlázás</button> 
        <button onclick="showSection('vevos')">Vevői adatok</button> 
    </div>

    <div class="content">
        <h1>Profilom</h1>

        <div id="info" class="section">
            <div class="profile-card">
                <h3>Felhasználói Információk</h3>
                <p><strong>Vevő neve:</strong> <?php echo htmlspecialchars($vevo_neve); ?></p> 
                <p><strong>Felhasználónév:</strong> <?php echo htmlspecialchars($user['FELHASZNALONEV']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['EMAIL']); ?></p>
                <p><strong>Szerep:</strong> <?php echo htmlspecialchars($user['SZEREP']); ?></p>
                <p><strong>Be van jelentkezve:</strong> <?php echo $user['BE_VAN_JELENTKEZVE'] === 'Y' ? 'Igen' : 'Nem'; ?></p>
                <p><strong>Számlaszám:</strong> <?php echo htmlspecialchars($user['SZAMLASZAM'] ?? 'Nincs számlaszám'); ?></p>
                <p><strong>Adószám:</strong> <?php echo htmlspecialchars($user['ADOSZAM'] ?? 'Nincs adószám'); ?></p>
            </div>
        </div>

        <div id="billing" class="section" style="display: none;">
            <form method="post" class="profile-card">
                <h3>Számlázási adatok megadása</h3>
                <input type="text" name="szamlaszam" placeholder="Számlaszám" required>
                <input type="text" name="adoszam" placeholder="Adószám" required>
                <button type="submit" name="szamlazas_submit">Adatok mentése</button>
            </form>
        </div>

        <div id="vevos" class="section" style="display: none;">
            <form method="post" class="profile-card">
                <h3>Vevői adatok megadása</h3>
                <input type="text" id="vevo_adoszama" placeholder="Adószám"name="vevo_adoszama" value="<?php echo htmlspecialchars($vevo_adoszama); ?>" required>
                <input type="text" id="vevo_neve" name="vevo_neve" placeholder="Minta Név" value="<?php echo htmlspecialchars($vevo_neve); ?>" required>
                <input type="text" id="vevo_cime" name="vevo_cime" placeholder="Cím" value="<?php echo htmlspecialchars($vevo_cime); ?>" required>
                <button type="submit" name="submit">Adatok mentése</button>
            </form>
        </div>

        <div id="settings" class="section" style="display: none;">
            <form method="post" class="profile-card">
                <h3>Beállítások módosítása</h3>
                <input type="text" name="felhasznalonev" placeholder="Új felhasználónév" value="<?php echo htmlspecialchars($user['FELHASZNALONEV']); ?>" required>
                <input type="email" name="email" placeholder="Új email" value="<?php echo htmlspecialchars($user['EMAIL']); ?>" required>
                <input type="password" name="jelszo" placeholder="Új jelszó (ha nem szeretnél változtatni, hagyd üresen)">
                <button type="submit">Adatok frissítése</button>
            </form>

            <form method="post" onsubmit="return confirm('Biztosan törölni szeretnéd a profilodat?');">
                <button type="submit" name="delete_profile" class="delete-button">Profil törlése</button>
            </form>
        </div>

        <div id="orders" class="section" style="display: none;">
            <div class="profile-card">
                <h3>Rendeléseim</h3>
                <p><strong>Rendelés ID:</strong> #12345</p>
                <p><strong>Termék neve:</strong> Web Hosting csomag</p>
                <p><strong>Dátum:</strong> 2025-04-22</p>
                <p><strong>Ár:</strong> 9.99 €</p>
            </div>
        </div>

        <div id="subscriptions" class="section" style="display: none;">
            <div class="profile-card">
                <h3>Előfizetések</h3>
                <p><strong>Alap csomag:</strong> Havi 5.99 €</p>
                <p><strong>Prémium csomag:</strong> Havi 9.99 €</p>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>
