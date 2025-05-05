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
        $stid_ans = oci_parse($conn, 'BEGIN update_valasz(:id, :valasz); END;');
        oci_bind_by_name($stid_ans, ':id', $id);
        oci_bind_by_name($stid_ans, ':valasz', $valasz);

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

$stid_top = oci_parse($conn, "
    SELECT 
        f.ID AS FELHASZNALO_ID,
        f.FELHASZNALONEV,
        f.EMAIL,
        COUNT(v.VASARLAS_AZON) AS VASARLASOK_SZAMA
    FROM 
        C##R6LBDN.FELHASZNALOK f
    JOIN 
        C##R6LBDN.VASARLAS v ON f.ID = v.FELHASZNALO_ID
    GROUP BY 
        f.ID, f.FELHASZNALONEV, f.EMAIL
    ORDER BY 
        VASARLASOK_SZAMA DESC
    FETCH FIRST 1 ROWS ONLY
");
oci_execute($stid_top);

$stid_user = "
SELECT f.ID, f.FELHASZNALONEV, COUNT(bn.NAPLO_ID) AS bejelentkezesek_szama
FROM FELHASZNALOK f
JOIN BEJELENTKEZES_NAPLO bn ON f.ID = bn.FELHASZNALO_ID
GROUP BY f.ID, f.FELHASZNALONEV
HAVING COUNT(bn.NAPLO_ID) = (
    SELECT MAX(bejelentkezesek_db)
    FROM (
        SELECT COUNT(*) AS bejelentkezesek_db
        FROM BEJELENTKEZES_NAPLO
        GROUP BY FELHASZNALO_ID
    )
)
";
$stid = oci_parse($conn, $stid_user);
oci_execute($stid);

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

$sql5 = "
    SELECT TO_CHAR(VASARLAS.VASARLAS_IDOPONT, 'YYYY-MM') AS honap, 
           SUM(DIJCSOMAG.CSOMAG_AR) AS havi_bevetel
    FROM VASARLAS
    JOIN FELHASZNALOK ON VASARLAS.FELHASZNALO_ID = FELHASZNALOK.ID
    JOIN SZAMLAK ON VASARLAS.SZAMLASZAM = SZAMLAK.SZAMLASZAM
    JOIN DIJCSOMAG ON SZAMLAK.TERMEKMEGNEVEZES = DIJCSOMAG.CSOMAGNEV
    GROUP BY TO_CHAR(VASARLAS.VASARLAS_IDOPONT, 'YYYY-MM')
    ORDER BY honap
";
$stid2 = oci_parse($conn, $sql5);
if (!oci_execute($stid2)) {
    $e = oci_error($stid2);
    die("Hiba a havi bevételek lekérdezésekor: " . htmlspecialchars($e['message']));
}

$sql_havi_csomag = "
    SELECT  TO_CHAR(v.VASARLAS_IDOPONT,'YYYY-MM') AS HONAP,
            d.CSOMAGNEV,
            SUM(d.CSOMAG_AR)                AS BEVETEL
    FROM    VASARLAS        v
    JOIN    SZAMLAK         sz ON v.SZAMLASZAM        = sz.SZAMLASZAM
    JOIN    DIJCSOMAG       d  ON sz.TERMEKMEGNEVEZES = d.CSOMAGNEV
    GROUP BY TO_CHAR(v.VASARLAS_IDOPONT,'YYYY-MM'), d.CSOMAGNEV
    ORDER BY HONAP, d.CSOMAGNEV
";
$stid_csomag = oci_parse($conn, $sql_havi_csomag);
oci_execute($stid_csomag);


$sql_nem_vasarlok = "
    SELECT FELHASZNALONEV, EMAIL
    FROM   FELHASZNALOK
    WHERE  ID NOT IN (SELECT DISTINCT FELHASZNALO_ID FROM VASARLAS)
    ORDER BY FELHASZNALONEV
";
$stid_nem_vasarlok = oci_parse($conn, $sql_nem_vasarlok);
oci_execute($stid_nem_vasarlok);




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

    <section id="topvasarlo">
    <h2>Top vásárló</h2>
    <?php if ($row = oci_fetch_assoc($stid_top)): ?>
        <table class="datatable">
            <thead>
                <tr><th>ID</th><th>Felhasználónév</th><th>Email</th><th>Vásárlások száma</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $row['FELHASZNALO_ID']; ?></td>
                    <td><?php echo htmlspecialchars($row['FELHASZNALONEV']); ?></td>
                    <td><?php echo htmlspecialchars($row['EMAIL']); ?></td>
                    <td><?php echo $row['VASARLASOK_SZAMA']; ?></td>
                </tr>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nincs vásárlási adat.</p>
    <?php endif; ?>
</section>

<section id="topbejelentkezo">
    <h2>Top bejelentkező</h2>
    <?php if ($row = oci_fetch_assoc($stid)): ?>
        <table class="datatable">
            <thead>
                <tr><th>ID</th><th>Felhasználónév</th><th>Bejelentkezések száma</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $row['ID']; ?></td>
                    <td><?php echo htmlspecialchars($row['FELHASZNALONEV']); ?></td>
                    <td><?php echo $row['BEJELENTKEZESEK_SZAMA']; ?></td>
                </tr>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nincs bejelentkezési adat.</p>
    <?php endif; ?>
</section>
</main>
<section id="bevetel">
<h2>Havi bevételek</h2>
    <table>
        <tr><th>Hónap</th><th>Bevétel (Ft)</th></tr>
        <?php while ($row = oci_fetch_assoc($stid)): ?>
            <tr>
                <td><?= htmlspecialchars($row['HONAP']) ?></td>
                <td><?= number_format($row['HAVI_BEVETEL'], 0, ',', ' ') ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    </section>

<section id="havi-bevetel-csomag">
  <h2>Havi bevétel csomagonként</h2>
  <table class="datatable">
    <tr><th>Hónap</th><th>Csomag</th><th>Bevétel (Ft)</th></tr>
    <?php while ($row = oci_fetch_assoc($stid_csomag)): ?>
      <tr>
        <td><?= htmlspecialchars($row['HONAP']) ?></td>
        <td><?= htmlspecialchars($row['CSOMAGNEV']) ?></td>
        <td><?= number_format($row['BEVETEL'], 0, ',', ' ') ?></td>
      </tr>
    <?php endwhile; ?>
  </table>
</section>


<section id="nemvasarlok">
  <h2>Nem vásárló felhasználók</h2>
  <?php if (oci_fetch_all($stid_nem_vasarlok, $tmp_nv, null, null, OCI_FETCHSTATEMENT_BY_ROW) === 0): ?>
      <p>Minden felhasználó vásárolt már.</p>
  <?php else: ?>
      <?php oci_execute($stid_nem_vasarlok); ?>
      <ul>
        <?php while ($row = oci_fetch_assoc($stid_nem_vasarlok)): ?>
          <li><strong><?= htmlspecialchars($row['FELHASZNALONEV']) ?></strong> – <?= htmlspecialchars($row['EMAIL']) ?></li>
        <?php endwhile; ?>
      </ul>
  <?php endif; ?>
</section>

<?php include 'footer.php'; ?>
</body>
</html>
<?php
oci_free_statement($stid_novalasz);
oci_free_statement($stid_valaszolt);
oci_close($conn);
?>
