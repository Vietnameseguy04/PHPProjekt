<!-- Anton Nguyen -->
<!-- Form for creating and editing a driver (creating, loading, and saving driver data in the database). -->


<?php

// Only start a session if none is active, avoids "session already started" warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "functions.php";


if (!isset($_SESSION["tname"])) {
    header("Location: ../niklas/index.php");
    exit();
}

$db = new mysqli("localhost", "gruppe19", "{yI)X2)vN7w1", "gruppe19");

if (mysqli_connect_error()) {
    die("Fehler: " . mysqli_connect_error());
}


$message = "";

// get edit parameters from URL
$editId   = isset($_GET["id"])   ? $_GET["id"]   : "";
$editName = isset($_GET["name"]) ? $_GET["name"] : "";

// check if form is in editmode, when true = edit, false = create new
$isEdit = ($editId !== "" && $editName !== "");

// default form values
$id      = $editId;
$name    = $editName;
$team    = isset($_SESSION["tname"]) ? $_SESSION["tname"] : "";
$street  = "";
$houseNr = "";
$zip     = "";
$city    = "";
$phone   = "";


// load existing driver data when editing
if ($isEdit) {
    $fetchDriver = $db->prepare("SELECT MID, TName, Name, Strasse, HNR, PLZ, Ort, TelNr FROM Fahrer WHERE MID = ?");
    $fetchDriver->bind_param("s", $editId);
    $fetchDriver->execute();
    $driverData = $fetchDriver->get_result();
    $fetchDriver->close();

    //store database values into variables
    if ($row = $driverData->fetch_assoc()) {
        $id      = $row["MID"];
        $team    = $row["TName"];
        $name    = $row["Name"];
        $street  = $row["Strasse"];
        $houseNr = $row["HNR"];
        $zip     = $row["PLZ"];
        $city    = $row["Ort"];
        $phone   = $row["TelNr"];
    }
}


if (isset($_POST["submit"])) {

    $id      = isset($_POST["id"])        ? trim($_POST["id"])        : "";
    $name    = isset($_POST["name"])      ? trim($_POST["name"])      : "";
    $team    = isset($_SESSION["tname"])  ? $_SESSION["tname"]        : "";
    $street  = isset($_POST["street"])    ? trim($_POST["street"])    : "";
    $houseNr = isset($_POST["house_nr"])  ? trim($_POST["house_nr"])  : "";
    $zip     = isset($_POST["zip"])       ? trim($_POST["zip"])       : "";
    $city    = isset($_POST["city"])      ? trim($_POST["city"])      : "";
    $phone   = isset($_POST["phone"])     ? trim($_POST["phone"])     : "";
    $oldId   = isset($_POST["old_id"])    ? trim($_POST["old_id"])    : "";

    if (
        empty($name) ||
        empty($street) || empty($houseNr) || empty($zip) ||
        empty($city) || empty($phone)
    ) {
        $message = "Bitte alle Felder ausfüllen!";
    } else {
        // Check if the selected team exists
        $checkTeam = $db->prepare("SELECT TName FROM Team WHERE TName = ?");
        $checkTeam->bind_param("s", $team);
        $checkTeam->execute();
        $teamExists = $checkTeam->get_result()->fetch_assoc() !== null;
        $checkTeam->close();

        if (!$teamExists) {
            $message = "Fehler: Team existiert nicht!";
        } else {
            $error = fahrerSpeichern($db, $id, $oldId, $team, $name, $street, $houseNr, $zip, $city, $phone);
            if ($error === "") {
                $message = $oldId !== "" ? "Fahrer wurde aktualisiert!" : "Fahrer wurde erstellt!";
            } else {
                $message = $error;
            }
        }
    }
}

$db->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fahrer</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1><?php echo $isEdit ? "Fahrer bearbeiten" : "Fahrer anlegen"; ?></h1>

<p><?php echo $message; ?></p>

<!-- Form uses POST so field values are not exposed in the URL-->
<form method="post" action="">

    
    <input type="hidden" name="old_id" value="<?php echo $editId; ?>">

    <?php if ($isEdit): ?>
    <label>Mitarbeiter-ID:</label><br>
    <input type="text" name="id" value="<?php echo $id; ?>" readonly>
    <br><br>
    <?php endif; ?>

    <label>Name:</label><br>
    <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>">
    <br><br>

    <label>Strasse:</label><br>
    <input type="text" name="street" value="<?php echo htmlspecialchars($street); ?>">
    <br><br>

    <label>Hausnummer:</label><br>
    <input type="text" name="house_nr" value="<?php echo htmlspecialchars($houseNr); ?>">
    <br><br>

    <label>PLZ:</label><br>
    <input type="text" name="zip" value="<?php echo htmlspecialchars($zip); ?>">
    <br><br>

    <label>Ort:</label><br>
    <input type="text" name="city" value="<?php echo htmlspecialchars($city); ?>">
    <br><br>

    <label>Telefon:</label><br>
    <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
    <br><br>


    <input type="submit" name="submit" value="<?php echo $isEdit ? 'Aktualisieren' : 'Speichern'; ?>">

</form>

<br>

<a href="dashboard.php">Zurück</a>

</body>
</html>
