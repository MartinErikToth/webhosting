<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['szerep'] ?? '') !== 'szerkeszto') {
    header('Location: index.php');
    exit;
}
$conn = oci_connect(
    'C##R6LBDN',
    'C##R6LBDN',
    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SID=orania2)))',
    'UTF8'
);
if (!$conn) {
    $e = oci_error();
    die('Kapcsolódási hiba: ' . $e['message']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csomagnev'])) {
    $csomagnev       = trim($_POST['csomagnev']);
    $csomagar        = trim($_POST['csomagar']);
    $szolgaltatasNev = trim($_POST['szolgaltatas_nev']);

    if ($csomagnev === '' || $csomagar === '' || $szolgaltatasNev === '') {
        $_SESSION['pkg_msg'] = 'Kérjük, tölts ki minden mezőt!';
    } else {
        
        $stid_pkg = oci_parse($conn, "INSERT INTO DIJCSOMAG (CSOMAGNEV, CSOMAG_AR) VALUES (:nev, :ar)");
        oci_bind_by_name($stid_pkg, ':nev', $csomagnev);
        oci_bind_by_name($stid_pkg, ':ar',  $csomagar);
        $sql_srv = "INSERT INTO WEB_SZOLGALTATAS (
                        SZOLGALTATAS_NEV, SZOLGALTATAS_TIPUS, SZOLGALTATAS_EGYSEGAR,
                        AKTIV_E, HASZNALAT_KEZDETE)
                    VALUES (:snev, :tipus, :ar, 'Y', SYSDATE)";
        $stid_srv = oci_parse($conn, $sql_srv);
        oci_bind_by_name($stid_srv, ':snev',  $szolgaltatasNev);
        oci_bind_by_name($stid_srv, ':tipus', $csomagnev);
        oci_bind_by_name($stid_srv, ':ar',    $csomagar);

        if (oci_execute($stid_pkg, OCI_NO_AUTO_COMMIT) && oci_execute($stid_srv, OCI_NO_AUTO_COMMIT)) {
            oci_commit($conn);
            $_SESSION['pkg_msg'] = 'Csomag és szolgáltatás sikeresen felvéve!';
        } else {
            oci_rollback($conn);
            $_SESSION['pkg_msg'] = 'Hiba történt a mentés során.';
        }
        oci_free_statement($stid_pkg);
        oci_free_statement($stid_srv);
    }
    header('Location: admin.php#csomag');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answer_submit'])) {
    $id     = (int)$_POST['bejegyzes_szama'];
    $valasz = trim($_POST['valasz']);

    if ($valasz === '') {
        $_SESSION['kb_msg'] = 'A válasz mező nem lehet üres!';
    } else {
        $stid_ans = oci_parse($conn, 'UPDATE BEJEGYZES SET VALASZ = :valasz WHERE BEJEGYZES_SZAMA = :id');
        oci_bind_by_name($stid_ans, ':valasz', $valasz);
        oci_bind_by_name($stid_ans, ':id', $id);

        if (oci_execute($stid_ans, OCI_NO_AUTO_COMMIT)) {
            oci_commit($conn);
            $_SESSION['kb_msg'] = 'Válasz sikeresen elmentve.';
        } else {
            $e = oci_error($stid_ans);
            oci_rollback($conn);
            $_SESSION['kb_msg'] = 'Mentési hiba: ' . $e['message'];
        }
        oci_free_statement($stid_ans);
    }
    header('Location: admin.php#tudastar');
    exit;
}

$stid_novalasz = oci_parse($conn, "SELECT BEJEGYZES_SZAMA, TIPUS, KERDES,
                                         TO_CHAR(MIKOR_KESZULT,'YYYY-MM-DD HH24:MI') AS KESZULT
                                  FROM BEJEGYZES
                                  WHERE VALASZ IS NULL OR VALASZ = 'Válaszra vár'
                                  ORDER BY MIKOR_KESZULT DESC");
oci_execute($stid_novalasz);

$stid_valaszolt = oci_parse($conn, "SELECT BEJEGYZES_SZAMA, TIPUS, KERDES, VALASZ,
                                           TO_CHAR(MIKOR_KESZULT,'YYYY-MM-DD HH24:MI') AS KESZULT
                                    FROM BEJEGYZES
                                    WHERE VALASZ IS NOT NULL AND VALASZ <> 'Válaszra vár'
                                    ORDER BY BEJEGYZES_SZAMA DESC");
oci_execute($stid_valaszolt);


?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin felület</title>
    <link rel="stylesheet" href="css/a.css">
</head>
<body>
<?php include 'header.php'; ?>

<nav>
    <a href="#csomag">Csomag felvitele</a>
    <a href="#tudastar">Tudástár kezelése</a>
</nav>

<main>
    <section id="csomag">
        <h2>Csomag felvitele</h2>
        <?php if (isset($_SESSION['pkg_msg'])): ?>
            <div class="flash <?php echo strpos($_SESSION['pkg_msg'], 'sikeresen') !== false ? 'ok' : 'err'; ?>">
                <?php echo $_SESSION['pkg_msg']; unset($_SESSION['pkg_msg']); ?>
            </div>
        <?php endif; ?>

        <form action="admin.php#csomag" method="post">
            <label>Csomag neve:<br>
                <input type="text" name="csomagnev" required>
            </label><br><br>
            <label>Csomag ára:<br>
                <input type="number" name="csomagar" required>
            </label><br><br>
            <label>Szolgáltatás neve:<br>
                <input type="text" name="szolgaltatas_nev" required>
            </label><br><br>
            <button type="submit">Mentés</button>
        </form>
    </section>

    <section id="tudastar">
        <h2>Tudástár – kérdések kezelése</h2>
        <?php if (isset($_SESSION['kb_msg'])): ?>
            <div class="flash <?php echo stripos($_SESSION['kb_msg'], 'hiba') !== false ? 'err' : 'ok'; ?>">
                <?php echo $_SESSION['kb_msg']; unset($_SESSION['kb_msg']); ?>
            </div>
        <?php endif; ?>

        <h3>Megválaszolatlan kérdések</h3>
        <?php if (oci_fetch_all($stid_novalasz, $tmp1, null, null, OCI_FETCHSTATEMENT_BY_ROW) === 0): ?>
            <p>Nincs megválaszolatlan kérdés.</p>
        <?php else: ?>
            <?php oci_execute($stid_novalasz); ?>
            <table class="datatable">
                <thead>
                    <tr><th>#</th><th>Típus</th><th>Kérdés</th><th>Felvétel ideje</th><th>Válasz</th></tr>
                </thead>
                <tbody>
                <?php while ($row = oci_fetch_assoc($stid_novalasz)): ?>
                    <tr>
                        <td><?php echo $row['BEJEGYZES_SZAMA']; ?></td>
                        <td><?php echo htmlspecialchars($row['TIPUS']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($row['KERDES'])); ?></td>
                        <td><?php echo $row['KESZULT']; ?></td>
                        <td>
                            <form method="post" action="admin.php#tudastar">
                                <input type="hidden" name="bejegyzes_szama" value="<?php echo $row['BEJEGYZES_SZAMA']; ?>">
                                <textarea name="valasz" required></textarea><br>
                                <button type="submit" name="answer_submit">Mentés</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h3>Megválaszolt kérdések</h3>
        <?php if (oci_fetch_all($stid_valaszolt, $tmp2, null, null, OCI_FETCHSTATEMENT_BY_ROW) === 0): ?>
            <p>Még nincs megválaszolt kérdés.</p>
        <?php else: ?>
            <?php oci_execute($stid_valaszolt); ?>
            <table class="datatable">
                <thead>
                    <tr><th>#</th><th>Típus</th><th>Kérdés</th><th>Felvétel ideje</th><th>Válasz</th></tr>
                </thead>
                <tbody>
                <?php while ($row = oci_fetch_assoc($stid_valaszolt)): ?>
                    <tr>
                        <td><?php echo $row['BEJEGYZES_SZAMA']; ?></td>
                        <td><?php echo htmlspecialchars($row['TIPUS']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($row['KERDES'])); ?></td>
                        <td><?php echo $row['KESZULT']; ?></td>
                        <td><?php echo nl2br(htmlspecialchars($row['VALASZ'])); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>

<?php include 'footer.php'; ?>
</body>
</html>
<?php
oci_free_statement($stid_novalasz);
oci_free_statement($stid_valaszolt);
oci_close($conn);
?>
