<?php 
if (isset($_POST['delete_profile'])) {
    $deleteUserQuery = "DELETE FROM FELHASZNALOK WHERE ID = :id";
    $stid = oci_parse($conn, $deleteUserQuery);
    oci_bind_by_name($stid, ":id", $id);
    oci_execute($stid);
    oci_free_statement($stid);

    session_destroy();
    header("Location: login.php");
    exit();
}

?>