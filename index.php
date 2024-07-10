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
$province_promotion_status = [];

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

        // Province promotion status
        foreach ($markers['STATION'] as $station) {
            if ($station['id'] == $promotion['station_id']) {
                $province = $station['province'];
                if (!isset($province_promotion_status[$province])) {
                    $province_promotion_status[$province] = ['active' => 0, 'expired' => 0];
                }
                $current_time = new DateTime('now', new DateTimeZone('Asia/Phnom_Penh'));
                $end_time = new DateTime($promo['end_time']);
                if ($end_time < $current_time) {
                    $province_promotion_status[$province]['expired']++;
                } else {
                    $province_promotion_status[$province]['active']++;
                }
                break;
            }
        }
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

// Prepare data for countdowns
$promotion_end_times = [];
foreach ($combined_data as $promotion) {
    foreach ($promotion['promotions'] as $promo) {
        $promotion_end_times[$promo['promotion_id']] = $promo['end_time'];
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
$promotion_end_times_json = json_encode($promotion_end_times);

// Data for province promotions chart
$province_labels = json_encode(array_keys($province_promotion_status));
$province_active_counts = json_encode(array_column($province_promotion_status, 'active'));
$province_expired_counts = json_encode(array_column($province_promotion_status, 'expired'));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
        }

        #wrapper {
            display: flex;
            height: 100vh;
        }

        #sidebar-wrapper {
            width: 250px;
            background-color: #343a40;
            color: white;
            transition: width 0.3s ease;
        }

        #sidebar-wrapper.toggled {
            width: 60px;
        }

        .sidebar-heading {
            padding: 20px;
            font-size: 1.25em;
            font-weight: bold;
            background: #007bff;
            text-align: center;
        }

        .sidebar-heading img {
            margin-right: 10px;
        }

        .list-group-item {
            border: none;
            color: white;
            background-color: #343a40;
            transition: background-color 0.3s ease;
        }

        .list-group-item:hover {
            background-color: #495057;
        }

        .list-group-item-action {
            color: white;
        }

        #page-content-wrapper {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            transition: margin-left 0.3s ease;
        }

        #page-content-wrapper.toggled {
            margin-left: -190px;
        }

        .navbar {
            padding: 10px 15px;
            background-color: whitesmoke;
            color: white;
            box-shadow: 0 10px 16px -4px rgba(0, 0, 0, 0.6);
        }


        .navbar-brand {
            display: flex;
            align-items: center;
            color: white;
        }

        .navbar-brand img {
            margin-right: 10px;
        }

        .content-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s forwards;
        }

        .content-section:nth-child(even) {
            animation-delay: 0.2s;
        }

        .content-section:nth-child(odd) {
            animation-delay: 0.4s;
        }

        .content-section h2 {
            font-size: 1.5em;
            margin-bottom: 20px;
            color: #343a40;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #007bff;
            color: white;
            border-bottom: none;
            border-radius: 10px 10px 0 0;
        }

        .card-body {
            padding: 20px;
        }

        .countdown-timer {
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745;
        }

        .promotion-id {
            font-size: 1.2em;
            color:white;
        }

        .chart-container {
            position: relative;
            height: 40vh;
        }

        h1 {
            color: #343a40;
            font-size: 2em;
            margin-bottom: 20px;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <div class="sidebar-heading">
                <img src="path_to_your_logo.png" width="30" height="30" alt="Logo">
                Your Brand
            </div>
            <div class="list-group list-group-flush">
                <a href="index.php" class="list-group-item list-group-item-action">Overview</a>
                <a href="manage.php" class="list-group-item list-group-item-action">Manage</a>
                <a href="#" class="list-group-item list-group-item-action">Analytics</a>
                <a href="#" class="list-group-item list-group-item-action">Export</a>
            </div>
        </div>
        <!-- /#sidebar-wrapper -->

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light">
                <!-- <button class="btn btn-primary" id="menu-toggle">Toggle Menu</button> -->
                <a class="navbar-brand ml-3" href="#">
                    <img src="./pictures/logo_Station.png" width="200" height="auto" alt="Logo">
                </a>
            </nav>
            <div class="container-fluid mt-5">
                <h1>Promotions Dashboard</h1>

                <div class="row">
                    <div class="col-lg-4 col-md-6">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-header">EARNINGS (MONTHLY)</div>
                            <div class="card-body">
                                <h5 class="card-title">$40,000</h5>
                                <p class="card-text"><i class="fas fa-arrow-up"></i> 3.48% Since last month</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-header">SALES</div>
                            <div class="card-body">
                                <h5 class="card-title">650</h5>
                                <p class="card-text"><i class="fas fa-arrow-up"></i> 12% Since last year</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="card text-white bg-info mb-3">
                            <div class="card-header">NEW USERS</div>
                            <div class="card-body">
                                <h5 class="card-title">366</h5>
                                <p class="card-text"><i class="fas fa-arrow-up"></i> 20.4% Since last month</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-section">
                    <h2>Promotion Countdowns</h2>
                    <ul id="promotion-list" class="list-group">
                        <?php foreach ($promotion_end_times as $promotion_id => $end_time) : ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="promotion-id"><?php echo $promotion_id; ?></span>
                                <span class="countdown-timer" data-end-time="<?php echo $end_time; ?>"></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="row mt-4">
                    <div class="col-lg-6">
                        <div class="content-section">
                            <h2>Promotion Distribution</h2>
                            <div class="chart-container">
                                <canvas id="chart3"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="content-section">
                            <h2>Province Promotion Status</h2>
                            <div class="chart-container">
                                <canvas id="chart5"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // document.getElementById("menu-toggle").addEventListener("click", function () {
        //     document.getElementById("wrapper").classList.toggle("toggled");
        //     document.getElementById("sidebar-wrapper").classList.toggle("toggled");
        //     document.getElementById("page-content-wrapper").classList.toggle("toggled");
        // });

        const ctx3 = document.getElementById('chart3').getContext('2d');
        const ctx5 = document.getElementById('chart5').getContext('2d');

        const promotionDistribution = <?php echo $promotion_distribution_json; ?>;
        const promotionLabels = <?php echo $promotion_labels_json; ?>;
        const provinceLabels = <?php echo $province_labels; ?>;
        const provinceActiveCounts = <?php echo $province_active_counts; ?>;
        const provinceExpiredCounts = <?php echo $province_expired_counts; ?>;

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
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutBounce'
                    }
                }
            }
        });

        const chart5 = new Chart(ctx5, {
            type: 'bar',
            data: {
                labels: provinceLabels,
                datasets: [
                    {
                        label: 'Active Promotions',
                        data: provinceActiveCounts,
                        backgroundColor: 'rgba(75, 192, 192, 1)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Expired Promotions',
                        data: provinceExpiredCounts,
                        backgroundColor: 'rgba(255, 99, 132, 1)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutBounce'
                    }
                }
            }
        });

        // Countdown timer functionality
        const promotionEndTimes = <?php echo $promotion_end_times_json; ?>;

        function updateCountdowns() {
            const now = new Date().getTime();

            document.querySelectorAll('.countdown-timer').forEach(timer => {
                const endTime = new Date(timer.dataset.endTime).getTime();
                const distance = endTime - now;

                if (distance < 0) {
                    timer.innerHTML = "EXPIRED";
                    timer.style.color = 'red';
                    return;
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                timer.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
            });
        }

        setInterval(updateCountdowns, 1000);
        updateCountdowns(); // Initial call to display countdowns immediately
    </script>
</body>

</html>




