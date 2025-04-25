<?php

$conn = oci_connect('C##R6LBDN', 'C##R6LBDN',
'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SID=orania2)))',
'UTF8');

if (!$conn) {
    $e = oci_error();
    echo "Kapcsolódás sikertelen: " . $e['message'];
    exit;
}

// Kérdés beküldése
if (isset($_POST['submit'])) {
    $tipus = $_POST['tipus'];
    $kerdes = $_POST['kerdes'];
    $valasz = "Válaszra vár"; 
    $mikorkeszult = date('Y-m-d H:i:s'); 

    $sql2 = "SELECT NVL(MAX(BEJEGYZES_SZAMA), 0) + 1 AS NEXT_BEJEGYZES_SZAMA FROM BEJEGYZES";
    $stid = oci_parse($conn, $sql2);
    oci_execute($stid);
    $row = oci_fetch_assoc($stid);
    $bejegyzesszama = $row['NEXT_BEJEGYZES_SZAMA']; 
    oci_free_statement($stid);

    // Kérdés beszúrása az adatbázisba
    $sql = "INSERT INTO BEJEGYZES (BEJEGYZES_SZAMA, TIPUS, KERDES, VALASZ, MIKOR_KESZULT) 
        VALUES (:bejegyzesszama, :tipus, :kerdes, :valasz, TO_TIMESTAMP(:mikorkeszult, 'YYYY-MM-DD HH24:MI:SS'))";
    $stid = oci_parse($conn, $sql);

    oci_bind_by_name($stid, ":bejegyzesszama", $bejegyzesszama);
    oci_bind_by_name($stid, ":tipus", $tipus);
    oci_bind_by_name($stid, ":kerdes", $kerdes);
    oci_bind_by_name($stid, ":valasz", $valasz);
    oci_bind_by_name($stid, ":mikorkeszult", $mikorkeszult);

    if (oci_execute($stid)) {
        $_SESSION['success_message'] = "A kérdés sikeresen mentésre került!"; 
        header("Location: tudastar.php"); 
        exit;
    } else {
        $e = oci_error($stid);
        $_SESSION['error_message'] = "Hiba történt: " . $e['message']; 
        header("Location: tudastar.php"); 
        exit;
    }
    oci_free_statement($stid);
}


$sql3 = "SELECT * FROM BEJEGYZES ORDER BY BEJEGYZES_SZAMA DESC FETCH FIRST 1 ROWS ONLY";
$stid3 = oci_parse($conn, $sql3);
oci_execute($stid3);

$latest_question = oci_fetch_assoc($stid3); 

oci_free_statement($stid3);

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tudásbázis - Kérdés feltevés</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/t.css">
    <script src="js/tudastar.js"></script>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <form action="tudastar.php" method="post" class="form-container" id="questionForm">
        <div class="form-group">
            <label for="tipus">Kérdés típusa</label>
            <input type="text" id="tipus" name="tipus" required>
        </div>
        <div class="form-group">
            <label for="kerdes">Kérdés</label>
            <textarea id="kerdes" name="kerdes" required></textarea>
        </div>
        <div class="form-group">
            <button type="submit" name="submit">Kérdés mentése</button>
        </div>
    </form>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="message success-message show-message"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php elseif (isset($_SESSION['error_message'])): ?>
        <div class="message error-message show-message"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>

    <?php if ($latest_question): ?>
        <div class="latest-question">
            <h3>Legutóbbi kérdés:</h3>
            <p><strong>Típus:</strong> <?php echo htmlspecialchars($latest_question['TIPUS']); ?></p>
            <p><strong>Kérdés:</strong> <?php echo htmlspecialchars($latest_question['KERDES']); ?></p>
            <p><strong>Válasz:</strong> <?php echo htmlspecialchars($latest_question['VALASZ']); ?></p>
            <p><strong>Feladás dátuma:</strong> <?php echo htmlspecialchars($latest_question['MIKOR_KESZULT']); ?></p>
        </div>
    <?php endif; ?>
</div>

<footer>
    <p>&copy; 2025 WebHosting | Minden jog fenntartva</p>
</footer>

</body>
</html>
