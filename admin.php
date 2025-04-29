<?php
session_start();
$conn = oci_connect('C##R6LBDN', 'C##R6LBDN',
                    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SID=orania2)))',
                    'UTF8');

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $csomagnev = $_POST['csomagnev'];
    $csomagar = $_POST['csomagar'];
    $szolgaltatasNev = $_POST['szolgaltatas_nev'];  

    if (!empty($csomagnev) && !empty($csomagar) && !empty($szolgaltatasNev)) {
        $query = "INSERT INTO DIJCSOMAG (CSOMAGNEV, CSOMAG_AR) VALUES (:csomagnev, :csomagar)";
        $stid = oci_parse($conn, $query);

        oci_bind_by_name($stid, ":csomagnev", $csomagnev);
        oci_bind_by_name($stid, ":csomagar", $csomagar);
        if (oci_execute($stid)) {
            $query2 = "INSERT INTO WEB_SZOLGALTATAS (
                           SZOLGALTATAS_NEV, 
                           SZOLGALTATAS_TIPUS, 
                           SZOLGALTATAS_EGYSEGAR, 
                           AKTIV_E, 
                           HASZNALAT_KEZDETE, 
                           HASZNALAT_VEGE) 
                       VALUES (:szolgaltatas_nev, :csomagnev, :csomagar, 'Y', SYSDATE, NULL)";
            $stid2 = oci_parse($conn, $query2);

            oci_bind_by_name($stid2, ":szolgaltatas_nev", $szolgaltatasNev);
            oci_bind_by_name($stid2, ":csomagnev", $csomagnev);
            oci_bind_by_name($stid2, ":csomagar", $csomagar);

            if (oci_execute($stid2)) {
                $_SESSION['message'] = "Csomag és szolgáltatás sikeresen felvéve!";
                header("Location: admin.php"); 
                exit;
            } else {
                $_SESSION['message'] = "Hiba történt a szolgáltatás felvitele közben!";
            }
            oci_free_statement($stid2);
        } else {
            $_SESSION['message'] = "Hiba történt a csomag felvitele közben!";
        }
        oci_free_statement($stid);
    } else {
        $_SESSION['message'] = "Kérjük, töltsd ki az összes mezőt!";
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
    <link rel="stylesheet" href="css/a.css">
</head>
<body>
<?php include 'header.php'; ?>
    <main>
        <h1>Csomag felvitele</h1>

        <?php 
        if (isset($_SESSION['message'])): ?>
            <p class="success"><?php echo $_SESSION['message']; ?></p>
            <?php 
            unset($_SESSION['message']);
        endif; 
        ?>

        <form method="POST" action="admin.php">
            <label for="csomagnev">Csomag neve:</label>
            <input type="text" id="csomagnev" name="csomagnev" required><br><br>

            <label for="csomagar">Csomag ára:</label>
            <input type="number" id="csomagar" name="csomagar" required><br><br>

            <label for="szolgaltatas_nev">Szolgáltatás neve:</label>
            <input type="text" id="szolgaltatas_nev" name="szolgaltatas_nev" required><br><br>

            <button type="submit">Csomag felvétele</button>
        </form>
    </main>
</body>
</html>
