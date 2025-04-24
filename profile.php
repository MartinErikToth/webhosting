<?php
session_start();

$conn = oci_connect('C##R6LBDN', 'C##R6LBDN',
    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SID=orania2)))', 'UTF8');


if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === 'guest') {
    header("Location: login.php");
    exit();
}

$id = $_SESSION['user_id'];


$query = "SELECT ID, FELHASZNALONEV, EMAIL, SZEREP, BE_VAN_JELENTKEZVE FROM FELHASZNALOK WHERE ID = :id";
$stid = oci_parse($conn, $query);
oci_bind_by_name($stid, ":id", $id);
oci_execute($stid);

$user = oci_fetch_assoc($stid);
oci_free_statement($stid);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uj_felhasznalonev = $_POST['felhasznalonev'];
    $uj_email = $_POST['email'];
    $uj_jelszo = $_POST['jelszo'];

    if (!empty($uj_felhasznalonev) && !empty($uj_email)) {
        $frissitesQuery = "UPDATE FELHASZNALOK SET FELHASZNALONEV = :fnev, EMAIL = :email";

        if (!empty($uj_jelszo)) {
            $hashelt_jelszo = password_hash($uj_jelszo, PASSWORD_DEFAULT);
            $frissitesQuery .= ", JELSZO = :jelszo";
        }

        $frissitesQuery .= " WHERE ID = :id";

        $stid = oci_parse($conn, $frissitesQuery);
        oci_bind_by_name($stid, ":fnev", $uj_felhasznalonev);
        oci_bind_by_name($stid, ":email", $uj_email);
        if (!empty($uj_jelszo)) {
            oci_bind_by_name($stid, ":jelszo", $hashelt_jelszo);
        }
        oci_bind_by_name($stid, ":id", $id);
        oci_execute($stid);
        oci_free_statement($stid);

        header("Location: profile.php");
        exit();
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
    </div>

    <div class="content">
        <h1>Profilom</h1>

        <div id="info" class="section">
            <div class="profile-card">
                <h3>Felhasználói Információk</h3>
                <p><strong>Felhasználónév:</strong> <?php echo htmlspecialchars($user['FELHASZNALONEV']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['EMAIL']); ?></p>
                <p><strong>Szerep:</strong> <?php echo htmlspecialchars($user['SZEREP']); ?></p>
                <p><strong>Be van jelentkezve:</strong> <?php echo $user['BE_VAN_JELENTKEZVE'] === 'Y' ? 'Igen' : 'Nem'; ?></p>
            </div>
        </div>

        <div id="settings" class="section" style="display: none;">
            <form method="post" class="profile-card">
                <h3>Beállítások módosítása</h3>
                <input type="text" name="felhasznalonev" placeholder="Új felhasználónév" value="<?php echo htmlspecialchars($user['FELHASZNALONEV']); ?>" required>
                <input type="email" name="email" placeholder="Új email" value="<?php echo htmlspecialchars($user['EMAIL']); ?>" required>
                <input type="password" name="jelszo" placeholder="Új jelszó (ha nem szeretnél változtatni, hagyd üresen)">
                <button type="submit">Adatok frissítése</button>
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
                <h3>Előfizetéseim</h3>
                <p><strong>Alap csomag:</strong> Havi 5.99 €</p>
                <p><strong>Prémium csomag:</strong> Havi 15.99 €</p>
            </div>
        </div>
    </div>
</div>
<footer>
    <p>&copy; 2025 WebHosting | Minden jog fenntartva</p>
</footer>
</body>
</html>


