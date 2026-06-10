
<!-- Anton Nguyen -->
<!--Deletes a driver from the database using the provided employee ID.-->

<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["tname"])) {
    header("Location: ../niklas/index.php");
    exit();
}

$db = new mysqli("localhost", "gruppe19", "{yI)X2)vN7w1", "gruppe19");

if (mysqli_connect_error()) {
    die("Fehler: " . mysqli_connect_error());
}

// Get employee ID from URL parameter
$id = isset($_GET["id"]) ? $_GET["id"] : "";

// Check if an ID was provided
if ($id !== "") {

    $deleteDriver = $db->prepare("DELETE FROM Fahrer WHERE MID = ? AND TName = ?");
    $deleteDriver->bind_param("ss", $id, $_SESSION["tname"]);
    $deleteDriver->execute();
    // Store number of deleted rows to verify the delete actually matched a record
    $affected = $deleteDriver->affected_rows;
    $deleteDriver->close();

    if ($affected > 0) {
        echo "Erfolgreich gelöscht. <a href='dashboard.php'>Zurück zum Dashboard</a>";
    } else {
        echo "Fehler: Fahrer nicht gefunden oder gehört nicht zu Ihrem Team.";
    }
} else {
    echo "Fehler: Keine Mitarbeiter-ID angegeben.";
}

$db->close();
?>

