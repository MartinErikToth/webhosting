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


$sql = "SELECT 
        TO_CHAR(v.VASARLAS_IDOPONT, 'YYYY-MM') AS honap,
        SUM(d.CSOMAG_AR) AS havi_bevetel
    FROM 
        DIJCSOMAG d
    JOIN 
        VASARLAS v ON d.VASARLAS_AZON = v.VASARLAS_AZON
    GROUP BY 
        TO_CHAR(v.VASARLAS_IDOPONT, 'YYYY-MM')
    ORDER BY honap
";
$stid = oci_parse($conn, $sql);
if (!oci_execute($stid)) {
    $e = oci_error($stid);
    die("Hiba a havi bevételek lekérdezésekor: " . htmlspecialchars($e['message']));
}


$sql_top_user = "SELECT VASARLO_NEV, vasarlasok_szama FROM (
        SELECT 
            V.VASARLO_NEV,
            COUNT(*) AS vasarlasok_szama,
            RANK() OVER (ORDER BY COUNT(*) DESC) AS rangsor
        FROM VASARLAS V
        WHERE TO_CHAR(V.VASARLAS_IDOPONT, 'YYYY-MM') = TO_CHAR(SYSDATE, 'YYYY-MM')
        GROUP BY V.VASARLO_NEV
    ) WHERE rangsor = 1
";
$stid_top = oci_parse($conn, $sql_top_user);
oci_execute($stid_top);
$top_vasarlok = [];
while ($row = oci_fetch_assoc($stid_top)) {
    $top_vasarlok[] = $row;
}


$sql_top_spender = "SELECT VASARLO_NEV, osszeg FROM (
        SELECT 
            V.VASARLO_NEV,
            SUM(D.CSOMAG_AR) AS osszeg,
            RANK() OVER (ORDER BY SUM(D.CSOMAG_AR) DESC) AS rangsor
        FROM VASARLAS V
        JOIN DIJCSOMAG D ON V.VASARLAS_AZON = D.VASARLAS_AZON
        WHERE TO_CHAR(V.VASARLAS_IDOPONT, 'YYYY-MM') = TO_CHAR(SYSDATE, 'YYYY-MM')
        GROUP BY V.VASARLO_NEV
    ) WHERE rangsor = 1
";
$stid_spender = oci_parse($conn, $sql_top_spender);
oci_execute($stid_spender);
$top_spenders = [];
while ($row = oci_fetch_assoc($stid_spender)) {
    $top_spenders[] = $row;
}
$sql_top_service = "SELECT SZOLGALTATAS_NEV, MEGTEKINTESK_SZAMA FROM (
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
    <title>Havi bevételek</title>
    <link rel="stylesheet" href="css/stat.css">
</head>
<body>
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

    <h2>Legaktívabb vásárlók ebben a hónapban</h2>
    <?php if (!empty($top_vasarlok)): ?>
        <ul>
        <?php foreach ($top_vasarlok as $vasarlo): ?>
            <li><strong><?= htmlspecialchars($vasarlo['VASARLO_NEV']) ?></strong> (<?= $vasarlo['VASARLASOK_SZAMA'] ?> vásárlás)</li>
        <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Nincs vásárlás ebben a hónapban.</p>
    <?php endif; ?>

    <h2>Legtöbbet költő vásárlók ebben a hónapban</h2>
    <?php if (!empty($top_spenders)): ?>
        <table>
            <tr><th>Vásárló</th><th>Összeg (Ft)</th></tr>
            <?php foreach ($top_spenders as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['VASARLO_NEV']) ?></td>
                    <td><?= number_format($row['OSSZEG'], 0, ',', ' ') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nincs vásárlás ebben a hónapban.</p>
    <?php endif; ?>
    <h2>Legnépszerűbb webszolgáltatás</h2>
    <?php if (!empty($top_services)): ?>
        <ul>
            <?php foreach ($top_services as $service): ?>
                <li><strong><?= htmlspecialchars($service['SZOLGALTATAS_NEV']) ?></strong> (<?= $service['MEGTEKINTESK_SZAMA'] ?> megtekintés)</li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Nem található népszerű szolgáltatás.</p>
    <?php endif; ?>
</body>
</html>
<?php
oci_free_statement($stid);
oci_free_statement($stid_top);
oci_free_statement($stid_spender);
oci_free_statement($stid_service);
oci_close($conn);
?>
