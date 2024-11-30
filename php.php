<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $query = $_POST['query'];

    // Database connection details
    $db_username = 'j.sui';
    $db_password = 'Q38vOyjmt1DQngytzjMzmMnN';
    $db_connection_string = 'oracle.cise.ufl.edu/orcl';

    // Connect to Oracle database
    $conn = oci_connect($db_username, $db_password, $db_connection_string);
    if (!$conn) {
        $e = oci_error();
        echo "Connection failed: " . $e['message'];
        exit;
    }

    // Execute the query
    $stid = oci_parse($conn, $query);
    if (!oci_execute($stid)) {
        $e = oci_error($stid);
        echo "Query failed: " . $e['message'];
        exit;
    }

    // Fetch and display the results
    echo "<table border='1'>";
    while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
        echo "<tr>";
        foreach ($row as $item) {
            echo "<td>" . ($item !== null ? htmlentities($item, ENT_QUOTES) : "&nbsp;") . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";

    // Free resources and close connection
    oci_free_statement($stid);
    oci_close($conn);
}
?>
