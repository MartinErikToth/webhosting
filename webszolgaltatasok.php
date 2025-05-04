<?php
session_start();
$conn = oci_connect('C##R6LBDN', 'C##R6LBDN',
    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SID=orania2)))', 'UTF8');

$felhasznalo_id = $_SESSION['user_id'] ?? null;
$uzenet_siker = $_SESSION['siker'] ?? "";
$uzenet_hiba  = $_SESSION['hiba'] ?? "";
unset($_SESSION['siker'], $_SESSION['hiba']);
$szerep = isset($_SESSION['szerep']) ? strtolower(trim($_SESSION['szerep'])) : null;       

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['torol_csomagkod'])) {

    if ($szerep === 'szerkeszto') {

        $torlendo_id = (int)$_POST['torol_csomagkod'];
        $del_sql = "DELETE FROM DIJCSOMAG WHERE CSOMAGKOD = :id";
        $del_st = oci_parse($conn, $del_sql);
        oci_bind_by_name($del_st, ":id", $torlendo_id);

        if (oci_execute($del_st)) {
            $uzenet_siker = "Csomag sikeresen törölve!";
        } else {
            $uzenet_hiba  = "Hiba történt a törlés során!";
        }
        oci_free_statement($del_st);

    } else {
        
        $uzenet_hiba = "Nincs jogosultság a művelethez!";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csomagkod'], $_POST['szamlaszam'])) {
    $csomagkod = $_POST['csomagkod'];
    $szamlaszam = $_POST['szamlaszam'];

    if ($felhasznalo_id && !empty($szamlaszam)) {
        $vasarlas_sql = "INSERT INTO VASARLAS (FELHASZNALO_ID, SZAMLASZAM) VALUES (:felhasznalo_id, :szamlaszam)";
        $stmt = oci_parse($conn, $vasarlas_sql);
        oci_bind_by_name($stmt, ":felhasznalo_id", $felhasznalo_id);
        oci_bind_by_name($stmt, ":szamlaszam", $szamlaszam);

        if (oci_execute($stmt)) {
            $_SESSION['siker'] = "Sikeres vásárlás!";
        } else {
            $_SESSION['hiba'] = "Hiba történt a vásárlás során!";
        }

        oci_free_statement($stmt);

        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $_SESSION['hiba'] = "Hiányzó adatok!";
    }
}
$list_sql = "SELECT * FROM DIJCSOMAG ORDER BY CSOMAG_AR";
$list_st  = oci_parse($conn, $list_sql);
oci_execute($list_st);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/webszolg.css">
    <title>Díjcsomagok</title>
</head>
<body>
<?php include 'header.php'; ?>

<h2 style="text-align:center;">Elérhető díjcsomagok</h2>

<?php if ($uzenet_siker): ?>
    <div class="message"><?= $uzenet_siker; ?></div>
<?php endif; ?>
<?php if ($uzenet_hiba): ?>
    <div class="error"><?= $uzenet_hiba; ?></div>
<?php endif; ?>

<table>
    <tr>
        <th>Díjcsomag neve</th>
        <th>Ár</th>
        <th>Vásárlás</th>
        <?php if ($szerep === 'szerkeszto'): ?>
            <th>Művelet</th>
        <?php endif; ?>
    </tr>

    <?php while ($row = oci_fetch_assoc($list_st)): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['CSOMAGNEV']); ?></td>
            <td><?php echo number_format((float)$row['CSOMAG_AR'], 2, ',', ' '); ?> Ft</td>
            <td>
                <form method="post">
                    <input type="hidden" name="csomagkod" value="<?= $row['CSOMAGKOD']; ?>">
                    <input type="text"   name="szamlaszam" placeholder="Számlaszám" required>
                    <button type="submit">Megvásárol</button>
                </form>
            </td>

            <?php if ($szerep === 'szerkeszto'): ?>
                <td>
                    <form method="post"
                          onsubmit="return confirm('Biztosan törlöd a »<?= htmlspecialchars($row['CSOMAGNEV']); ?>« csomagot?');">
                        <input type="hidden" name="torol_csomagkod" value="<?= $row['CSOMAGKOD']; ?>">
                        <button type="submit" class="danger">Törlés</button>
                    </form>
                </td>
            <?php endif; ?>
        </tr>
    <?php endwhile; ?>
</table>
<?php include 'footer.php'; ?>
</body>
</html>
<?php
oci_free_statement($list_st);
oci_close($conn);
?>
