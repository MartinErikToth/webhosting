<?php
$conn = oci_connect(
    'C##R6LBDN', 'C##R6LBDN',
    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SID=orania2)))',
    'UTF8'
);

if (!$conn) {
    $e = oci_error();
    die("Kapcsolódási hiba: " . htmlspecialchars($e['message']));
}

$sql_top_user = "SELECT FELHASZNALONEV
FROM (
  SELECT FELHASZNALOK.FELHASZNALONEV, COUNT(*) AS VASARLASOK,
         ROW_NUMBER() OVER (ORDER BY COUNT(*) DESC) AS RNUM
  FROM FELHASZNALOK
  JOIN VASARLAS ON VASARLAS.FELHASZNALO_ID = FELHASZNALOK.ID
  GROUP BY FELHASZNALOK.FELHASZNALONEV
) WHERE RNUM = 1";


$stid_top = oci_parse($conn, $sql_top_user);
oci_execute($stid_top);
$top_vasarlok = [];
while ($row = oci_fetch_assoc($stid_top)) {
    $top_vasarlok[] = $row;
}

$sql_top_spender = "SELECT FELHASZNALONEV
FROM(
    SELECT FELHASZNALONEV, SUM(DIJCSOMAG.CSOMAG_AR) AS AR
    FROM FELHASZNALOK
    JOIN VASARLAS ON VASARLAS.FELHASZNALO_ID=FELHASZNALOK.ID
    JOIN DIJCSOMAG ON VASARLAS.CSOMAGKOD =DIJCSOMAG.CSOMAGKOD

    GROUP BY FELHASZNALONEV
    ORDER BY AR DESC
)
WHERE ROWNUM=1

";
$stid_spender = oci_parse($conn, $sql_top_spender);
oci_execute($stid_spender);
$top_spenders = [];
while ($row = oci_fetch_assoc($stid_spender)) {
    $top_spenders[] = $row;
}

$sql_top_service = "SELECT SZOLGALTATAS_NEV FROM (
    SELECT SZOLGALTATAS_NEV, MEGTEKINTESK_SZAMA,
           RANK() OVER (ORDER BY MEGTEKINTESK_SZAMA DESC) AS rangsor
    FROM WEB_SZOLGALTATAS
) WHERE rangsor = 1";


$stid_service = oci_parse($conn, $sql_top_service);
if (!oci_execute($stid_service)) {
    $e = oci_error($stid_service);
    die("Hiba a legnépszerűbb szolgáltatás lekérdezésekor: " . htmlspecialchars($e['message']));
}
$top_services = [];
while ($row = oci_fetch_assoc($stid_service)) {
    $top_services[] = $row;
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Havi statisztikák</title>
    <link rel="stylesheet" href="css/stat.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container">
    
    <h2>Legaktívabb vásárlók</h2>
    <?php if (!empty($top_vasarlok)): ?>
        <ul>
        <?php foreach ($top_vasarlok as $vasarlo): ?>
            <li><strong><?= htmlspecialchars($vasarlo['FELHASZNALONEV']) ?></li>
        <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Nincs vásárlás adat.</p>
    <?php endif; ?>

    <h2>Legtöbbet költő vásárlók</h2>
    <?php if (!empty($top_spenders)): ?>
        <table>
            <tr><th>Vásárló</th><th>Összeg (Ft)</th></tr>
            <?php foreach ($top_spenders as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['FELHASZNALONEV']) ?></td>
                    <td><?= number_format($row['AR'], 0, ',', ' ') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nincs költési adat.</p>
    <?php endif; ?>

    <h2>Legnépszerűbb webszolgáltatás</h2>
    <?php if (!empty($top_services)): ?>
        <ul>
            <?php foreach ($top_services as $service): ?>
                <li><?= htmlspecialchars($service['SZOLGALTATAS_NEV']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Nincs elérhető adat.</p>
    <?php endif; ?>
</div>
</body>
</html>
<?php

oci_free_statement($stid_top);
oci_free_statement($stid_spender);
oci_free_statement($stid_service);
oci_close($conn);
?>
