<?php
session_start();

$conn = oci_connect('C##R6LBDN', 'C##R6LBDN',
    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SID=orania2)))', 'UTF8');

if (isset($_SESSION['user_id'])) {
    $felhasznalo_id = $_SESSION['user_id'];
    
    $sql = "UPDATE FELHASZNALOK SET BE_VAN_JELENTKEZVE = 'N' WHERE ID = :felhasznalo_id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":felhasznalo_id", $felhasznalo_id);

    if (oci_execute($stmt)) {
        $_SESSION = []; 
        session_destroy(); 

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        header("Location: login.php");
        exit;
    } else {
        echo "Hiba történt a kijelentkezés során!";
    }

    oci_free_statement($stmt);
} else {
    header("Location: login.php");
    exit;
}

oci_close($conn);
?>
