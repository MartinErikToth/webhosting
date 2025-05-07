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
        $del_st = oci_parse($conn, 'BEGIN torol_dijcsomag(:id); END;');
        oci_bind_by_name($del_st, ':id', $torlendo_id);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['szamlaszam'])) {
    $szamlaszam = $_POST['szamlaszam'];
    $csomagkod = $_POST['csomagkod'] ?? null;

    if (!empty($felhasznalo_id) && !empty($szamlaszam) && !empty($csomagkod)) {
        $stmt = oci_parse($conn, "BEGIN FELHASZNALO_VASAROL(:felhasznalo_id, :szamlaszam); END;");
        oci_bind_by_name($stmt, ":felhasznalo_id", $felhasznalo_id);
        oci_bind_by_name($stmt, ":szamlaszam", $szamlaszam);

        if (oci_execute($stmt)) {
            $dijcsomag_st = oci_parse($conn, "SELECT CSOMAGNEV FROM DIJCSOMAG WHERE CSOMAGKOD = :ck");
            oci_bind_by_name($dijcsomag_st, ":ck", $csomagkod);
            oci_execute($dijcsomag_st);
            $csomagnev = "";
            if ($row = oci_fetch_assoc($dijcsomag_st)) {
                $csomagnev = $row['CSOMAGNEV'];
            }
            oci_free_statement($dijcsomag_st);

            $adoszam_st = oci_parse($conn, "SELECT ADOSZAM FROM FELHASZNALOK WHERE ID = :fid");
            oci_bind_by_name($adoszam_st, ":fid", $felhasznalo_id);
            oci_execute($adoszam_st);
            $adoszam = "";
            if ($row = oci_fetch_assoc($adoszam_st)) {
                $adoszam = $row['ADOSZAM'];
            }
            oci_free_statement($adoszam_st);
            $id_query = oci_parse($conn, "SELECT NVL(MAX(ID), 0) AS MAX_ID FROM SZAMLAK");

            oci_execute($id_query);
            $id_row = oci_fetch_assoc($id_query);
            $uj_id = $id_row['MAX_ID'] + 1;
            oci_free_statement($id_query);
            var_dump($adoszam);
            $szamla_st = oci_parse($conn, "INSERT INTO SZAMLAK (ID, SZAMLASZAM, TERMEKMEGNEVEZES, VEVO_ADOSZAMA) 
                              VALUES (:id, :szamlaszam, :csomagnev, :adoszam)");
            oci_bind_by_name($szamla_st, ":id", $uj_id);
            oci_bind_by_name($szamla_st, ":szamlaszam", $szamlaszam);
            oci_bind_by_name($szamla_st, ":csomagnev", $csomagnev);
            oci_bind_by_name($szamla_st, ":adoszam", $adoszam);

            if (!oci_execute($szamla_st)) {
                $e = oci_error($szamla_st);
                $_SESSION['hiba'] = "Hiba a számlák beszúrásánál: " . $e['message'];
            } else {
                $_SESSION['siker'] = "Sikeres vásárlás és számla létrehozás!";
            }
            oci_free_statement($szamla_st);

            $_SESSION['siker'] = "Sikeres vásárlás!";
        } else {
            $e = oci_error($stmt);
            $_SESSION['hiba'] = "Hiba történt a vásárlás során: " . $e['message'];
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
            <td><?= htmlspecialchars($row['CSOMAGNEV']); ?></td>
            <td><?= number_format((float)$row['CSOMAG_AR'], 2, ',', ' ') ?> Ft</td>
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
