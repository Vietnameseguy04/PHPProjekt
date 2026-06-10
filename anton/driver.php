
<!-- Anton Nguyen -->
<!-- Displays all drivers of the logged-in team in a table with links for editing and deleting.-->

<h1>Fahrer verwalten</h1>

<a href="driver_form.php">Neuer Fahrer</a>
<br><br>

<table border="1" cellpadding="8">
    <tr>
        <th>Name</th>
        <th>Mitarbeiter-ID</th>
        <th>Adresse</th>
        <th>Telefon</th>
        <th>Aktion</th>
    </tr>

<?php

$db = new mysqli("localhost", "gruppe19", "{yI)X2)vN7w1", "gruppe19");

if (mysqli_connect_error()) {
    die("Fehler: " . mysqli_connect_error());
}

// Only fetch drivers belonging to the currently logged-in team
$fetchDrivers = $db->prepare("SELECT MID, TName, Name, Strasse, HNR, PLZ, Ort, TelNr FROM Fahrer WHERE TName = ?");
$fetchDrivers->bind_param("s", $_SESSION["tname"]);
$fetchDrivers->execute();
$driversResult = $fetchDrivers->get_result();
$fetchDrivers->close();

while ($row = $driversResult->fetch_assoc()) {
   
    $address = htmlspecialchars($row["Strasse"]) . " " . htmlspecialchars($row["HNR"]) . ", " . htmlspecialchars($row["PLZ"]) . " " . htmlspecialchars($row["Ort"]);

    echo "<tr>";
    echo "<td>" . htmlspecialchars($row["Name"])  . "</td>";
    echo "<td>" . htmlspecialchars($row["MID"])   . "</td>";
    echo "<td>" . $address                        . "</td>";
    echo "<td>" . htmlspecialchars($row["TelNr"]) . "</td>";
    echo "<td>
            <a href='driver_form.php?id=" . $row["MID"] . "&name=" . $row["Name"] . "'>Bearbeiten</a> |
            <a href='driver_delete.php?id=" . $row["MID"] . "&team=" . $row["TName"] . "'>Löschen</a>
          </td>";
    echo "</tr>";
}

$db->close();

?>

</table>