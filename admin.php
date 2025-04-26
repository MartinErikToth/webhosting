<?php
session_start();
// Adatbázis kapcsolat (Ezt módosítani kell a saját beállításaik szerint)
/* Gép: 
$conn = oci_connect('C##R6LBDN', 'C##R6LBDN',
    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SID=orania2)))', 'UTF8');
*/

/* Laptop: */
$conn = oci_connect('C##R6LBDN', 'C##R6LBDN',
    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=11521))(CONNECT_DATA=(SID=orania2)))', 'UTF8');

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $csomagnev = $_POST['csomagnev'];
    $csomagar = $_POST['csomagar'];

    if (!empty($csomagnev) && !empty($csomagar)) {
        $query = "INSERT INTO DIJCSOMAG (CSOMAGNEV, CSOMAG_AR) VALUES (:csomagnev, :csomagar)";
        $stid = oci_parse($conn, $query);

        oci_bind_by_name($stid, ":csomagnev", $csomagnev);
        oci_bind_by_name($stid, ":csomagar", $csomagar);

        if (oci_execute($stid)) {
            $message = "Csomag sikeresen felvéve!";
        } else {
            $message = "Hiba történt a csomag felvitele közben!";
        }
        oci_free_statement($stid);
    } else {
        $message = "Kérjük, töltsd ki az összes mezőt!";
    }
}

oci_close($conn);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Csomag felvitele</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<?php include 'header.php'; ?>
    <main>
        <h1>Csomag felvitele</h1>

        <?php if ($message): ?>
            <p class="success"><?php echo $message; ?></p>
        <?php endif; ?>

        <form method="POST" action="admin.php">
            <label for="csomagnev">Csomag neve:</label>
            <input type="text" id="csomagnev" name="csomagnev" required><br><br>

            <label for="csomagar">Csomag ára:</label>
            <input type="number" id="csomagar" name="csomagar" required><br><br>

            <button type="submit">Csomag felvétele</button>
        </form>
    </main>
</body>
</html>
