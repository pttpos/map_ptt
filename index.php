<?php
date_default_timezone_set('Asia/Phnom_Penh');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission
    $promotions = json_decode(file_get_contents('./data/promotions.json'), true);

    // Retrieve data from the form
    $station_id = $_POST['station_id'] ?? null;
    $promotion_id = $_POST['promotion_id'];
    $new_promotion_id = $_POST['new_promotion_id'] ?? '';
    $end_time = $_POST['end_time'];
    $action = $_POST['action'];

    // Convert end_time to Cambodia time zone
    $end_time = (new DateTime($end_time, new DateTimeZone('Asia/Phnom_Penh')))->format('Y-m-d\TH:i:s\Z');

    if ($action === 'add_to_all') {
        // Add promotion to all stations
        foreach ($promotions['PROMOTIONS'] as &$station) {
            $station['promotions'][] = [
                'promotion_id' => $promotion_id,
                'end_time' => $end_time
            ];
        }
    } else {
        // Find the station in the JSON data
        foreach ($promotions['PROMOTIONS'] as &$station) {
            if ($station['station_id'] == $station_id) {
                if ($action == 'add') {
                    $station['promotions'][] = [
                        'promotion_id' => $promotion_id,
                        'end_time' => $end_time
                    ];
                } elseif ($action == 'edit') {
                    foreach ($station['promotions'] as &$promotion) {
                        if ($promotion['promotion_id'] == $promotion_id) {
                            $promotion['promotion_id'] = $new_promotion_id;
                            $promotion['end_time'] = $end_time;
                        }
                    }
                } elseif ($action == 'delete') {
                    foreach ($station['promotions'] as $key => $promotion) {
                        if ($promotion['promotion_id'] == $promotion_id) {
                            unset($station['promotions'][$key]);
                        }
                    }
                }
                break;
            }
        }
    }

    // Save the updated data back to the JSON file
    file_put_contents('./data/promotions.json', json_encode($promotions, JSON_PRETTY_PRINT));

    header('Location: index.php');
    exit();
}

// Load promotions data
$promotions = json_decode(file_get_contents('./data/promotions.json'), true);

// Load markers data
$markers = json_decode(file_get_contents('./data/markers.json'), true);

// Combine promotions with markers data based on station ID
$combined_data = [];
foreach ($promotions['PROMOTIONS'] as $promotion) {
    foreach ($markers['STATION'] as $station) {
        if ($station['id'] == $promotion['station_id']) {
            $promotion['title'] = $station['title'];
            $promotion['address'] = $station['address'];
            $combined_data[] = $promotion;
            break;
        }
    }
}

// Prepare data for charts
$station_titles = [];
$promotion_counts = [];
$monthly_promotions = [];
$promotion_distribution = [];

foreach ($combined_data as $promotion) {
    $station_titles[] = $promotion['title'];
    $promotion_counts[] = count($promotion['promotions']);
    
    foreach ($promotion['promotions'] as $promo) {
        $month = date('F', strtotime($promo['end_time']));
        if (!isset($monthly_promotions[$month])) {
            $monthly_promotions[$month] = 0;
        }
        $monthly_promotions[$month]++;
        
        if (!isset($promotion_distribution[$promo['promotion_id']])) {
            $promotion_distribution[$promo['promotion_id']] = 0;
        }
        $promotion_distribution[$promo['promotion_id']]++;
    }
}

// Process data for expiration status
$active_count = 0;
$expired_count = 0;
$current_time = new DateTime('now', new DateTimeZone('Asia/Phnom_Penh'));

foreach ($combined_data as $promotion) {
    foreach ($promotion['promotions'] as $promo) {
        $end_time = new DateTime($promo['end_time']);
        if ($end_time < $current_time) {
            $expired_count++;
        } else {
            $active_count++;
        }
    }
}

// Convert data for use in JS
$station_titles_json = json_encode($station_titles);
$promotion_counts_json = json_encode($promotion_counts);
$monthly_promotions_json = json_encode(array_values($monthly_promotions));
$monthly_labels_json = json_encode(array_keys($monthly_promotions));
$promotion_distribution_json = json_encode(array_values($promotion_distribution));
$promotion_labels_json = json_encode(array_keys($promotion_distribution));
$expiration_status_json = json_encode([$active_count, $expired_count]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div class="bg-light border-right" id="sidebar-wrapper">
        <div class="sidebar-heading">Dashboard </div>
        <div class="list-group list-group-flush">
            <a href="index.php" class="list-group-item list-group-item-action bg-light">Overview</a>
            <a href="manage.php" class="list-group-item list-group-item-action bg-light">Manage</a>
            <a href="#" class="list-group-item list-group-item-action bg-light">Analytics</a>
            <a href="#" class="list-group-item list-group-item-action bg-light">Export</a>
        </div>
    </div>
    <!-- /#sidebar-wrapper -->

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
            <button class="btn btn-primary" id="menu-toggle">Toggle Menu</button>
        </nav>

        <div class="container-fluid">
            <h1 class="mt-4">Promotions Dashboard</h1>
            
            <div class="row">
                <div class="col-lg-6">
                    <canvas id="chart1"></canvas>
                </div>
                <div class="col-lg-6">
                    <canvas id="chart2"></canvas>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-lg-6">
                    <canvas id="chart3"></canvas>
                </div>
                <div class="col-lg-6">
                    <canvas id="chart4"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById("menu-toggle").addEventListener("click", function() {
    document.getElementById("wrapper").classList.toggle("toggled");
});

const ctx1 = document.getElementById('chart1').getContext('2d');
const ctx2 = document.getElementById('chart2').getContext('2d');
const ctx3 = document.getElementById('chart3').getContext('2d');
const ctx4 = document.getElementById('chart4').getContext('2d');

const stationTitles = <?php echo $station_titles_json; ?>;
const promotionCounts = <?php echo $promotion_counts_json; ?>;
const monthlyPromotions = <?php echo $monthly_promotions_json; ?>;
const monthlyLabels = <?php echo $monthly_labels_json; ?>;
const promotionDistribution = <?php echo $promotion_distribution_json; ?>;
const promotionLabels = <?php echo $promotion_labels_json; ?>;
const expirationStatus = <?php echo $expiration_status_json; ?>;

const chart1 = new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: stationTitles,
        datasets: [{
            label: '# of Promotions',
            data: promotionCounts,
            backgroundColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                display: true
            }
        }
    }
});

const chart2 = new Chart(ctx2, {
    type: 'line',
    data: {
        labels: monthlyLabels,
        datasets: [{
            label: 'Promotions Over Time',
            data: monthlyPromotions,
            backgroundColor: 'rgba(153, 102, 255, 1)',
            borderColor: 'rgba(153, 102, 255, 1)',
            borderWidth: 1,
            tension: 0.4,
            fill: false,
            pointBackgroundColor: 'rgba(255, 159, 64, 1)'
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                display: true
            }
        }
    }
});

const chart3 = new Chart(ctx3, {
    type: 'pie',
    data: {
        labels: promotionLabels,
        datasets: [{
            label: 'Promotion Distribution',
            data: promotionDistribution,
            backgroundColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

const chart4 = new Chart(ctx4, {
    type: 'pie',
    data: {
        labels: ['Active Promotions', 'Expired Promotions'],
        datasets: [{
            label: 'Promotion Expiration Status',
            data: expirationStatus,
            backgroundColor: [
                'rgba(75, 192, 192, 1)',
                'rgba(255, 99, 132, 1)'
            ],
            borderColor: [
                'rgba(75, 192, 192, 1)',
                'rgba(255, 99, 132, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

</body>
</html>
