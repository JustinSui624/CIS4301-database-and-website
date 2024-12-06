<?php
// Enable error reporting for debugging purposes
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Oracle database connection details
$username = 'j.sui';
$password = 'Q38vOyjmt1DQngytzjMzmMnN';
$connection_string = '//oracle.cise.ufl.edu/orcl';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $event_id = $_POST['event'];
    $countries = $_POST['countries'];  // Array of selected countries

    // Ensure countries are selected
    if (empty($countries)) {
        echo "Please select at least one country.";
        exit;
    }

    // Convert the array of countries into a comma-separated list for SQL query
    $country_list = implode("','", $countries);  // Ensure it's properly formatted as 'country1', 'country2'

    // Connect to Oracle database
    $conn = oci_connect($username, $password, $connection_string);

    if (!$conn) {
        $e = oci_error();
        echo "Connection failed: " . $e['message'];
        exit;
    }

    // SQL Query to calculate non-podium averages (Positions 4 and below results)
    $sql = "
        SELECT 
            c.CountryId AS country,
            c.year AS year,
            AVG(r.best) AS non_podium_average
        FROM 
            Competitions c
        JOIN 
            Results r ON c.id = r.competitionId
        WHERE 
            r.eventId = :event_id
            AND c.CountryId IN ('$country_list')
        AND r.pos > 3  -- Only considers the positions 4 and below
        GROUP BY 
            c.CountryId, c.year
        ORDER BY 
            c.year DESC, non_podium_average ASC
    ";

    // Prepare and execute SQL query
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ':event_id', $event_id);

    // Execute the query
    if (!oci_execute($stid)) {
        $e = oci_error($stid);
        echo "Error executing query: " . $e['message'];
        exit;
    }

    // Prepare data for the chart
    $chartData = [];
    $years = [];
    $countries = [];
    while ($row = oci_fetch_assoc($stid)) {
        $chartData[] = $row['NON_PODIUM_AVERAGE'];  // Podium average
        $years[] = $row['YEAR'];  // Year
        $countries[] = $row['COUNTRY'];  // Country
    }

    // Free the statement and close the connection
    oci_free_statement($stid);
    oci_close($conn);
} else {
    echo "Invalid request method.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Non Podium Averages Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <h1>Non Podium Averages by Country</h1>

    <canvas id="podiumChart" width="400" height="200"></canvas>
    <script>
        var ctx = document.getElementById('podiumChart').getContext('2d');
        var podiumChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($years); ?>,  // X-axis: Year data from PHP
                datasets: [
                    <?php 
                    $uniqueCountries = array_unique($countries);  // Ensure we get only unique countries for multiple lines
                    foreach ($uniqueCountries as $country) {
                        echo "{ 
                            label: '$country',
                            data: [";
                        $countryData = [];
                        foreach ($countries as $key => $c) {
                            if ($c == $country) {
                                $countryData[] = $chartData[$key];  // Only push data for the current country
                            }
                        }
                        echo implode(',', $countryData) . "],
                        borderColor: 'rgba(75, 192, 192, 1)',
                        fill: false
                    },";
                    } 
                    ?>
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Year'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Podium Average'
                        }
                    }
                }
            }
        });
    </script>

</body>
</html>