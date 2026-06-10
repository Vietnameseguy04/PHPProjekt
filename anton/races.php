<!-- Anton Nguyen -->
<!-- Displays upcoming races and allows the registration of drivers from the logged-in team. -->

<?php

$db = new mysqli("localhost", "gruppe19", "{yI)X2)vN7w1", "gruppe19");

if (mysqli_connect_error()) {
    die("Fehler: " . mysqli_connect_error());
}

$message = "";

// Load all drivers belonging to the currently logged-in team
$drivers = [];
$fetchDrivers = $db->prepare("SELECT MID, Name, TName FROM Fahrer WHERE TName = ?");
$fetchDrivers->bind_param("s", $_SESSION["tname"]);
$fetchDrivers->execute();
$driversResult = $fetchDrivers->get_result();
$fetchDrivers->close();
while ($row = $driversResult->fetch_assoc()) {
    $drivers[] = ["mid" => $row["MID"], "name" => $row["Name"], "tname" => $row["TName"]];
}

// Load all upcoming races (today or later)
$races = [];
$upcomingRacesResult = $db->query(
    "SELECT RID, Datum, StartOrt FROM Radrennen WHERE Datum >= CURDATE() ORDER BY Datum ASC"
);
while ($row = $upcomingRacesResult->fetch_assoc()) {
    $races[] = ["rid" => $row["RID"], "datum" => $row["Datum"], "ort" => $row["StartOrt"]];
}

// Process copy form submission
if (isset($_POST["copy"])) {
    $source_rid = isset($_POST["source_rid"]) ? $_POST["source_rid"] : "";
    $target_rid = isset($_POST["target_rid"]) ? $_POST["target_rid"] : "";

   
    $fetchSource = $db->prepare(
        "SELECT MID, TName FROM Teilnahme WHERE RID = ? AND TName = ?"
    );
    $fetchSource->bind_param("ss", $source_rid, $_SESSION["tname"]);
    $fetchSource->execute();
    $sourceResult = $fetchSource->get_result();
    $fetchSource->close();

    $copied   = 0;
    $error    = false;
    $skipped  = [];

    while ($row = $sourceResult->fetch_assoc()) {
        $mid      = $row["MID"];
        $teamName = $row["TName"];

        $checkReg = $db->prepare("SELECT MID FROM Teilnahme WHERE MID = ? AND RID = ?");
        $checkReg->bind_param("ss", $mid, $target_rid);
        $checkReg->execute();
        $alreadyReg = $checkReg->get_result()->fetch_assoc() !== null;
        $checkReg->close();
        if ($alreadyReg) {
            $skipped[] = htmlspecialchars($mid) . " (bereits in Zielrennen angemeldet)";
            continue;
        }

        $checkDate = $db->prepare(
            "SELECT t.MID FROM Teilnahme t
             JOIN Radrennen r ON t.RID = r.RID
             WHERE t.MID = ? AND r.Datum = (SELECT Datum FROM Radrennen WHERE RID = ?)"
        );
        $checkDate->bind_param("ss", $mid, $target_rid);
        $checkDate->execute();
        $hasConflict = $checkDate->get_result()->fetch_assoc() !== null;
        $checkDate->close();
        if ($hasConflict) {
            $skipped[] = htmlspecialchars($mid) . " (Datumskonflikt mit anderem Rennen)";
            continue;
        }

        // Insert — trigger assigns start number automatically
        $insert = $db->prepare("INSERT INTO Teilnahme (TName, MID, RID) VALUES (?, ?, ?)");
        $insert->bind_param("sss", $teamName, $mid, $target_rid);
        if ($insert->execute()) {
            $copied++;
        } else {
            $message = "Fehler beim Kopieren: " . $insert->error;
            $error = true;
        }
        $insert->close();
    }

    if (!$error) {
        if ($copied > 0 && empty($skipped)) {
            $message = $copied . " Fahrer wurden von Rennen " . htmlspecialchars($source_rid)
                     . " nach Rennen " . htmlspecialchars($target_rid) . " kopiert!";
        } elseif ($copied > 0 && !empty($skipped)) {
            $message = $copied . " Fahrer kopiert. Folgende Fahrer wurden übersprungen: "
                     . implode(", ", $skipped);
        } else {
            $message = "Fehler: Keine Fahrer wurden kopiert. Folgende Fahrer konnten nicht übertragen werden: "
                     . implode(", ", $skipped);
        }
    }
}

// Process registration form submission
if (isset($_POST["register"])) {
    $rid   = isset($_POST["rid"])   ? $_POST["rid"]        : "";
    $count = isset($_POST["count"]) ? (int)$_POST["count"] : 0;

    $error      = false;
    $registered = 0;

    // Loop through each selected driver slot
    for ($i = 0; $i < $count; $i++) {
        $mid = isset($_POST["driver_" . $i]) ? $_POST["driver_" . $i] : "";
        if (empty($mid)) continue;

        $checkDriver = $db->prepare("SELECT TName FROM Fahrer WHERE MID = ?");
        $checkDriver->bind_param("s", $mid);
        $checkDriver->execute();
        $row = $checkDriver->get_result()->fetch_assoc();
        $checkDriver->close();
        if ($row === null) continue;
        $teamName = $row["TName"];

        // Check if the driver is already registered for this race
        $checkReg = $db->prepare("SELECT MID FROM Teilnahme WHERE MID = ? AND RID = ?");
        $checkReg->bind_param("ss", $mid, $rid);
        $checkReg->execute();
        $alreadyReg = $checkReg->get_result()->fetch_assoc() !== null;
        $checkReg->close();
        if ($alreadyReg) {
            $message = "Fehler: Fahrer " . htmlspecialchars($mid) . " ist bereits für dieses Rennen angemeldet!";
            $error = true;
            continue;
        }

        // Check if the driver is already registered for another race on the same date
        $checkDate = $db->prepare(
            "SELECT t.MID FROM Teilnahme t
             JOIN Radrennen r ON t.RID = r.RID
             WHERE t.MID = ? AND r.Datum = (SELECT Datum FROM Radrennen WHERE RID = ?)"
        );
        $checkDate->bind_param("ss", $mid, $rid);
        $checkDate->execute();
        $hasConflict = $checkDate->get_result()->fetch_assoc() !== null;
        $checkDate->close();
        if ($hasConflict) {
            $message = "Fehler: Fahrer " . htmlspecialchars($mid) . " ist bereits an diesem Datum bei einem anderen Rennen angemeldet!";
            $error = true;
            continue;
        }

        // Insert registration — start number is assigned automatically by trigger
        $insertRegistration = $db->prepare(
            "INSERT INTO Teilnahme (TName, MID, RID) VALUES (?, ?, ?)"
        );
        $insertRegistration->bind_param("sss", $teamName, $mid, $rid);
        if ($insertRegistration->execute()) {
            $registered++;
        } else {
            $message = "Fehler: " . $insertRegistration->error;
            $error = true;
        }
        $insertRegistration->close();
    }

    if (!$error) {
        $message = $registered . " Fahrer wurden erfolgreich angemeldet!";
    }
}

// Remember which race and how many drivers were selected
$selected_rid   = isset($_POST["rid"])   ? $_POST["rid"]        : "";
$selected_count = isset($_POST["count"]) ? (int)$_POST["count"] : 1;

?>

<h1>Rennen & Anmeldung</h1>

<!-- Display success or error message if present -->
<?php if ($message !== ""): ?>
    <p><strong><?php echo $message; ?></strong></p>
<?php endif; ?>

<!-- Table of upcoming races with a registration button per row -->
<h2>Kommende Rennen</h2>
<table border="1" cellpadding="8">
    <tr>
        <th>ID</th>
        <th>Datum</th>
        <th>Startort</th>
        <th>Anzahl Fahrer</th>
        <th>Aktion</th>
    </tr>
    <?php foreach ($races as $race): ?>
        <!-- Highlight the currently selected race row -->
        <tr <?php if ($selected_rid == $race["rid"]) echo 'style="background:#d0e8ff"'; ?>>
            <td><?php echo htmlspecialchars($race["rid"]); ?></td>
            <td><?php echo htmlspecialchars($race["datum"]); ?></td>
            <td><?php echo htmlspecialchars($race["ort"]); ?></td>
            <td>
                <!-- Each row has its own form so the submit only affects that race -->
                <form method="post" action="">
                    <input type="hidden" name="rid" value="<?php echo htmlspecialchars($race["rid"]); ?>">
                    <input type="number" name="count" min="1" value="1" style="width:50px">
                </td>
                <td>
                    <input type="submit" name="proceed" value="Fahrer eintragen">
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<!-- Copy registration form -->
<h2>Anmeldungen kopieren</h2>
<p>Alle Fahrer deines Teams von einem Rennen auf ein neues Rennen übertragen:</p>
<form method="post" action="">
    <label>Quell-Rennen (von):</label>
    <select name="source_rid">
        <?php
        // Load all races that have at least one driver from this team registered
        $pastRaces = $db->prepare(
            "SELECT DISTINCT r.RID, r.Datum, r.StartOrt
             FROM Radrennen r
             JOIN Teilnahme t ON r.RID = t.RID
             WHERE t.TName = ?
             ORDER BY r.Datum DESC"
        );
        $pastRaces->bind_param("s", $_SESSION["tname"]);
        $pastRaces->execute();
        $pastResult = $pastRaces->get_result();
        $pastRaces->close();
        while ($row = $pastResult->fetch_assoc()):
        ?>
            <option value="<?php echo htmlspecialchars($row["RID"]); ?>">
                <?php echo htmlspecialchars($row["RID"]) . " – " . htmlspecialchars($row["Datum"]) . " – " . htmlspecialchars($row["StartOrt"]); ?>
            </option>
        <?php endwhile; ?>
    </select>
    &nbsp;
    <label>Ziel-Rennen (nach):</label>
    <select name="target_rid">
        <?php foreach ($races as $race): ?>
            <option value="<?php echo htmlspecialchars($race["rid"]); ?>">
                <?php echo htmlspecialchars($race["rid"]) . " – " . htmlspecialchars($race["datum"]) . " – " . htmlspecialchars($race["ort"]); ?>
            </option>
        <?php endforeach; ?>
    </select>
    &nbsp;
    <input type="submit" name="copy" value="Anmeldungen kopieren">
</form>

<!-- Driver selection table-->
<?php if (isset($_POST["proceed"]) || isset($_POST["register"])): ?>
    <br>
    <h2>Fahrer anmelden für Rennen <?php echo htmlspecialchars($selected_rid); ?></h2>

   
    <form method="post" action="">
        <input type="hidden" name="rid" value="<?php echo htmlspecialchars($selected_rid); ?>">
        <input type="hidden" name="count" value="<?php echo $selected_count; ?>">

        <table border="1" cellpadding="8">
            <tr>
                <th>Startnummer</th>
                <th>Fahrer</th>
            </tr>
            <!-- One dropdown row per driver slot -->
            <?php for ($i = 0; $i < $selected_count; $i++): ?>
                <tr>
                    <td><?php echo ($i + 1); ?></td>
                    <td>
                        <select name="driver_<?php echo $i; ?>">
                            <?php foreach ($drivers as $driver): ?>
                                <option value="<?php echo htmlspecialchars($driver["mid"]); ?>">
                                    <?php echo htmlspecialchars($driver["mid"]); ?> - <?php echo htmlspecialchars($driver["name"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endfor; ?>
        </table>

        <br>
        <input type="submit" name="register" value="Speichern">
    </form>
<?php endif; ?>
<?php $db->close(); ?>
