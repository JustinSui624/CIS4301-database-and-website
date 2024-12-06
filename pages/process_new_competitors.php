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

    // SQL Query to count competitions grouped by year and country
    $sql = "
        SELECT 
            c.CountryId AS country,
            c.year AS year,
            COUNT(DISTINCT r.personId) AS new_competitors_count
        FROM 
            Competitions c
        JOIN 
            Results r ON c.id = r.competitionId    
        WHERE 
            c.CountryId IN ('$country_list')
        GROUP BY 
            c.CountryId, c.year
        ORDER BY 
            c.year ASC, c.CountryId ASC
    ";

    // Prepare and execute SQL query
    $stid = oci_parse($conn, $sql);

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
        $chartData[] = $row['NEW_COMPETITORS_COUNT'];  // Competition count
        $years[] = $row['YEAR'];  // Year
        $countries[] = $row['COUNTRY'];  // Country
    }

    // Free the statement and close the connection
    oci_free_statement($stid);
    oci_close($conn);

    $uniqueYears = array_unique($years);
    sort($uniqueYears); 


} else {
    echo "Invalid request method.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Competitors by Country Over Time</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <h1>New Competitors by Country Over Time</h1>

    <canvas id="competitionsChart" width="400" height="200"></canvas>
    <script>
        var ctx = document.getElementById('competitionsChart').getContext('2d');
        var competitionsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($uniqueYears); ?>,  // X-axis: Year data from PHP
                datasets: [
                    <?php 
                    $uniqueCountries = array_unique($countries);  // Ensure we get only unique countries for multiple lines

                    function getRandomColor() {
                        // Generate a random RGB color
                        $r = rand(0, 255);
                        $g = rand(0, 255);
                        $b = rand(0, 255);
                        return "rgba($r, $g, $b, 1)";  // Return color in RGBA format
                    }

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
                        borderColor: '" . getRandomColor() . "',
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
                            text: 'Number of New Competitors'
                        }
                    }
                }
            }
        });
    </script>

</body>
</html>