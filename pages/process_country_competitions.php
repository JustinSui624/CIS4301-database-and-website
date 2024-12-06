<?php
// Database connection
 $conn = oci_connect($username = 'j.sui',
                          $password = 'Q38vOyjmt1DQngytzjMzmMnN',
                          $connection_string = '//oracle.cise.ufl.edu/orcl');

    if (!$conn) {
        $e = oci_error();
        echo "Connection failed: " . $e['message'];
        exit;
    }

// Get selected countries from the form
$countries = $_POST['countries'] ?? [];
if (empty($countries)) {
    echo json_encode(['error' => 'No countries selected']);
    exit;
}

// Sanitize input
$countries = array_map([$conn, 'real_escape_string'], $countries);
$countries_list = "'" . implode("','", $countries) . "'";

// Query database
$sql = "
    SELECT 
        year, 
        countryid, 
        COUNT(*) AS competition_count
    FROM competitions
    WHERE countryid IN ($countries_list)
    GROUP BY year, countryid
    ORDER BY year ASC;
";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$conn->close();

// Return data as JSON
echo json_encode($data);
?>
