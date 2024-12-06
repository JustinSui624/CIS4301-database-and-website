<?php

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Oracle database connection details
$username = 'j.sui';
$password = 'Q38vOyjmt1DQngytzjMzmMnN';
$connection_string = '//oracle.cise.ufl.edu/orcl';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $start_year = $_POST['start_year'];
    $end_year = $_POST['end_year'];

    // Validate years
    if (!is_numeric($start_year) || !is_numeric($end_year) || $start_year > $end_year) {
        echo "Invalid year range.";
        exit;
    }

    // Connect to Oracle database
    $conn = oci_connect($username, $password, $connection_string);

    if (!$conn) {
        $e = oci_error();
        echo "Connection failed: " . $e['message'];
        exit;
    }

    // SQL Query to fetch personal record data grouped by competition and year
    $sql = "
SELECT 
    c.NAME AS competition_name,
    c.YEAR AS year,
    COUNT(*) AS record_count
FROM 
    competitions c
JOIN 
    results r ON c.ID = r.competitionId
WHERE 
    r.best = (
        SELECT MIN(r2.best)
        FROM results r2
        WHERE r2.personId = r.personId
          AND r2.eventId = r.eventId
    )
    AND c.YEAR BETWEEN :start_year AND :end_year
GROUP BY 
    c.NAME, c.YEAR
ORDER BY 
    c.NAME ASC, c.YEAR ASC
    ";

    // Prepare and execute SQL query
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ':start_year', $start_year);
    oci_bind_by_name($stid, ':end_year', $end_year);

    if (!oci_execute($stid)) {
        $e = oci_error($stid);
        echo "Error executing query: " . $e['message'];
        exit;
    }

    // Process results to calculate streaks
    $streaks = [];
    $chartData = [];
    $years = [];
    $competitions = [];
    
    while ($row = oci_fetch_assoc($stid)) {
        $competition_name = $row['COMPETITION_NAME'];
        $year = $row['YEAR'];
        $record_count = $row['RECORD_COUNT'];

        // Add to streaks data
        if (!isset($streaks[$competition_name])) {
            $streaks[$competition_name] = ['streak' => 0, 'previous_year' => null];
        }

        if ($streaks[$competition_name]['previous_year'] !== null &&
            $streaks[$competition_name]['previous_year'] + 1 == $year) {
            // Increment streak
            $streaks[$competition_name]['streak']++;
        } else {
            // Reset streak
            $streaks[$competition_name]['streak'] = 1;
        }

        // Update previous year
        $streaks[$competition_name]['previous_year'] = $year;

        // Collect data for the chart
        $chartData[] = $record_count;
        $years[] = $year;
        $competitions[] = $competition_name;
    }

    // Free the statement and close the connection
    oci_free_statement($stid);
    oci_close($conn);

    // Sort streaks to find the top 10 competitions
    uasort($streaks, function ($a, $b) {
        return $b['streak'] - $a['streak'];
    });

    $topCompetitions = array_slice($streaks, 0, 10, true);

    // Filter chart data to only include top 10 competitions
    $filteredChartData = [];
    $filteredYears = [];
    $filteredCompetitions = [];

    foreach ($competitions as $index => $competition) {
        if (isset($topCompetitions[$competition])) {
            $filteredChartData[] = $chartData[$index];
            $filteredYears[] = $years[$index];
            $filteredCompetitions[] = $competition;
        }
    }
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
    <title>Top Competitions Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <h1>Top Competitions for Consecutive Personal Records</h1>

    <canvas id="competitionChart" width="400" height="200"></canvas>
    <script>
        var ctx = document.getElementById('competitionChart').getContext('2d');

        var competitionChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_unique($filteredYears)); ?>, // X-axis: Unique years
                datasets: [
                    <?php 
                    $uniqueCompetitions = array_unique($filteredCompetitions); // Get unique competition names

                    function getRandomColor() {
                        $r = rand(0, 255);
                        $g = rand(0, 255);
                        $b = rand(0, 255);
                        return "rgba($r, $g, $b, 1)";
                    }

                    foreach ($uniqueCompetitions as $competition) {
                        echo "{ 
                            label: '$competition',
                            data: [";
                        
                        // Prepare data for each competition
                        $competitionData = [];
                        foreach ($filteredYears as $index => $year) {
                            if ($filteredCompetitions[$index] == $competition) {
                                $competitionData[] = $filteredChartData[$index];
                            }
                        }

                        echo implode(',', $competitionData) . "],
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
                            text: 'Personal Record Count'
                        }
                    }
                }
            }
        });
    </script>

</body>
</html>

