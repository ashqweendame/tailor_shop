<?php
session_start();
include '../database/database.php';

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login/login.php");
    exit;
}

$employee_id = $_SESSION['employee_id'];
$selected_order = isset($_GET['orderID']) ? intval($_GET['orderID']) : null;

// Force browser to not cache this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Fetch active tasks for the middle pane
$query_progress = "SELECT
    o.order_id,
    o.order_code,
    o.est_pickup_date AS due_date,
    o.total_amount,
    od.service_type,
    g.suit_type,
    f.fabric_material,
    f.fabric_color,
    f.fabric_pattern,
    c.firstname AS cust_first,
    c.lastname AS cust_last,
    c.email,
    c.phone,
    o.order_status AS prominent_status
FROM orders o
JOIN order_details od ON o.order_id = od.order_id
JOIN customer c ON od.customer_id = c.customer_id
JOIN garment g ON od.garment_id = g.garment_id
JOIN fabric f ON g.fabric_id = f.fabric_id
WHERE od.employee_id = ?
AND o.order_status IN ('Pending','Assigned','Ready')
GROUP BY o.order_id
ORDER BY
    FIELD(o.order_status, 'Assigned', 'Pending', 'Ready'),
    o.est_pickup_date ASC, o.order_id ASC";

$stmt = $conn->prepare($query_progress);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result_progress = $stmt->get_result();

$tasks = [];
while ($row = $result_progress->fetch_assoc()) {
    $tasks[] = $row;
}
$count_progress = count($tasks);

// Find the selected task data for the right pane
$active_task_data = null;
if ($selected_order) {
    foreach ($tasks as $t) {
        if ($t['order_id'] == $selected_order) {
            $active_task_data = $t;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tailor Workspace | <?= htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']) ?></title>
    <!-- We use the admin stylesheet to maintain the exact 3-pane aesthetic -->
    <link rel="stylesheet" href="../admin/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Tailor-specific overrides mapped to admin aesthetic */
        .status-update-dropdown {
            background: var(--light-purple);
            color: var(--primary-purple);
            border: 1px solid var(--primary-purple);
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            outline: none;
            width: auto;
        }

        .status-update-dropdown:focus {
            border-color: var(--violet);
        }
    </style>
</head>

<body>

    <header class="navbar">
        <div class="logo-container">
            <span class="logo-icon"><i class="fa-solid fa-scissors"></i></span>
            <h1 class="logo-text">Swift Tailoring</h1>
        </div>
        <a class="logout-btn" href="../login/login.php">Logout</a>
    </header>

    <div class="app-container">

        <!-- PANE 1: LEFT SIDEBAR (MAIN NAV) -->
        <div class="sidebar">
            <div>
                <div class="sidebar-title">Navigation</div>
                <ul class="nav-menu">
                    <li>
                        <a href="?page=task_list" class="nav-item-link active">
                            <div class="nav-label-group"> <i class="fa-solid fa-clipboard"></i> My Tasks</div>
                            <span class="nav-badge"><?php echo $count_progress; ?></span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="tailor-profile-block">
                <?php $initials = strtoupper(substr($_SESSION['firstname'], 0, 1) . substr($_SESSION['lastname'], 0, 1)); ?>
                <div class="avatar"><?php echo $initials; ?></div>
                <div class="tailor-profile-info">
                    <h4><?= htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']) ?></h4>
                    <p><?= htmlspecialchars($_SESSION['user_role']) ?></p>
                </div>
            </div>
        </div>

        <!-- PANE 2 & 3: MAIN WORKSPACE -->
        <div class="main-workspace">
            <div class="split-chat-view">

                <!-- PANE 2: MIDDLE (ORDER LIST) -->
                <div class="chat-left-pane">
                    <div class="search-container">
                        <div style="font-size: 12px; font-weight: bold; color: var(--text-muted); text-transform: uppercase; padding: 4px 0 8px 4px;">
                            Active Assignments
                        </div>
                    </div>

                    <div class="order-history-list">
                        <?php if ($count_progress > 0): ?>
                            <?php foreach ($tasks as $task):
                                $is_active = ($selected_order == $task['order_id']) ? 'active' : '';
                                $badge_bg = ($task['prominent_status'] == 'Ready') ? '#f1f6d9' : 'var(--light-purple)';
                                $badge_color = ($task['prominent_status'] == 'Ready') ? 'var(--green-accent)' : 'var(--primary-purple)';
                            ?>
                                <a href="?orderID=<?php echo $task['order_id']; ?>" class="order-chat-item <?php echo $is_active; ?>">
                                    <div class="chat-main-text">
                                        <h4><?php echo htmlspecialchars($task['cust_first'] . " " . $task['cust_last']); ?></h4>
                                        <p><?php echo htmlspecialchars($task['service_type']) . " — " . str_replace('_', ' ', htmlspecialchars($task['suit_type'])); ?></p>
                                        <span><?php echo htmlspecialchars($task['order_code']); ?></span>
                                    </div>
                                    <div class="chat-meta-info">
                                        <div style="color: var(--text-dark); font-weight: 500;">Due: <?php echo date("M d", strtotime($task['due_date'])); ?></div>
                                        <span class="badge" style="background: <?php echo $badge_bg; ?>; color: <?php echo $badge_color; ?>;">
                                            <?php echo htmlspecialchars($task['prominent_status']); ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-pane-graphic" style="padding-top: 40px;">
                                <h3>No Tasks</h3>
                                <p>You have no active assignments.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- PANE 3: RIGHT (ORDER DETAILS) -->
                <div class="chat-right-pane">
                    <?php if ($selected_order && $active_task_data): ?>
                        <div class="detail-card-active">
                            <div class="form-header-row">
                                <div>
                                    <h2 style="margin:0; color:var(--primary-purple); font-family:serif;">Task Details</h2>
                                    <p style="color:var(--text-muted); font-size:13px; margin:4px 0 0 0;">
                                        Order Code: <?php echo htmlspecialchars($active_task_data['order_code']); ?>
                                    </p>
                                </div>
                                <div>
                                    <!-- Status update integrated directly into the header -->
                                    <select class="status-update-dropdown" onchange="updateStatus(<?php echo $active_task_data['order_id']; ?>, this.value)">
                                        <option value="Pending" <?php if ($active_task_data['prominent_status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                        <option value="Assigned" <?php if ($active_task_data['prominent_status'] == 'Assigned') echo 'selected'; ?>>Assigned</option>
                                        <option value="Ready" <?php if ($active_task_data['prominent_status'] == 'Ready') echo 'selected'; ?>>Ready</option>
                                        <option value="Collected" <?php if ($active_task_data['prominent_status'] == 'Collected') echo 'selected'; ?>>Collected</option>
                                        <option value="Cancelled" <?php if ($active_task_data['prominent_status'] == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <hr style="border:0; border-top:1px solid var(--border-color); margin-bottom:20px;">

                            <!-- Customer Information (Read Only) -->
                            <div style="display:flex; gap:16px; margin-bottom: 20px;">
                                <div class="form-group" style="flex:1; margin-bottom: 0;">
                                    <label>Customer Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($active_task_data['cust_first'] . " " . $active_task_data['cust_last']); ?>" disabled>
                                </div>
                                <div class="form-group" style="flex:1; margin-bottom: 0;">
                                    <label>Contact Info</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($active_task_data['email'] . " | " . $active_task_data['phone']); ?>" disabled>
                                </div>
                            </div>

                            <!-- Garment Specifications -->
                            <div style="text-align: center; font-size: 13px; font-weight: 600; color: #7d5289; text-transform: uppercase; margin-bottom: 8px;">Garment Specifications</div>
                            <div style="display:flex; gap:16px; margin-bottom: 24px;">
                                <div class="form-group" style="flex:1; margin-bottom: 0;">
                                    <label>Suit Type</label>
                                    <input type="text" class="form-control" value="<?php echo str_replace('_', ' ', htmlspecialchars($active_task_data['suit_type'])); ?>" disabled>
                                </div>
                                <?php if ($active_task_data['service_type'] === 'Tailoring'): ?>
                                    <div class="form-group" style="flex:1; margin-bottom: 0;">
                                        <label>Fabric Detail</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($active_task_data['fabric_color'] . ", " . str_replace('_', ' ', $active_task_data['fabric_pattern']) . " " . str_replace('_', ' ', $active_task_data['fabric_material'])); ?>" disabled>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Measurement Items grouped by Garment Type -->
                            <div style="text-align: center; font-size: 13px; font-weight: 600; color: #7d5289; text-transform: uppercase; margin-bottom: 8px;">Recorded Measurements</div>
                            <div class="items-scroll-box">
                                <?php
                                // Fetch specific measurements for this active order and tailor
                                $meas_query = "SELECT od.measurement_value, od.order_price, g.garment_component, g.garment_type 
                                               FROM order_details od 
                                               JOIN garment g ON od.garment_id = g.garment_id 
                                               WHERE od.order_id = ? AND od.employee_id = ?";
                                $m_stmt = $conn->prepare($meas_query);
                                $m_stmt->bind_param("ii", $selected_order, $employee_id);
                                $m_stmt->execute();
                                $measurements = $m_stmt->get_result();

                                $grouped_measurements = [];
                                if ($measurements->num_rows > 0) {
                                    while ($item = $measurements->fetch_assoc()) {
                                        $grouped_measurements[$item['garment_type']][] = $item;
                                    }
                                }

                                if (!empty($grouped_measurements)):
                                    foreach ($grouped_measurements as $garment_type => $items):
                                ?>
                                        <div style="font-size: 14px; font-weight: bold; color: #a29ba6; text-transform: uppercase; margin-bottom: 12px; margin-top: 10px; padding-left: 4px;">
                                            Garment: <?php echo htmlspecialchars(str_replace('_', ' ', $garment_type)); ?>
                                        </div>
                                        <div class="image-header-label">
                                            <div>Component</div>
                                            <div>Measurement Specification</div>
                                            <div>Price (₱)</div>
                                        </div>

                                        <?php foreach ($items as $item): ?>
                                            <div class="item-grid-row">
                                                <div class="pill-component-tag" title="<?php echo htmlspecialchars(str_replace('_', ' ', $item['garment_component'])); ?>">
                                                    <?php echo htmlspecialchars(str_replace('_', ' ', $item['garment_component'])); ?>
                                                </div>
                                                <div>
                                                    <input type="text" class="capsule-input" style="text-align: center; font-weight: bold;" value="<?php echo htmlspecialchars($item['measurement_value']); ?>" disabled>
                                                </div>
                                                <div>
                                                    <input type="text" class="capsule-input" style="text-align: center;" value="₱<?php echo number_format($item['order_price'], 2); ?>" disabled>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <hr style="border:0; border-top:1px dashed var(--border-color); margin: 16px 0;">
                                <?php
                                    endforeach;
                                else:
                                    echo "<p style='text-align:center; color:var(--text-muted); font-size:12px; padding:20px 0;'>No measurements recorded.</p>";
                                endif;
                                ?>
                            </div>

                            <div class="form-group" style="display:flex; justify-content:space-between; align-items:center; background: #fdfafb; padding: 14px; border-radius: 8px; border: 1px solid var(--border-color);">
                                <div>
                                    <label style="color: var(--text-muted); margin-bottom:2px;">Due Date</label>
                                    <span style="font-size:14px; font-weight:bold; color:var(--text-dark);"><?php echo date("Y-m-d", strtotime($active_task_data['due_date'])); ?></span>
                                </div>
                                <div style="text-align: right;">
                                    <label style="color: var(--primary-purple); margin-bottom:2px;">Service Price</label>
                                    <span style="font-size:16px; font-weight:bold; color:var(--primary-purple);">₱<?php echo number_format($active_task_data['total_amount'], 2); ?></span>
                                </div>
                            </div>

                        </div>
                    <?php else: ?>
                        <div class="empty-pane-graphic">
                            <i class="fa-solid fa-clipboard " style="font-size: 48px; color: var(--text-muted);"></i>
                            <h3>No task selected</h3>
                            <p>Select a task assignment from the left pane to view details and update statuses.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Function to handle database update trigger via AJAX
        function updateStatus(orderId, newStatus) {
            fetch('update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'order_id=' + orderId + '&status=' + encodeURIComponent(newStatus)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Success! Reload the page smoothly so the list/badges update
                        window.location.reload();
                    } else {
                        alert('Error updating status: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the status.');
                });
        }
    </script>
</body>

</html>