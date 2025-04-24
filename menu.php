<?php
session_start();

$conn = oci_connect('C##R6LBDN', 'C##R6LBDN',
                    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SID=orania2)))',
                    'UTF8');

$felhasznalonev = "Vendég";

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== 'guest') {
    $id = $_SESSION['user_id'];

    $query = "SELECT FELHASZNALONEV FROM FELHASZNALOK WHERE ID = :id";
    $stid = oci_parse($conn, $query);
    oci_bind_by_name($stid, ":id", $id);
    oci_execute($stid);

    if ($row = oci_fetch_assoc($stid)) {
        $felhasznalonev = $row['FELHASZNALONEV'];
    }

    oci_free_statement($stid);
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Menü</title>
    <link rel="stylesheet" href="css/menu.css">
</head>
<body>

<!-- Oldalsó menü -->
<div class="side-menu">
    <a href="index.php">Főoldal</a>
    <a href="buying.php">Vásárlás</a>
    <a href="my_recipt.php">Saját nyugták</a>
    <a href="statistic.php">Statisztika</a>
    <a href="profile.php">Profilom</a>
    <a href="login.php">Kijelentkezés</a>
</div>

<!-- Tartalom -->
<div class="content">
    <div class="header">
        <h1>Főmenü</h1>
        <p class="welcome">Bejelentkezve: <strong><?php echo htmlspecialchars($felhasznalonev); ?></strong></p>
    </div>

    <!-- Itt jöhet a többi tartalom, ha szükséges -->
</div>

</body>
</html>
