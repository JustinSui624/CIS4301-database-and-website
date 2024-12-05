<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trend = $_POST['trend'];

    switch ($trend) {
        case 'podium_averages':
            header('Location: podium_averages.html');
            break;
        case 'new_competitors_by_country':
            header('Location: new_competitors_by_country.html');
            break;
        case 'record_streaks_by_competition':
            header('Location: record_streaks_by_competition.html');
            break;
        case 'non_podium_results':
            header('Location: non_podium_results.html');
            break;
        case 'competitions_by_country':
            header('Location: competitions_by_country.html');
            break;
        default:
            echo "Invalid selection!";
    }
    exit;
}
?>
