<?php
/* Gép: 
$conn = oci_connect('C##R6LBDN', 'C##R6LBDN',
    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SID=orania2)))', 'UTF8');
*/

/* Laptop: */
$conn = oci_connect('C##R6LBDN', 'C##R6LBDN',
    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=11521))(CONNECT_DATA=(SID=orania2)))', 'UTF8');


$felhasznalo_id = $_SESSION['user_id'] ?? null;
$hiba = "";
$siker = "";


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

$sql = "SELECT * FROM DIJCSOMAG";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

if (isset($_SESSION['siker'])) {
    $siker = $_SESSION['siker'];
    unset($_SESSION['siker']);
}

if (isset($_SESSION['hiba'])) {
    $hiba = $_SESSION['hiba'];
    unset($_SESSION['hiba']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/webszolg.css">
    <title>Webszolgáltatások</title>
</head>

<body>
<?php include 'header.php'; ?>
<h2 style="text-align:center;">Elérhető díjcsomagok</h2>

<?php if ($siker): ?>
    <div class="message"><?php echo $siker; ?></div>
<?php endif; ?>
<?php if ($hiba): ?>
    <div class="error"><?php echo $hiba; ?></div>
<?php endif; ?>

<table>
    <tr>
        <th>Díjcsomag neve</th>
        <th>Ár</th>
        <th>Vásárlás</th>
    </tr>

    <?php while ($row = oci_fetch_assoc($stmt)): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['CSOMAGNEV']); ?></td>
            <td><?php echo number_format((float)$row['CSOMAG_AR'], 2, ',', ' '); ?> Ft</td>
            <td>
                <form method="post">
                    <input type="hidden" name="csomagkod" value="<?php echo $row['CSOMAGKOD']; ?>">
                    <input type="text" name="szamlaszam" placeholder="Számlaszám" required>
                    <button type="submit">Megvásárol</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>

</table>

</body>
<?php include 'footer.php'; ?>
</html>

<?php
oci_free_statement($stmt);
oci_close($conn);
?>
