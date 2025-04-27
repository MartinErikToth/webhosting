<?php
session_start();

$hiba = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['vissza'])) {
        header("Location: index.php");
        exit;
    }

    if (isset($_POST['guest'])) {
        header("Location: index.php");
        exit;
    }

    if (isset($_POST['regisztracio'])) {
        $felhasznalonev = $_POST['felhasznalonev'];
        $jelszo = $_POST['jelszo'];
        $email = $_POST['email'];

        if (empty($felhasznalonev) || empty($jelszo) || empty($email)) {
            $hiba = "Minden mezőt ki kell tölteni!";
        } else {
            /* Gép: 
            $conn = oci_connect('C##R6LBDN', 'C##R6LBDN',
                '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SID=orania2)))', 'UTF8');
            */

            /* Laptop: */
            $conn = oci_connect('C##R6LBDN', 'C##R6LBDN',
            '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=11521))(CONNECT_DATA=(SID=orania2)))', 'UTF8');

            if (!$conn) {
                $e = oci_error();
                die("Kapcsolódási hiba: " . $e['message']);
            }

            $check = "SELECT COUNT(*) AS CNT FROM FELHASZNALOK WHERE EMAIL = :email OR FELHASZNALONEV = :fnev";
            $stid = oci_parse($conn, $check);
            oci_bind_by_name($stid, ":email", $email);
            oci_bind_by_name($stid, ":fnev", $felhasznalonev);
            oci_execute($stid);
            $row = oci_fetch_assoc($stid);

            if ($row['CNT'] > 0) {
                $hiba = "Már létezik ilyen e-mail vagy felhasználónév.";
            } else {
                $hashed = password_hash($jelszo, PASSWORD_DEFAULT);
                $insert = "INSERT INTO FELHASZNALOK (BE_VAN_JELENTKEZVE, EMAIL, FELHASZNALONEV, JELSZO, SZEREP)
                            VALUES ('N', :email, :fnev, :jelszo, 'Vendeg')
                            RETURNING ID INTO :new_id";
                $stid2 = oci_parse($conn, $insert);
                oci_bind_by_name($stid2, ":email", $email);
                oci_bind_by_name($stid2, ":fnev", $felhasznalonev);
                oci_bind_by_name($stid2, ":jelszo", $hashed);
                oci_bind_by_name($stid2, ":new_id", $new_id, -1, SQLT_INT);

                if (oci_execute($stid2)) {
                    $_SESSION['user_id'] = $new_id;
                    oci_free_statement($stid2);
                    oci_free_statement($stid);
                    oci_close($conn);
                    header("Location: login.php");
                    exit;
                } else {
                    $hiba = "Hiba történt a regisztráció során.";
                }
            }

            oci_free_statement($stid);
            oci_close($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Regisztráció</title>
    <link rel="stylesheet" href="css/singup.css">
    <script>
        function setRequired(gomb) {
            const user = document.getElementById('felhasznalonev');
            const pass = document.getElementById('jelszo');
            const email = document.getElementById('email');
            if (gomb === 'regisztracio') {
                user.required = true;
                pass.required = true;
                email.required = true;
            } else {
                user.required = false;
                pass.required = false;
                email.required = false;
            }
        }
    </script>
</head>
<body>
    <div class="login-container">
        <h1>Regisztráció</h1>

        <form method="post">
            <div class="input-box">
                <label for="felhasznalonev">Felhasználónév:</label>
                <input type="text" name="felhasznalonev" id="felhasznalonev">
            </div>

            <div class="input-box">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email">
            </div>

            <div class="input-box">
                <label for="jelszo">Jelszó:</label>
                <input type="password" name="jelszo" id="jelszo">
            </div>

            <div class="button-box">
                <button type="submit" name="regisztracio" onclick="setRequired('regisztracio')">Regisztráció</button>
                <button type="submit" name="guest" onclick="window.location.href='index.php';">Folytatás vendégként</button>
                <button type="button" onclick="window.location.href='login.php';">Van fiókod? Jelentkezz be!</button>
            </div>
        </form>

        <?php if (!empty($hiba)): ?>
            <p class="error-msg"><?php echo $hiba; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>

