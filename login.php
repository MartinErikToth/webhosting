<?php
session_start();

$hiba = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {   
    if (isset($_POST['vissza'])) {
        header("Location: index.php");
        exit;
    }

    if (isset($_POST['belepes'])) {
        $conn = oci_connect('C##R6LBDN', 'C##R6LBDN',
                    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SID=orania2)))',
                    'UTF8');
        if (!$conn) {
            $e = oci_error();
            die("Kapcsolódási hiba: " . $e['message']);
        }

        $felhasznalonev = $_POST['felhasznalonev'];
        $jelszo = $_POST['jelszo'];

        $query = "SELECT ID, JELSZO, BE_VAN_JELENTKEZVE, SZEREP FROM FELHASZNALOK WHERE FELHASZNALONEV = :fnev";
        $stid = oci_parse($conn, $query);
        oci_bind_by_name($stid, ":fnev", $felhasznalonev);
        oci_execute($stid);
        $row = oci_fetch_assoc($stid);

        if ($row) {
            if (password_verify($jelszo, $row['JELSZO'])) {
                if (trim($row['BE_VAN_JELENTKEZVE']) === 'Y') {
                    $hiba = "Ez a felhasználó már be van jelentkezve.";
                } else {
                    $update = "UPDATE FELHASZNALOK SET BE_VAN_JELENTKEZVE = 'Y' WHERE ID = :id";
                    $stid2 = oci_parse($conn, $update);
                    oci_bind_by_name($stid2, ":id", $row['ID']);
                    oci_execute($stid2);
                    $_SESSION['user_id'] = $row['ID'];
                    $_SESSION['szerep'] = strtolower(trim($row['SZEREP']));
                    oci_free_statement($stid2);
                    oci_free_statement($stid);
                    oci_close($conn);
                    header("Location: index.php");
                    exit;
                }
            } else {
                $hiba = "Hibás jelszó.";
            }
        } else {
            $hiba = "Nem létező felhasználónév.";
        }

        oci_free_statement($stid);
        oci_close($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Bejelentkezés</title>
    <link rel="stylesheet" href="css/login.css">
    <script>
        function setRequired(gomb) {
            const user = document.getElementById('felhasznalonev');
            const pass = document.getElementById('jelszo');
            if (gomb === 'belepes') {
                user.required = true;
                pass.required = true;
            } else {
                user.required = false;
                pass.required = false;
            }
        }
    </script>
</head>
<body>
    <div class="login-container">
        <h1>Bejelentkezés</h1>

        <form method="post">
            <div class="input-box">
                <label for="felhasznalonev">Felhasználónév:</label>
                <input type="text" name="felhasznalonev" id="felhasznalonev" required>
            </div>

            <div class="input-box">
                <label for="jelszo">Jelszó:</label>
                <input type="password" name="jelszo" id="jelszo" required>
            </div>

            <div class="button-box">
                <button type="submit" name="belepes">Belépés</button>
                <button type="button" onclick="window.location.href='singup.php';">Regisztráció</button>
            </div>
        </form>

        <?php if (!empty($hiba)): ?>
            <p class="error-msg"><?php echo $hiba; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
