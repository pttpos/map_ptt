<?php
date_default_timezone_set('Asia/Phnom_Penh');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $promotions = json_decode(file_get_contents('./data/promotions.json'), true);
    $markers = json_decode(file_get_contents('./data/markers.json'), true);
    $messages = [];

    if (isset($_POST['delete_all_promotions'])) {
        $selected_promotions = $_POST['selected_promotions'] ?? [];
        foreach ($promotions['PROMOTIONS'] as &$station) {
            $station['promotions'] = array_filter($station['promotions'], function ($promo) use ($selected_promotions) {
                return !in_array($promo['promotion_id'], $selected_promotions);
            });
        }
        file_put_contents('./data/promotions.json', json_encode($promotions, JSON_PRETTY_PRINT));
        header('Location: manage.php');
        exit();
    }

    if (isset($_POST['clear_promotions'])) {
        $selected_promotion = $_POST['selected_promotion'];
        foreach ($promotions['PROMOTIONS'] as &$station) {
            $station['promotions'] = array_filter($station['promotions'], function ($promo) use ($selected_promotion) {
                return $promo['promotion_id'] !== $selected_promotion;
            });
        }
        file_put_contents('./data/promotions.json', json_encode($promotions, JSON_PRETTY_PRINT));
        header('Location: manage.php');
        exit();
    }

    if (isset($_POST['clear_all_expired'])) {
        $current_time = new DateTime('now', new DateTimeZone('Asia/Phnom_Penh'));
        foreach ($promotions['PROMOTIONS'] as &$station) {
            $station['promotions'] = array_filter($station['promotions'], function ($promo) use ($current_time) {
                $end_time = new DateTime($promo['end_time']);
                return $end_time >= $current_time;
            });
        }
        file_put_contents('./data/promotions.json', json_encode($promotions, JSON_PRETTY_PRINT));
        header('Location: manage.php');
        exit();
    }

    $station_id = $_POST['station_id'] ?? null;
    $promotion_id = $_POST['promotion_id'];
    $new_promotion_id = $_POST['new_promotion_id'] ?? '';
    $end_time = $_POST['end_time'];
    $action = $_POST['action'];

    $end_time = (new DateTime($end_time, new DateTimeZone('Asia/Phnom_Penh')))->format('Y-m-d\TH:i:s\Z');
    if ($action === 'add_to_all') {
        $selected_provinces = !empty($_POST['provinces']) ? explode(',', $_POST['provinces']) : [];

        foreach ($promotions['PROMOTIONS'] as &$station) {
            $station_id = $station['station_id'];
            foreach ($markers['STATION'] as $marker) {
                if ($marker['id'] == $station_id && (empty($selected_provinces) || in_array($marker['province'], $selected_provinces))) {
                    $already_exists = false;
                    foreach ($station['promotions'] as $promo) {
                        if ($promo['promotion_id'] == $promotion_id) {
                            $already_exists = true;
                            break;
                        }
                    }
                    if (!$already_exists) {
                        $station['promotions'][] = [
                            'promotion_id' => $promotion_id,
                            'end_time' => $end_time
                        ];
                    } else if (!empty($selected_provinces)) {
                        echo "<script>alert('Promotion $promotion_id already exists in province: {$marker['province']}');</script>";
                    }
                }
            }
        }
    } else {
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

    file_put_contents('./data/promotions.json', json_encode($promotions, JSON_PRETTY_PRINT));

    if (empty($messages)) {
        header('Location: manage.php');
        exit();
    }
}

$promotions = json_decode(file_get_contents('./data/promotions.json'), true);

$unique_promotions = [];
foreach ($promotions['PROMOTIONS'] as $promotion) {
    foreach ($promotion['promotions'] as $promo) {
        if (!in_array($promo['promotion_id'], $unique_promotions)) {
            $unique_promotions[] = $promo['promotion_id'];
        }
    }
}

$markers = json_decode(file_get_contents('./data/markers.json'), true);

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

$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$selected_province = isset($_GET['province']) ? $_GET['province'] : '';

$filtered_promotions = array_filter($combined_data, function ($promotion) use ($search_query, $selected_province, $markers) {
    $matches_search_query = empty($search_query) || stripos($promotion['title'], $search_query) !== false ||
        array_reduce($promotion['promotions'], function ($carry, $promo) use ($search_query) {
            return $carry || stripos($promo['promotion_id'], $search_query) !== false;
        }, false);

    if (!empty($selected_province)) {
        foreach ($markers['STATION'] as $marker) {
            if ($marker['id'] == $promotion['station_id'] && $marker['province'] == $selected_province) {
                return $matches_search_query;
            }
        }
        return false;
    }

    return $matches_search_query;
});


$per_page = 10;
$total_stations = count($filtered_promotions);
$total_pages = ceil($total_stations / $per_page);

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, min($total_pages, $page));

$offset = ($page - 1) * $per_page;

$current_page_promotions = array_slice($filtered_promotions, $offset, $per_page);

$promotion_ids = json_decode(file_get_contents('./data/promotion_ids.json'), true);
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
<style>
    #sidebar-wrapper {
        min-height: 100vh;
        border-right: 1px solid #ddd;
    }

    .list-group-item {
        cursor: pointer;
    }

    .page-item.active .page-link {
        background-color: #007bff;
        border-color: #007bff;
    }

    .page-item.disabled .page-link {
        color: #6c757d;
    }

    .form-check-input:checked {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    .badge {
        display: inline-block;
        padding: 0.5em 0.75em;
        margin: 0.25em 0;
        font-size: 0.75em;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
    }

    .badge-secondary {
        background-color: #6c757d;
        color: #fff;
    }

    .remove-tag {
        margin-left: 0.5em;
    }
</style>

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
                <?php if (!empty($messages)) : ?>
                    <div class="alert alert-warning" role="alert">
                        <?php echo implode('<br>', $messages); ?>
                    </div>
                <?php endif; ?>
                <button class="btn btn-warning mb-4" id="checkExpiredPromotionsBtn">Check Expired Promotions</button>
                <!-- Form for Adding Promotion to All Stations -->
                <!-- Form for Adding Promotion to All Stations -->
                <form action="manage.php" method="post" class="mb-4 p-3 border rounded shadow-sm bg-light">
                    <input type="hidden" name="action" value="add_to_all">
                    <div class="form-group">
                        <label for="promotion_id">Promotion ID:</label>
                        <select class="form-select form-control" name="promotion_id" required>
                            <?php foreach ($promotion_ids as $promo) : ?>
                                <option value="<?php echo $promo['promotion_id']; ?>"><?php echo $promo['promotion_id']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="end_time">End Time:</label>
                        <input type="datetime-local" class="form-control" name="end_time" required>
                    </div>
                    <div class="form-group">
                        <label for="province">Provinces:</label>
                        <select id="province-select" class="form-control">
                        <option value="">Select a province</option>
                            <?php
                            $provinces = array_unique(array_column($markers['STATION'], 'province'));
                            foreach ($provinces as $province) {
                                echo "<option value=\"$province\">$province</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Selected Provinces:</label>
                        <div id="selected-provinces-container" class="border p-2 rounded" style="background-color: #fff;">
                            <!-- Selected provinces will be displayed here as tags -->
                        </div>
                    </div>
                    <input type="hidden" name="provinces" id="selected-provinces" value="">
                    <button type="submit" class="btn btn-primary">Add Promotion to Selected Provinces</button>
                </form>

                <!-- Add the following CSS for additional styling -->
                <style>
                    #province-select,
                    .form-control {
                        border-radius: 0.25rem;
                        transition: all 0.3s ease-in-out;
                    }

                    #province-select:focus,
                    .form-control:focus {
                        border-color: #80bdff;
                        box-shadow: 0 0 5px rgba(0, 123, 255, 0.25);
                    }

                    #selected-provinces-container {
                        min-height: 38px;
                    }

                    .badge-secondary {
                        background-color: #6c757d;
                        color: #fff;
                        cursor: pointer;
                        margin-right: 5px;
                        margin-bottom: 5px;
                        display: inline-block;
                    }

                    .badge-secondary .remove-tag {
                        margin-left: 0.5em;
                    }

                    .badge-secondary .remove-tag:hover {
                        color: red;
                    }
                </style>

                <!-- Add the following JavaScript for tag functionality -->
                <script>
                    $(document).ready(function() {
                        $('#province-select').on('change', function() {
                            var selectedProvince = $(this).val();
                            if (selectedProvince) {
                                addProvinceTag(selectedProvince);
                                $(this).val('');
                            }
                        });

                        function addProvinceTag(province) {
                            var container = $('#selected-provinces-container');
                            var existingProvinces = $('#selected-provinces').val().split(',').filter(Boolean);

                            if (!existingProvinces.includes(province)) {
                                existingProvinces.push(province);
                                var tag = $('<span class="badge badge-secondary">' + province + ' <span class="remove-tag">&times;</span></span>');
                                tag.find('.remove-tag').on('click', function() {
                                    removeProvinceTag(province, tag);
                                });
                                container.append(tag);
                                $('#selected-provinces').val(existingProvinces.join(','));
                            }
                        }

                        function removeProvinceTag(province, tag) {
                            var existingProvinces = $('#selected-provinces').val().split(',').filter(Boolean);
                            existingProvinces = existingProvinces.filter(function(item) {
                                return item !== province;
                            });
                            $('#selected-provinces').val(existingProvinces.join(','));
                            tag.remove();
                        }
                    });
                </script>



                <!-- Clear Specific Promotion Form -->
                <!-- <form id="clearPromotionForm" action="manage.php" method="post" class="mb-4">
            <input type="hidden" name="clear_promotions" value="1">
            <div class="form-group">
                <label for="selected_promotion">Select Promotion to Clear:</label>
                <select class="form-select" name="selected_promotion" required>
                    <?php foreach ($unique_promotions as $promotion_id) : ?>
                        <option value="<?php echo $promotion_id; ?>"><?php echo $promotion_id; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="button" class="btn btn-danger" onclick="confirmAction('Are you sure you want to clear the selected promotion?', 'clearPromotionForm')">Clear Selected Promotion</button>
        </form> -->
                <!-- Button to check for expired promotions -->


                <!-- Modal for Expired Promotions -->
                <div class="modal fade" id="expiredPromotionsModal" tabindex="-1" role="dialog" aria-labelledby="expiredPromotionsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="expiredPromotionsModalLabel">Expired Promotions</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Station ID</th>
                                            <th>Promotion ID</th>
                                            <th>End Time</th>
                                        </tr>
                                    </thead>
                                    <tbody id="expiredPromotionsTable">
                                        <!-- Rows will be populated by JavaScript -->
                                    </tbody>
                                </table>
                                <nav>
                                    <ul class="pagination justify-content-center" id="pagination">
                                        <!-- Pagination links will be populated by JavaScript -->
                                    </ul>
                                </nav>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" id="clearExpiredPromotionsBtn">Clear All
                                    Expired Promotions</button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clear All Selected Promotions Form -->
                <form id="clearAllPromotionsForm" action="manage.php" method="post" class="mb-4 p-3 border rounded shadow-sm" style="background-color: #f8f9fa;">
                    <input type="hidden" name="delete_all_promotions" value="1">
                    <div class="form-group">
                        <label for="selected_promotions">Select Promotions to Clear:</label>
                        <?php foreach ($unique_promotions as $promotion_id) : ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="selected_promotions[]" value="<?php echo $promotion_id; ?>" id="promo_<?php echo $promotion_id; ?>">
                                <label class="form-check-label" for="promo_<?php echo $promotion_id; ?>">
                                    <?php echo $promotion_id; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-danger" onclick="confirmAction('Are you sure you want to clear the selected promotions?', 'clearAllPromotionsForm')">Clear Selected Promotions</button>
                </form>

                <!-- Add the following CSS for animation and additional styling -->
                <style>
                    #clearAllPromotionsForm .form-check-input,
                    #clearAllPromotionsForm .btn {
                        transform: translateY(0);
                        opacity: 1;
                    }

                    #clearAllPromotionsForm .form-check-input,
                    #clearAllPromotionsForm .btn {
                        transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
                    }

                    #clearAllPromotionsForm .form-check-input:focus,
                    #clearAllPromotionsForm .btn:focus {
                        transform: translateY(-5px);
                        opacity: 0.9;
                    }

                    #clearAllPromotionsForm {
                        background-color: #fff;
                        padding: 20px;
                        border-radius: 5px;
                        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                    }

                    #clearAllPromotionsForm .btn {
                        transition: all 0.3s ease-in-out;
                    }

                    #clearAllPromotionsForm .btn:hover {
                        background-color: #c82333;
                        color: #fff;
                    }
                </style>


                <!-- Search Form -->
                <!-- Search Form -->
                <form class="form-inline mb-4 p-3 border rounded shadow-sm" id="searchForm" method="get" action="manage.php" style="background-color: #f8f9fa;">
                    <div class="form-group mr-2">
                        <input class="form-control" type="text" id="search" name="search" placeholder="Search by Station Title or Promotion ID" value="<?php echo htmlspecialchars($search_query); ?>" style="transition: all 0.3s ease-in-out;">
                    </div>
                    <div class="form-group mr-2">
                        <select class="custom-select" id="province-filter" name="province" style="transition: all 0.3s ease-in-out;">
                            <option value="">All Provinces</option>
                            <?php
                            $provinces = array_unique(array_column($markers['STATION'], 'province'));
                            foreach ($provinces as $province) {
                                echo "<option value=\"$province\"" . ($province === $selected_province ? " selected" : "") . ">$province</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary mr-2" style="transition: all 0.3s ease-in-out;">Filter</button>
                    <button type="button" id="clearFilter" class="btn btn-secondary" style="transition: all 0.3s ease-in-out;">Clear</button>
                </form>

                <!-- Add the following CSS for animation and additional styling -->
                <style>
                    #searchForm .form-control,
                    #searchForm .custom-select,
                    #searchForm .btn {
                        transform: translateY(0);
                        opacity: 1;
                    }

                    #searchForm .form-control,
                    #searchForm .custom-select,
                    #searchForm .btn {
                        transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
                    }

                    #searchForm .form-control:focus,
                    #searchForm .custom-select:focus,
                    #searchForm .btn:focus {
                        transform: translateY(-5px);
                        opacity: 0.9;
                    }

                    #searchForm {
                        background-color: #fff;
                        padding: 20px;
                        border-radius: 5px;
                        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                    }

                    #clearFilter {
                        transition: all 0.3s ease-in-out;
                    }

                    #clearFilter:hover {
                        background-color: #6c757d;
                        color: #fff;
                    }
                </style>

                <!-- Add the following JavaScript to handle the clear button functionality -->
                <script>
                    $(document).ready(function() {
                        $('#clearFilter').click(function() {
                            $('#search').val('');
                            $('#province-filter').val('');
                            $('#results').hide();
                            window.location.href = 'manage.php';
                        });

                        $('#searchForm').submit(function(event) {
                            var searchQuery = $('#search').val();
                            var selectedProvince = $('#province-filter').val();
                            var url = 'manage.php?search=' + encodeURIComponent(searchQuery) + '&province=' + encodeURIComponent(selectedProvince);
                            window.location.href = url;
                            event.preventDefault();
                        });
                    });
                </script>




                <div id="results" style="display: <?php echo (!empty($selected_province) || !empty($search_query)) ? 'block' : 'none'; ?>;">
                    <?php if (!empty($filtered_promotions)) : ?>
                        <?php foreach ($current_page_promotions as $promotion) : ?>
                            <div class="card mb-3">
                                <div class="card-header">
                                    <strong><?php echo $promotion['title']; ?> (Station ID:
                                        <?php echo $promotion['station_id']; ?>)</strong>
                                    <br>
                                    <small><?php echo $promotion['address']; ?></small>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($promotion['promotions'])) : ?>
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Promotion ID</th>
                                                    <th>End Time</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($promotion['promotions'] as $promo) : ?>
                                                    <tr data-promo-id="<?php echo $promo['promotion_id']; ?>" data-end-time="<?php echo $promo['end_time']; ?>">
                                                        <form action="manage.php" method="post" class="form-inline">
                                                            <input type="hidden" name="station_id" value="<?php echo $promotion['station_id']; ?>">
                                                            <input type="hidden" name="promotion_id" value="<?php echo $promo['promotion_id']; ?>">
                                                            <input type="hidden" name="action" value="edit">
                                                            <td>
                                                                <select class="form-select" name="new_promotion_id" required>
                                                                    <?php foreach ($promotion_ids as $promo_option) : ?>
                                                                        <option value="<?php echo $promo_option['promotion_id']; ?>" <?php echo ($promo_option['promotion_id'] == $promo['promotion_id']) ? 'selected' : ''; ?>>
                                                                            <?php echo $promo_option['promotion_id']; ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
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
                                    <?php else : ?>
                                        <p>No promotions available.</p>
                                    <?php endif; ?>
                                    <form action="manage.php" method="post" class="mt-4">
                                        <input type="hidden" name="station_id" value="<?php echo $promotion['station_id']; ?>">
                                        <input type="hidden" name="action" value="add">
                                        <div class="form-group">
                                            <label for="promotion_id">Promotion ID:</label>
                                            <select class="form-select" name="promotion_id" required>
                                                <?php foreach ($promotion_ids as $promo) : ?>
                                                    <option value="<?php echo $promo['promotion_id']; ?>">
                                                        <?php echo $promo['promotion_id']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
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
                                <li class="page-item <?php if ($page <= 1)
                                                            echo 'disabled'; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_query); ?>&province=<?php echo urlencode($selected_province); ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                        <span class="sr-only">Previous</span>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                                    <li class="page-item <?php if ($page == $i)
                                                                echo 'active'; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>&province=<?php echo urlencode($selected_province); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php if ($page >= $total_pages)
                                                            echo 'disabled'; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_query); ?>&province=<?php echo urlencode($selected_province); ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                        <span class="sr-only">Next</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php else : ?>
                        <p>No promotions found for the selected criteria.</p>
                    <?php endif; ?>
                </div>


            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#searchForm').submit(function(event) {
                var searchQuery = $('#search').val();
                var selectedProvince = $('#province-filter').val();
                var url = 'manage.php?search=' + encodeURIComponent(searchQuery) + '&province=' + encodeURIComponent(selectedProvince);
                window.location.href = url;
                event.preventDefault();
            });

            $('#province-filter, #search').on('change', function() {
                var selectedProvince = $('#province-filter').val();
                var searchQuery = $('#search').val();

                if (selectedProvince || searchQuery) {
                    $('#results').show();
                } else {
                    $('#results').hide();
                }
            });

            $('#clearFilter').click(function() {
                $('#search').val('');
                $('#province-filter').val('');
                $('#results').hide();
                window.location.href = 'manage.php';
            });
        });


        function confirmAction(message, formId) {
            if (confirm(message)) {
                document.getElementById(formId).submit();
            }
        }

        $("#menu-toggle").click(function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });
        $(document).ready(function() {
            $('#searchForm').submit(function(event) {
                var searchQuery = $('#search').val();
                var selectedProvince = $('#province-filter').val();
                var url = 'manage.php?search=' + encodeURIComponent(searchQuery) + '&province=' + encodeURIComponent(selectedProvince);
                window.location.href = url;
                event.preventDefault();
            });
        });

        const rowsPerPage = 10;
        let currentPage = 1;

        function displayExpiredPromotions(promotions, page) {
            const tableBody = document.getElementById('expiredPromotionsTable');
            tableBody.innerHTML = '';

            const start = (page - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const pagePromotions = promotions.slice(start, end);

            pagePromotions.forEach(promo => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${promo.station_id}</td>
                    <td>${promo.promotion_id}</td>
                    <td>${new Date(promo.end_time).toLocaleString()}</td>
                `;
                tableBody.appendChild(row);
            });
        }

        function displayPagination(totalPromotions, currentPage) {
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';

            const totalPages = Math.ceil(totalPromotions / rowsPerPage);
            for (let i = 1; i <= totalPages; i++) {
                const pageItem = document.createElement('li');
                pageItem.className = `page-item ${i === currentPage ? 'active' : ''}`;
                pageItem.innerHTML = `<a class="page-link" href="#" onclick="gotoPage(${i})">${i}</a>`;
                pagination.appendChild(pageItem);
            }
        }

        function gotoPage(page) {
            currentPage = page;
            displayExpiredPromotions(expiredPromotions, currentPage);
            displayPagination(expiredPromotions.length, currentPage);
        }
        $(document).ready(function() {
            $('#province-select').on('change', function() {
                var selectedProvince = $(this).val();
                if (selectedProvince) {
                    addProvinceTag(selectedProvince);
                    $(this).val('');
                }
            });

            function addProvinceTag(province) {
                var container = $('#selected-provinces-container');
                var existingProvinces = $('#selected-provinces').val().split(',').filter(Boolean);

                if (!existingProvinces.includes(province)) {
                    existingProvinces.push(province);
                    var tag = $('<span class="badge badge-secondary mr-2">' + province + ' <span class="remove-tag" style="cursor:pointer;">&times;</span></span>');
                    tag.find('.remove-tag').on('click', function() {
                        removeProvinceTag(province, tag);
                    });
                    container.append(tag);
                    $('#selected-provinces').val(existingProvinces.join(','));
                }
            }

            function removeProvinceTag(province, tag) {
                var existingProvinces = $('#selected-provinces').val().split(',').filter(Boolean);
                existingProvinces = existingProvinces.filter(function(item) {
                    return item !== province;
                });
                $('#selected-provinces').val(existingProvinces.join(','));
                tag.remove();
            }
        });

        $(document).ready(function() {
            $('#search').on('input', function() {
                var searchQuery = $(this).val();
                if (searchQuery.length > 0) {
                    $.get('manage.php', {
                        search: searchQuery
                    }, function(data) {
                        $('#results').html($(data).find('#results').html());
                        $('#results').show();
                    });
                } else {
                    $('#results').hide();
                }
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

            setInterval(checkExpiredPromotions, 60000);

            $('#checkExpiredPromotionsBtn').click(function() {
                var expiredPromotions = [];
                var currentTime = new Date().toISOString();

                <?php foreach ($combined_data as $promotion) : ?>
                    <?php foreach ($promotion['promotions'] as $promo) : ?>
                        if (new Date('<?php echo $promo['end_time']; ?>').toISOString() < currentTime) {
                            expiredPromotions.push({
                                station_id: '<?php echo $promotion['station_id']; ?>',
                                promotion_id: '<?php echo $promo['promotion_id']; ?>',
                                end_time: '<?php echo $promo['end_time']; ?>'
                            });
                        }
                    <?php endforeach; ?>
                <?php endforeach; ?>

                window.expiredPromotions = expiredPromotions;
                displayExpiredPromotions(expiredPromotions, currentPage);
                displayPagination(expiredPromotions.length, currentPage);

                $('#expiredPromotionsModal').modal('show');
            });

            $('#clearExpiredPromotionsBtn').click(function() {
                $.post('manage.php', {
                    clear_all_expired: 1
                }, function(response) {
                    location.reload();
                });
            });
        });

        function deletePromotion(stationId, promotionId) {
            if (confirm('Are you sure you want to delete this promotion?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'manage.php';

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
    $promotions = json_decode(file_get_contents('./data/promotions.json'), true);
    $markers = json_decode(file_get_contents('./data/markers.json'), true);

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

    $station_titles_json = json_encode($station_titles);
    $promotion_counts_json = json_encode($promotion_counts);
    $monthly_promotions_json = json_encode(array_values($monthly_promotions));
    $monthly_labels_json = json_encode(array_keys($monthly_promotions));
    $promotion_distribution_json = json_encode(array_values($promotion_distribution));
    $promotion_labels_json = json_encode(array_keys($promotion_distribution));
    ?>
</body>

</html>