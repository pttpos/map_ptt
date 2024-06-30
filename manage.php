<?php
date_default_timezone_set('Asia/Phnom_Penh');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $promotions = json_decode(file_get_contents('./data/promotions.json'), true);

    if (isset($_POST['delete_all_promotions'])) {
        $selected_promotions = $_POST['selected_promotions'] ?? [];
        foreach ($promotions['PROMOTIONS'] as &$station) {
            $station['promotions'] = array_filter($station['promotions'], function($promo) use ($selected_promotions) {
                return !in_array($promo['promotion_id'], $selected_promotions);
            });
        }
        // Save the updated data back to the JSON file
        file_put_contents('./data/promotions.json', json_encode($promotions, JSON_PRETTY_PRINT));
        header('Location: manage.php');
        exit();
    }

    if (isset($_POST['clear_promotions'])) {
        $selected_promotion = $_POST['selected_promotion'];
        foreach ($promotions['PROMOTIONS'] as &$station) {
            $station['promotions'] = array_filter($station['promotions'], function($promo) use ($selected_promotion) {
                return $promo['promotion_id'] !== $selected_promotion;
            });
        }
        // Save the updated data back to the JSON file
        file_put_contents('./data/promotions.json', json_encode($promotions, JSON_PRETTY_PRINT));
        header('Location: manage.php');
        exit();
    }

    // Handle form submission
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
    header('Location: manage.php');
    exit();
}

// Load promotions data
$promotions = json_decode(file_get_contents('./data/promotions.json'), true);

// Extract unique promotion IDs
$unique_promotions = [];
foreach ($promotions['PROMOTIONS'] as $promotion) {
    foreach ($promotion['promotions'] as $promo) {
        if (!in_array($promo['promotion_id'], $unique_promotions)) {
            $unique_promotions[] = $promo['promotion_id'];
        }
    }
}

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

// Search functionality
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Filter promotions based on the search query
$filtered_promotions = array_filter($combined_data, function($promotion) use ($search_query) {
    return empty($search_query) || stripos($promotion['title'], $search_query) !== false || 
           array_reduce($promotion['promotions'], function($carry, $promo) use ($search_query) {
               return $carry || stripos($promo['promotion_id'], $search_query) !== false;
           }, false);
});

// Pagination settings
$per_page = 10; // Number of stations per page
$total_stations = count($filtered_promotions);
$total_pages = ceil($total_stations / $per_page);

// Get current page from URL parameter, default is 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, min($total_pages, $page)); // Ensure the page number is within bounds

// Calculate the offset for the slice
$offset = ($page - 1) * $per_page;

// Slice the promotions array to get only the current page data
$current_page_promotions = array_slice($filtered_promotions, $offset, $per_page);

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

            <!-- Search Form -->
            <form class="form-inline mb-4" id="searchForm">
                <input class="form-control mr-2" type="text" id="search" placeholder="Search by Station Title or Promotion ID" value="<?php echo htmlspecialchars($search_query); ?>">
            </form>

            <!-- Add Promotion to All Stations Form -->
            <form action="index.php" method="post" class="mb-4">
                <input type="hidden" name="action" value="add_to_all">
                <div class="form-group">
                    <label for="promotion_id">Promotion ID:</label>
                    <input type="text" class="form-control" name="promotion_id" required>
                </div>
                <div class="form-group">
                    <label for="end_time">End Time:</label>
                    <input type="datetime-local" class="form-control" name="end_time" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Promotion to All Stations</button>
            </form>
   <!-- Clear Specific Promotion Form -->
            <form action="manage.php" method="post" class="mb-4">
                <input type="hidden" name="clear_promotions" value="1">
                <div class="form-group">
                    <label for="selected_promotion">Select Promotion to Clear:</label>
                    <select class="form-control" name="selected_promotion" required>
                        <?php foreach ($unique_promotions as $promotion_id): ?>
                            <option value="<?php echo $promotion_id; ?>"><?php echo $promotion_id; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-danger">Clear Selected Promotion</button>
            </form>

            <!-- Clear All Selected Promotions Form -->
            <form action="manage.php" method="post" class="mb-4">
                <input type="hidden" name="delete_all_promotions" value="1">
                <div class="form-group">
                    <label for="selected_promotions">Select Promotions to Clear:</label>
                    <?php foreach ($unique_promotions as $promotion_id): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="selected_promotions[]" value="<?php echo $promotion_id; ?>" id="promo_<?php echo $promotion_id; ?>">
                            <label class="form-check-label" for="promo_<?php echo $promotion_id; ?>">
                                <?php echo $promotion_id; ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-danger">Clear Selected Promotions</button>
            </form>

            <div id="results">
                <?php foreach ($current_page_promotions as $promotion): ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <strong><?php echo $promotion['title']; ?> (Station ID: <?php echo $promotion['station_id']; ?>)</strong>
                            <br>
                            <small><?php echo $promotion['address']; ?></small>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($promotion['promotions'])): ?>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Promotion ID</th>
                                            <th>End Time</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($promotion['promotions'] as $promo): ?>
                                            <tr data-promo-id="<?php echo $promo['promotion_id']; ?>" data-end-time="<?php echo $promo['end_time']; ?>">
                                                <form action="index.php" method="post" class="form-inline">
                                                    <input type="hidden" name="station_id" value="<?php echo $promotion['station_id']; ?>">
                                                    <input type="hidden" name="promotion_id" value="<?php echo $promo['promotion_id']; ?>">
                                                    <input type="hidden" name="action" value="edit">
                                                    <td>
                                                        <input type="text" class="form-control" name="new_promotion_id" value="<?php echo $promo['promotion_id']; ?>" required>
                                                    </td>
                                                    <td>
                                                        <input type="datetime-local" class="form-control" name="end_time" value="<?php echo date('Y-m-d\TH:i', strtotime($promo['end_time'])); ?>" required>
                                                    </td>
                                                    <td>
                                                        <button type="submit" class="btn btn-primary">Update</button>
                                                        <button type="button" class="btn btn-danger ml-2" onclick="deletePromotion('<?php echo $promotion['station_id']; ?>', '<?php echo $promo['promotion_id']; ?>')">Delete</button>
                                                    </td>
                                                </form>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No promotions available.</p>
                            <?php endif; ?>
                            <form action="index.php" method="post" class="mt-4">
                                <input type="hidden" name="station_id" value="<?php echo $promotion['station_id']; ?>">
                                <input type="hidden" name="action" value="add">
                                <div class="form-group">
                                    <label for="promotion_id">Promotion ID:</label>
                                    <input type="text" class="form-control" name="promotion_id" required>
                                </div>
                                <div class="form-group">
                                    <label for="end_time">End Time:</label>
                                    <input type="datetime-local" class="form-control" name="end_time" required>
                                </div>
                                <button type="submit" class="btn btn-success">Add Promotion</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination Controls -->
                <nav aria-label="Page navigation example">
                    <ul class="pagination">
                        <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_query); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                                <span class="sr-only">Previous</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php if ($page == $i) echo 'active'; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>"><?php echo $i; ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_query); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                                <span class="sr-only">Next</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#search').on('input', function() {
        var searchQuery = $(this).val();
        $.get('index.php', { search: searchQuery }, function(data) {
            $('#results').html($(data).find('#results').html());
        });
    });

    function checkExpiredPromotions() {
        var now = new Date().toISOString();
        $('tr[data-end-time]').each(function() {
            var endTime = $(this).data('end-time');
            if (now >= endTime) {
                $(this).remove();
            }
        });
    }

    setInterval(checkExpiredPromotions, 60000); // Check every minute
});

function deletePromotion(stationId, promotionId) {
    if (confirm('Are you sure you want to delete this promotion?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php';
        
        const stationIdInput = document.createElement('input');
        stationIdInput.type = 'hidden';
        stationIdInput.name = 'station_id';
        stationIdInput.value = stationId;
        form.appendChild(stationIdInput);
        
        const promotionIdInput = document.createElement('input');
        promotionIdInput.type = 'hidden';
        promotionIdInput.name = 'promotion_id';
        promotionIdInput.value = promotionId;
        form.appendChild(promotionIdInput);
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
// Load promotions data
$promotions = json_decode(file_get_contents('./data/promotions.json'), true);
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

// Convert data for use in JS
$station_titles_json = json_encode($station_titles);
$promotion_counts_json = json_encode($promotion_counts);
$monthly_promotions_json = json_encode(array_values($monthly_promotions));
$monthly_labels_json = json_encode(array_keys($monthly_promotions));
$promotion_distribution_json = json_encode(array_values($promotion_distribution));
$promotion_labels_json = json_encode(array_keys($promotion_distribution));
?>
</body>
</html>

