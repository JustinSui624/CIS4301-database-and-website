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

    // SQL Query to calculate podium averages (top 3 results)
    $sql = "
        SELECT 
            c.CountryId AS country,
            c.year AS year,
            AVG(r.best) AS podium_average
        FROM 
            Competitions c
        JOIN 
            Results r ON c.id = r.competitionId
        WHERE 
            r.eventId = :event_id
            AND c.CountryId IN ('$country_list')
        AND r.pos <= 3  -- Only consider top 3 positions
        GROUP BY 
            c.CountryId, c.year
        ORDER BY 
            c.year DESC, podium_average ASC
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
    
    // Fetch results and prepare for charting
    while ($row = oci_fetch_assoc($stid)) {
        $chartData[] = $row['PODIUM_AVERAGE'];  // Podium average
        $years[] = $row['YEAR'];  // Year
        $countries[] = $row['COUNTRY'];  // Country
    }

    // Get unique years for the x-axis
    $uniqueYears = array_unique($years);
    sort($uniqueYears); // Sorting years in ascending order

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
    <title>Podium Averages Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <h1>Podium Averages by Country</h1>

    <canvas id="podiumChart" width="400" height="200"></canvas>
    <script>
        var ctx = document.getElementById('podiumChart').getContext('2d');
        
        var podiumChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($uniqueYears); ?>,  // X-axis: Unique years
                datasets: [
                    <?php 
                    $uniqueCountries = array_unique($countries);  // Get unique countries
                    foreach ($uniqueCountries as $country) {
                        echo "{ 
                            label: '$country',
                            data: [";
                        
                        // Prepare data for the country
                        $countryData = [];
                        foreach ($years as $index => $year) {
                            if ($countries[$index] == $country) {
                                // Push corresponding podium averages for each country
                                $countryData[] = $chartData[$index];
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

    <!-- Display the query results in a table -->
    <h2>Query Results</h2>
    <table border="1">
        <tr>
            <th>Country</th>
            <th>Year</th>
            <th>Podium Average</th>
        </tr>
        <?php foreach ($years as $index => $year): ?>
            <tr>
                <td><?php echo htmlspecialchars($countries[$index]); ?></td>
                <td><?php echo htmlspecialchars($year); ?></td>
                <td><?php echo htmlspecialchars($chartData[$index]); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

</body>
</html>
