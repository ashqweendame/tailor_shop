<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. DATABASE CONNECTION
include("../database/database.php");

// Determine active page state via parameters
$current_page = isset($_GET['page']) ? $_GET['page'] : 'task_list';
$selected_order = isset($_GET['orderID']) ? intval($_GET['orderID']) : null;
$selected_employee = isset($_GET['employeeID']) ? intval($_GET['employeeID']) : null;
$edit_mode = isset($_GET['mode']) && $_GET['mode'] === 'edit';
$add_employee_mode = isset($_GET['mode']) && $_GET['mode'] === 'add_employee';

// Helper function to safely escape strings for SQL
function escape($conn, $str)
{
    return $conn->real_escape_string($str);
}
$search_query = isset($_GET['search']) ? escape($conn, $_GET['search']) : '';
$filter_status = isset($_GET['status_filter']) ? escape($conn, $_GET['status_filter']) : '';
$filter_role = isset($_GET['role_filter']) ? escape($conn, $_GET['role_filter']) : '';

// 2. HANDLE DATA MANIPULATION ACTIONS (CRUD)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_order') {
        $order_id = intval($_POST['order_id']);
        $status = escape($conn, $_POST['order_status']);
        $emp_id = intval($_POST['employee_id']);
        $fname = escape($conn, $_POST['firstname']);
        $lname = escape($conn, $_POST['lastname']);

        // Find customer ID associated with this order to update names as well
        $cust_res = $conn->query("SELECT customer_id FROM Order_Details WHERE order_id = $order_id LIMIT 1")->fetch_assoc();
        if ($cust_res) {
            $customer_id = $cust_res['customer_id'];
            $conn->query("UPDATE Customer SET firstname = '$fname', lastname = '$lname' WHERE customer_id = $customer_id");
        }

        // Batch update all order items statuses and assign tailors for the order ticket
        $conn->query("UPDATE Orders SET order_status='$status' WHERE order_id=$order_id");
        $conn->query("UPDATE Order_Details SET employee_id=$emp_id WHERE order_id=$order_id");

        // Update custom fields if editable attributes changed
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $od_id => $item_data) {
                $od_id = intval($od_id);
                $line_price = floatval($item_data['order_price']);
                $measurement = escape($conn, $item_data['measurement_value']);
                $conn->query("UPDATE Order_Details SET order_price = $line_price, measurement_value = '$measurement' WHERE order_details_id = $od_id");
            }
        }

        // Dynamically recalculate fresh combined aggregate ticket value total
        $conn->query("UPDATE Orders SET total_amount = (SELECT SUM(order_price) FROM Order_Details WHERE order_id = $order_id) WHERE order_id = $order_id");

        // Appended &msg=update_applied here
        header("Location: ?page=order_list&orderID=" . $order_id . "&search=" . urlencode($search_query) . "&status_filter=" . urlencode($filter_status) . "&msg=update_applied");
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update_employee') {
        $emp_id = intval($_POST['employee_id']);
        $fname = escape($conn, $_POST['firstname']);
        $lname = escape($conn, $_POST['lastname']);
        $role = escape($conn, $_POST['role']);
        $email = escape($conn, $_POST['email']);
        $phone = escape($conn, $_POST['phone']);
        $status = escape($conn, $_POST['status']);

        $conn->query("UPDATE Employee
            SET firstname='$fname',
            lastname='$lname',
            role='$role',
            email='$email',
            phone='$phone',
            employee_status='$status'
            WHERE employee_id=$emp_id");

        header("Location: ?page=employee_list&employeeID=$emp_id&search=" . urlencode($search_query) . "&role_filter=" . urlencode($filter_role) . "&msg=update_applied");
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'create_employee') {

        $fname = escape($conn, $_POST['firstname']);
        $lname = escape($conn, $_POST['lastname']);
        $role = escape($conn, $_POST['role']);
        $email = escape($conn, $_POST['email']);
        $phone = escape($conn, $_POST['phone']);
        $username_input = escape($conn, $_POST['username']);
        $password_input = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {

            $conn->query("
            INSERT INTO Employee
            (firstname, lastname, role, email, phone, username, password)
            VALUES
            ('$fname','$lname','$role','$email','$phone','$username_input','$password_input')
        ");

            $new_id = $conn->insert_id;

            $msg = urlencode("[$role] $fname $lname is added");

            header("Location: ?page=employee_list&employeeID=$new_id&msg=$msg");
            exit;
        } catch (mysqli_sql_exception $e) {

            if ($e->getCode() == 1062) {

                if (strpos($e->getMessage(), 'email') !== false) {
                    $msg = urlencode("Email address already exists.");
                } elseif (strpos($e->getMessage(), 'username') !== false) {
                    $msg = urlencode("Username already exists.");
                } else {
                    $msg = urlencode("Duplicate entry found.");
                }
            } else {
                $msg = urlencode("Database Error: " . $e->getMessage());
            }

            header("Location: ?page=employee_list&mode=add_employee&msg=$msg");
            exit;
        }
    }
}

// 3. FETCH TOP METRICS QUERIES
// Active Tailors
$active_tailors_res = $conn->query("
    SELECT COUNT(*) AS total
    FROM Employee
    WHERE role='Tailor'
")->fetch_assoc();

// Total Orders (everything except Cancelled)
$total_tasks_res = $conn->query("
    SELECT COUNT(*) AS total
    FROM Orders
    WHERE order_status NOT IN('Collected', 'Cancelled')
")->fetch_assoc();

// Active Tasks (Assigned)
$assigned_tasks_res = $conn->query("
    SELECT COUNT(*) AS total
    FROM Orders
    WHERE order_status='Assigned'
")->fetch_assoc();

// Ready Tasks
$ready_tasks_res = $conn->query("
    SELECT COUNT(*) AS total
    FROM Orders
    WHERE order_status='Ready'
")->fetch_assoc();

// Completed Tasks
$collected_tasks_res = $conn->query("
    SELECT COUNT(*) AS total
    FROM Orders
    WHERE order_status='Collected'
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | <?= htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']) ?></title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
        <div class="sidebar">
            <div>
                <div class="sidebar-title">Navigation</div>
                <ul class="nav-menu">
                    <li>
                        <a href="?page=task_list" class="nav-item-link <?php if ($current_page == 'task_list') echo 'active'; ?>">
                            <div class="nav-label-group"><i class="fa-solid fa-clipboard"></i> Task List</div>
                        </a>
                    </li>
                    <li>
                        <a href="?page=order_list" class="nav-item-link <?php if ($current_page == 'order_list') echo 'active'; ?>">
                            <div class="nav-label-group"><i class="fa-solid fa-receipt"></i> Order List</div>
                            <span class="nav-badge"><?php echo $total_tasks_res['total']; ?></span>
                        </a>
                    </li>
                    <li>
                        <a href="?page=employee_list" class="nav-item-link <?php if ($current_page == 'employee_list') echo 'active'; ?>">
                            <div class="nav-label-group"><i class="fa-solid fa-user"></i> Employee List</div>
                        </a>
                    </li>
                </ul>

            </div>
            <div class="tailor-profile-block">
                <?php
                $initials = strtoupper(
                    substr($_SESSION['firstname'], 0, 1) .
                        substr($_SESSION['lastname'], 0, 1)
                );
                ?>

                <div class="avatar">
                    <?= htmlspecialchars($initials) ?>
                </div>

                <div class="tailor-profile-info">
                    <h4>
                        <?= htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']) ?>
                    </h4>
                    <p><?= htmlspecialchars($_SESSION['user_role']) ?></p>
                </div>
            </div>
        </div>

        <div class="main-workspace">

            <?php if ($current_page == 'task_list'): ?>
                <div class="page-view-container">
                    <section class="metrics-container">
                        <div class="card">
                            <h3>Active Tailors</h3>
                            <div class="number"><?php echo $active_tailors_res['total']; ?></div>
                        </div>

                        <div class="card">
                            <h3>Total Orders</h3>
                            <div class="number"><?php echo $total_tasks_res['total']; ?></div>
                        </div>

                        <div class="card">
                            <h3>Active Tasks</h3>
                            <div class="number"><?php echo $assigned_tasks_res['total']; ?></div>
                        </div>

                        <div class="card">
                            <h3>Ready Tasks</h3>
                            <div class="number"><?php echo $ready_tasks_res['total']; ?></div>
                        </div>

                        <div class="card">
                            <h3>Collected Tasks</h3>
                            <div class="number"><?php echo $collected_tasks_res['total']; ?></div>
                        </div>
                    </section>

                    <h2 class="section-title">Staff & Tasks</h2>
                    <div class="table-column-labels">
                        <div>Avatar</div>
                        <div>Name / Role</div>
                        <div class="label-center">Total Tasks</div>
                        <div class="label-center">Done</div>
                        <div class="label-center">Avg Progress</div>
                        <div>Progress Bar</div>
                        <div></div>
                    </div>

                    <section class="staff-section">
                        <?php
                        $staff_query = "SELECT * FROM Employee WHERE role = 'Tailor' AND employee_status = 'Active'";
                        $staff_result = $conn->query($staff_query);
                        if ($staff_result && $staff_result->num_rows > 0) {
                            while ($employee = $staff_result->fetch_assoc()) {
                                $employee_id = $employee['employee_id'];
                                $total_count = $conn->query("SELECT COUNT(DISTINCT o.order_id) 
                                    AS count
                                    FROM Orders o
                                    JOIN Order_Details od ON o.order_id=od.order_id
                                    WHERE od.employee_id=$employee_id
                                    AND o.order_status<>'Cancelled'
                                    ")->fetch_assoc()['count'];

                                $done_count = $conn->query("SELECT COUNT(DISTINCT o.order_id) 
                                    AS count
                                    FROM Orders o
                                    JOIN Order_Details od ON o.order_id=od.order_id
                                    WHERE od.employee_id=$employee_id
                                    AND o.order_status IN ('Ready', 'Collected')
                                    ")->fetch_assoc()['count'];

                                $assigned_count = $conn->query("SELECT COUNT(DISTINCT o.order_id) 
                                    AS count
                                    FROM Orders o
                                    JOIN Order_Details od ON o.order_id=od.order_id
                                    WHERE od.employee_id=$employee_id
                                    AND o.order_status='Assigned'
                                    ")->fetch_assoc()['count'];

                                if ($total_count > 0) {
                                    $avg_progress = round(($done_count / $total_count) * 100);
                                } else {
                                    $avg_progress = 0;
                                }
                                if ($done_count == $total_count && $total_count > 0) $avg_progress = 100;

                                $initials = strtoupper(substr($employee['firstname'], 0, 1) . substr($employee['lastname'], 0, 1));
                        ?>
                                <details>
                                    <summary>
                                        <div class="summary-grid">
                                            <div class="avatar"><?php echo $initials; ?></div>
                                            <div class="staff-info">
                                                <h4><?php echo $employee['firstname'] . " " . $employee['lastname']; ?></h4><span><?php echo $employee['role']; ?></span>
                                            </div>
                                            <div class="stat-column"><?php echo $assigned_count; ?></div>
                                            <div class="stat-column done"><?php echo $done_count; ?></div>
                                            <div class="stat-column avg"><?php echo $avg_progress; ?>%</div>
                                            <div>
                                                <div class="ui-progress-bg">
                                                    <div class="ui-progress-fill" style="width: <?php echo $avg_progress; ?>%;"></div>
                                                </div>
                                            </div>
                                            <div class="arrow-icon">∨</div>
                                        </div>
                                    </summary>
                                    <div class="task-list">
                                        <div class="task-table-header">
                                            <div>Task</div>
                                            <div>Customer</div>
                                            <div>Due</div>
                                            <div>Status</div>
                                        </div>
                                        <?php
                                        $tasks_result = $conn->query("
                                            SELECT
                                                o.order_id,
                                                o.order_code,
                                                o.est_pickup_date,
                                                o.created_at,
                                                c.firstname AS cf,
                                                c.lastname AS cl,
                                                GROUP_CONCAT(DISTINCT od.service_type SEPARATOR ', ') AS services,
                                                o.order_status
                                            FROM Orders o
                                            JOIN Order_Details od ON o.order_id = od.order_id
                                            JOIN Customer c ON od.customer_id = c.customer_id
                                            WHERE od.employee_id = $employee_id
                                            AND o.order_status = 'Assigned'
                                            GROUP BY o.order_id
                                            ORDER BY o.est_pickup_date ASC
                                            LIMIT 5
                                            ");
                                        if ($tasks_result && $tasks_result->num_rows > 0) {
                                            while ($task = $tasks_result->fetch_assoc()) {
                                                $progress = ($task['order_status'] == 'Pending') ? 15 : (($task['order_status'] == 'Assigned') ? 50 : (($task['order_status'] == 'Ready') ? 85 : 100));
                                        ?>
                                                <div class="task-item">
                                                    <div class="task-main">
                                                        <h5><?php echo htmlspecialchars($task['services']); ?></h5><span><?php echo htmlspecialchars($task['order_code']); ?></span>
                                                    </div>
                                                    <div class="customer-name"><?php echo $task['cf'] . " " . $task['cl']; ?></div>
                                                    <div class="due-date"><?php echo date("M d", strtotime($task['est_pickup_date'])); ?></div>
                                                    <div>
                                                        <span class="badge"><?php echo htmlspecialchars($task['order_status']); ?></span>
                                                    </div>
                                                </div>
                                        <?php }
                                        } else {
                                            echo "<p style='padding:24px;color:var(--text-muted);margin:0;'>No tasks assigned.</p>";
                                        } ?>
                                    </div>
                                </details>
                        <?php }
                        } ?>
                    </section>
                </div>

                <!-- TAB CONTAINER 2: ORDER LIST -->
            <?php elseif ($current_page == 'order_list'): ?>
                <div class="split-chat-view">
                    <div class="chat-left-pane">
                        <div class="search-container">
                            <form method="GET" action="" class="search-container-spaced">
                                <input type="hidden" name="page" value="order_list">

                                <input type="text"
                                    name="search"
                                    class="search-input"
                                    placeholder="Search customer or order code"
                                    value="<?php echo htmlspecialchars($search_query); ?>">

                                <select name="status_filter" class="filter-select">
                                    <option value="">All Status</option>
                                    <option value="Pending" <?php if ($filter_status == "Pending") echo "selected"; ?>>Pending</option>
                                    <option value="Assigned" <?php if ($filter_status == "Assigned") echo "selected"; ?>>Assigned</option>
                                    <option value="Ready" <?php if ($filter_status == "Ready") echo "selected"; ?>>Ready</option>
                                    <option value="Collected" <?php if ($filter_status == "Collected") echo "selected"; ?>>Collected</option>
                                    <option value="Cancelled" <?php if ($filter_status == "Cancelled") echo "selected"; ?>>Cancelled</option>
                                </select>

                                <select name="date_filter" class="filter-select">
                                    <option value="">All Dates</option>
                                    <option value="daily" <?php if (isset($_GET['date_filter']) && $_GET['date_filter'] == "daily") echo "selected"; ?>>Today</option>
                                    <option value="weekly" <?php if (isset($_GET['date_filter']) && $_GET['date_filter'] == "weekly") echo "selected"; ?>>This Week</option>
                                    <option value="monthly" <?php if (isset($_GET['date_filter']) && $_GET['date_filter'] == "monthly") echo "selected"; ?>>This Month</option>
                                    <option value="yearly" <?php if (isset($_GET['date_filter']) && $_GET['date_filter'] == "yearly") echo "selected"; ?>>Specific Year</option>
                                </select>

                                <select name="year" class="filter-select">
                                    <option value="">Select Year</option>
                                    <?php
                                    for ($y = date("Y"); $y >= 2020; $y--) {
                                        $selected = (isset($_GET['year']) && $_GET['year'] == $y) ? "selected" : "";
                                        echo "<option value='$y' $selected>$y</option>";
                                    }
                                    ?>
                                </select>

                                <button class="btn-save" style="margin-top:8px;">Apply Filters</button>
                            </form>
                        </div>

                        <div class="order-history-list">
                            <?php
                            $date_filter = $_GET['date_filter'] ?? '';
                            $year = isset($_GET['year']) ? intval($_GET['year']) : '';

                            $sql = "SELECT
                                        o.order_id,
                                        o.order_code,
                                        o.created_at,
                                        c.firstname,
                                        c.lastname,
                                        GROUP_CONCAT(DISTINCT od.service_type SEPARATOR ', ') AS services,
                                        GROUP_CONCAT(DISTINCT g.suit_type SEPARATOR ', ') AS suit_types,
                                        o.order_status
                                    FROM Orders o
                                    JOIN Order_Details od ON o.order_id=od.order_id
                                    JOIN Customer c ON od.customer_id=c.customer_id
                                    JOIN Garment g ON od.garment_id=g.garment_id
                                    WHERE 1=1";

                            if (!empty($search_query)) {
                                $sql .= " AND (
                                    c.firstname LIKE '%$search_query%' OR
                                    c.lastname LIKE '%$search_query%' OR
                                    od.service_type LIKE '%$search_query%' OR
                                    g.suit_type LIKE '%$search_query%' OR
                                    o.order_code LIKE '%$search_query%'
                                )";
                            }

                            if (!empty($filter_status)) {
                                $sql .= " AND o.order_status='$filter_status'";
                            }

                            if ($date_filter == "daily") {
                                $sql .= " AND DATE(o.created_at)=CURDATE()";
                            }

                            if ($date_filter == "weekly") {
                                $sql .= " AND YEARWEEK(o.created_at,1)=YEARWEEK(CURDATE(),1)";
                            }

                            if ($date_filter == "monthly") {
                                $sql .= " AND MONTH(o.created_at)=MONTH(CURDATE())
                                        AND YEAR(o.created_at)=YEAR(CURDATE())";
                            }

                            if ($date_filter == "yearly" && !empty($year)) {
                                $sql .= " AND YEAR(o.created_at)=$year";
                            }

                            $sql .= " GROUP BY o.order_id
                                    ORDER BY o.created_at DESC,o.order_id DESC";

                            $orders_res = $conn->query($sql);

                            if ($orders_res && $orders_res->num_rows > 0) {
                                while ($chat_order = $orders_res->fetch_assoc()) {
                                    $is_active = ($selected_order == $chat_order['order_id']) ? 'active' : '';
                            ?>
                                    <a href="?page=order_list&orderID=<?php echo $chat_order['order_id']; ?>&search=<?php echo urlencode($search_query); ?>&status_filter=<?php echo urlencode($filter_status); ?>" class="order-chat-item <?php echo $is_active; ?>">
                                        <div class="chat-main-text">
                                            <h4><?php echo $chat_order['firstname'] . " " . $chat_order['lastname']; ?></h4>
                                            <p><?php echo $chat_order['services'] . " — " . str_replace('_', ' ', $chat_order['suit_types']); ?></p>
                                            <span><?php echo htmlspecialchars($chat_order['order_code']); ?></span>
                                        </div>
                                        <div class="chat-meta-info">
                                            <div><?php echo date("h:i A", strtotime($chat_order['created_at'])); ?></div>
                                            <span class="badge in-progress" style="font-size:10px;"><?php echo $chat_order['order_status']; ?></span>
                                        </div>
                                    </a>
                            <?php }
                            } else {
                                echo "<p style='padding:20px;text-align:center;color:var(--text-muted);'>No matching entries.</p>";
                            } ?>
                        </div>
                    </div>

                    <div class="chat-right-pane">
                        <?php if ($selected_order):
                            $order_base = $conn->query("SELECT o.*, c.firstname, c.lastname, o.order_status, od.employee_id 
                            FROM Orders o 
                            JOIN Order_Details od ON o.order_id = od.order_id
                            JOIN Customer c ON od.customer_id = c.customer_id 
                            WHERE o.order_id = $selected_order LIMIT 1")->fetch_assoc();
                            if ($order_base): ?>
                                <div class="detail-card-active">
                                    <div class="form-header-row">
                                        <div>
                                            <h2 style="margin:0; color:var(--primary-purple); font-family:serif;">Order Ticket Record</h2>
                                            <p style="color:var(--text-muted); font-size:13px; margin:4px 0 0 0;">
                                                <?php echo htmlspecialchars($order_base['order_code']); ?>
                                            </p>
                                        </div>
                                        <?php if (!$edit_mode): ?>
                                            <a href="?page=order_list&orderID=<?php echo $selected_order; ?>&search=<?php echo urlencode($search_query); ?>&status_filter=<?php echo urlencode($filter_status); ?>&mode=edit" class="btn-action-trigger">✏️ Edit</a>
                                        <?php else: ?>
                                            <a href="?page=order_list&orderID=<?php echo $selected_order; ?>&search=<?php echo urlencode($search_query); ?>&status_filter=<?php echo urlencode($filter_status); ?>" class="btn-action-trigger" style="color:var(--text-muted); border-color:var(--border-color);">Cancel</a>
                                        <?php endif; ?>
                                    </div>
                                    <hr style="border:0; border-top:1px solid var(--border-color); margin-bottom:20px;">

                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="update_order">
                                        <input type="hidden" name="order_id" value="<?php echo $order_base['order_id']; ?>">

                                        <div style="display:flex; gap:16px;">
                                            <div class="form-group" style="flex:1;">
                                                <label>Customer First Name</label>
                                                <input type="text" name="firstname" class="form-control" value="<?php echo htmlspecialchars($order_base['firstname']); ?>" required <?php if (!$edit_mode) echo 'disabled'; ?>>
                                            </div>
                                            <div class="form-group" style="flex:1;">
                                                <label>Customer Last Name</label>
                                                <input type="text" name="lastname" class="form-control" value="<?php echo htmlspecialchars($order_base['lastname']); ?>" required <?php if (!$edit_mode) echo 'disabled'; ?>>
                                            </div>
                                        </div>

                                        <div style="text-align: center; font-size: 13px; font-weight: 600; color: #7d5289; text-transform: uppercase; margin-bottom: 8px;">Item-Line</div>

                                        <div class="items-scroll-box">
                                            <?php
                                            $items_query = $conn->query("SELECT od.*, g.* FROM Order_Details od 
                                        JOIN Garment g ON od.garment_id = g.garment_id 
                                        WHERE od.order_id = $selected_order LIMIT 1");
                                            $first_item = $items_query->fetch_assoc();
                                            if ($first_item):
                                            ?>
                                                <div style="font-size: 14px; font-weight: bold; color: #a29ba6; text-transform: uppercase; margin-bottom: 16px; padding-left: 4px;">
                                                    Garment: <?php echo htmlspecialchars(str_replace('_', ' ', $first_item['garment_type'])); ?>
                                                </div>
                                            <?php endif; ?>

                                            <div class="image-header-label">
                                                <div>Component</div>
                                                <div>Measurement Specification</div>
                                                <div>Price</div>
                                            </div>

                                            <?php
                                            $rows_query = $conn->query("SELECT od.*, g.* FROM Order_Details od 
                                        JOIN Garment g ON od.garment_id = g.garment_id 
                                        WHERE od.order_id = $selected_order");
                                            while ($item = $rows_query->fetch_assoc()): ?>
                                                <div class="item-grid-row">
                                                    <div class="pill-component-tag" title="<?php echo htmlspecialchars($item['garment_component']); ?>">
                                                        <?php echo htmlspecialchars($item['garment_component']); ?>
                                                    </div>
                                                    <div>
                                                        <input type="text" name="items[<?php echo $item['order_details_id']; ?>][measurement_value]" class="capsule-input" style="text-align: center;" value="<?php echo htmlspecialchars($item['measurement_value']); ?>" placeholder="Measurement Value" <?php if (!$edit_mode) echo 'disabled'; ?>>
                                                    </div>
                                                    <div>
                                                        <input type="number" step="0.01" name="items[<?php echo $item['order_details_id']; ?>][order_price]" class="capsule-input" style="text-align: center;" value="<?php echo $item['order_price']; ?>" placeholder="Price" <?php if (!$edit_mode) echo 'disabled'; ?>>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>

                                        <div class="form-group">
                                            <label>Order Ticket Status</label>
                                            <select name="order_status" class="form-control" <?php if (!$edit_mode) echo 'disabled'; ?>>
                                                <option value="Pending" <?php if ($order_base['order_status'] === 'Pending') echo 'selected'; ?>>Pending</option>
                                                <option value="Assigned" <?php if ($order_base['order_status'] === 'Assigned') echo 'selected'; ?>>Assigned</option>
                                                <option value="Ready" <?php if ($order_base['order_status'] === 'Ready') echo 'selected'; ?>>Ready</option>
                                                <option value="Collected" <?php if ($order_base['order_status'] === 'Collected') echo 'selected'; ?>>Collected</option>
                                                <option value="Cancelled" <?php if ($order_base['order_status'] === 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Assign Primary Tailor</label>
                                            <select name="employee_id" class="form-control" <?php if (!$edit_mode) echo 'disabled'; ?>>
                                                <?php
                                                $emps = $conn->query("SELECT * FROM Employee WHERE role='Tailor'");
                                                while ($e = $emps->fetch_assoc()) {
                                                    $selected = ($e['employee_id'] == $order_base['employee_id']) ? 'selected' : '';
                                                    echo "<option value='{$e['employee_id']}' $selected>{$e['firstname']} {$e['lastname']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <div class="form-group" style="margin-top: 20px; background: #fdfafb; padding: 14px; border-radius: 8px; border: 1px solid var(--border-color);">
                                            <label style="color: var(--primary-purple);">Aggregate Ticket Invoice Value Total ($)</label>
                                            <input type="text" class="form-control" style="font-size:16px; font-weight:bold; color:var(--primary-purple);" value="$<?php echo number_format($order_base['total_amount'], 2); ?>" disabled>
                                        </div>
                                        <?php if ($edit_mode): ?>
                                            <button type="submit" class="btn-save">Apply Ticket Updates</button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            <?php endif;
                        else: ?>
                            <div class="empty-pane-graphic"><i class="fa-solid fa-receipt" style="font-size: 48px; color: var(--text-muted);"></i>
                                <h3>No item selected</h3>
                                <p>Select an entry from the list left pane to view details.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TAB CONTAINER 3: EMPLOYEE LIST -->
            <?php elseif ($current_page == 'employee_list'): ?>
                <div class="split-chat-view">
                    <div class="chat-left-pane">
                        <div class="search-container">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <span style="font-size: 12px; font-weight: bold; color: var(--text-muted); text-transform: uppercase;">Staff Directory</span>
                                <a href="?page=employee_list&mode=add_employee" style="font-size: 13px; font-weight: bold; color: var(--primary-purple); text-decoration: none;">+ Add Employee</a>
                            </div>
                            <form method="GET" action="" class="search-container-spaced">
                                <input type="hidden" name="page" value="employee_list">
                                <input type="text" name="search" class="search-input" placeholder="Search name or id" value="<?php echo htmlspecialchars($search_query); ?>">
                                <select name="role_filter" class="filter-select" onchange="this.form.submit()">
                                    <option value="">All Roles</option>
                                    <option value="Admin" <?php if ($filter_role == 'Admin') echo 'selected'; ?>>Admin</option>
                                    <option value="Tailor" <?php if ($filter_role == 'Tailor') echo 'selected'; ?>>Tailor</option>
                                    <option value="Service" <?php if ($filter_role == 'Service') echo 'selected'; ?>>Service</option>
                                </select>
                            </form>
                        </div>

                        <div class="order-history-list">
                            <?php
                            $emp_sql = "SELECT * FROM Employee WHERE 1=1";

                            if (empty($search_query)) {
                                $emp_sql .= " AND employee_status = 'Active'";
                            }
                            if (!empty($search_query)) {
                                $clean_emp_search = str_replace('EMP-00', '', $search_query);
                                $emp_sql .= " AND (firstname LIKE '%$search_query%' OR lastname LIKE '%$search_query%' OR username LIKE '%$search_query%' OR employee_id = '$clean_emp_search')";
                            }
                            if (!empty($filter_role)) {
                                $emp_sql .= " AND role = '$filter_role'";
                            }
                            $employees_res = $conn->query($emp_sql);

                            if ($employees_res && $employees_res->num_rows > 0) {
                                while ($emp_data = $employees_res->fetch_assoc()) {
                                    $is_active = ($selected_employee == $emp_data['employee_id'] && !$add_employee_mode) ? 'active' : 'inactive';
                                    $emp_id_curr = $emp_data['employee_id'];

                                    $is_currently_active = $emp_data['employee_status'];
                            ?>
                                    <a href="?page=employee_list&employeeID=<?php echo $emp_data['employee_id']; ?>" class="order-chat-item <?php echo $is_active; ?>">
                                        <div class="chat-main-text">
                                            <h4><?php echo $emp_data['firstname'] . " " . $emp_data['lastname']; ?></h4>
                                            <p style="font-weight: 500; color: var(--text-dark); margin: 3px 0;">
                                                Role: <?php echo $emp_data['role']; ?>
                                            </p>
                                            <span>EMP-00<?php echo $emp_data['employee_id']; ?></span>
                                        </div>
                                        <div class="chat-meta-info">
                                            <span class="badge" style="
                                                background: <?php echo ($emp_data['employee_status'] == 'Active') ? '#eef4e4' : '#fdebeb'; ?>;
                                                color: <?php echo ($emp_data['employee_status'] == 'Active') ? 'var(--green-accent)' : 'var(--red-accent)'; ?>;">
                                                <?php echo htmlspecialchars($emp_data['employee_status']); ?>
                                            </span>
                                        </div>
                                    </a>
                            <?php }
                            } else {
                                echo "<p style='padding:20px;text-align:center;color:var(--text-muted);'>No matching staff entries found.</p>";
                            } ?>
                        </div>
                    </div>

                    <div class="chat-right-pane">
                        <?php if ($add_employee_mode): ?>
                            <!-- NEW EMPLOYEE INSERTION INTERFACE PANEL -->
                            <div class="detail-card-active">
                                <div class="form-header-row">
                                    <div>
                                        <h2 style="margin:0; color:var(--primary-purple); font-family:serif;">Add New Employee</h2>
                                        <p style="color:var(--text-muted); font-size:13px; margin:4px 0 0 0;">Fill out profile settings to build system credentials</p>
                                    </div>
                                    <a href="?page=employee_list" class="btn-action-trigger" style="color:var(--text-muted); border-color:var(--border-color);">Cancel</a>
                                </div>
                                <hr style="border:0; border-top:1px solid var(--border-color); margin-bottom:20px;">

                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="create_employee">

                                    <div style="display:flex; gap:16px;">
                                        <div class="form-group" style="flex:1;">
                                            <label>First Name</label>
                                            <input type="text" name="firstname" class="form-control" required placeholder="e.g. John">
                                        </div>
                                        <div class="form-group" style="flex:1;">
                                            <label>Last Name</label>
                                            <input type="text" name="lastname" class="form-control" required placeholder="e.g. Doe">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Professional Assignment Role</label>
                                        <select name="role" class="form-control" required>
                                            <option value="Tailor">Tailor</option>
                                            <option value="Service">Service</option>
                                            <option value="Admin">Admin</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Email Address Alignment</label>
                                        <input type="email" name="email" class="form-control" required placeholder="name@domain.com">
                                    </div>
                                    <div class="form-group">
                                        <label>Phone Line Contact Number</label>
                                        <input type="text" name="phone" class="form-control" required placeholder="e.g. 555-0199">
                                    </div>
                                    <div style="display:flex; gap:16px;">
                                        <div class="form-group" style="flex:1;">
                                            <label>System Username</label>
                                            <input type="text" name="username" class="form-control" required placeholder="Unique username">
                                        </div>
                                        <div class="form-group" style="flex:1;">
                                            <label>Security Password</label>

                                            <div class="password-wrapper">
                                                <input
                                                    type="password"
                                                    id="employeePassword"
                                                    name="password"
                                                    class="form-control"
                                                    required
                                                    placeholder="••••••••">

                                                <button
                                                    type="button"
                                                    class="password-toggle"
                                                    onclick="togglePassword()">
                                                    <i id="passwordIcon" class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn-save" style="margin-top: 12px;">Register New Employee</button>
                                </form>
                            </div>

                            <?php elseif ($selected_employee):
                            $emp_details = $conn->query("SELECT * FROM Employee WHERE employee_id = $selected_employee")->fetch_assoc();
                            if ($emp_details): ?>
                                <div class="detail-card-active">
                                    <div class="form-header-row">
                                        <div>
                                            <h2 style="margin:0; color:var(--primary-purple); font-family:serif;">Staff Profile</h2>
                                            <p style="color:var(--text-muted); font-size:13px; margin:4px 0 0 0;">EMP-00<?php echo $emp_details['employee_id']; ?></p>
                                        </div>
                                        <?php if (!$edit_mode): ?>
                                            <a href="?page=employee_list&employeeID=<?php echo $selected_employee; ?>&mode=edit" class="btn-action-trigger">✏️ Edit</a>
                                        <?php else: ?>
                                            <a href="?page=employee_list&employeeID=<?php echo $selected_employee; ?>" class="btn-action-trigger" style="color:var(--text-muted); border-color:var(--border-color);">Cancel</a>
                                        <?php endif; ?>
                                    </div>
                                    <hr style="border:0; border-top:1px solid var(--border-color); margin-bottom:20px;">

                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="update_employee">
                                        <input type="hidden" name="employee_id" value="<?php echo $emp_details['employee_id']; ?>">

                                        <div style="display:flex; gap:16px;">
                                            <div class="form-group" style="flex:1;">
                                                <label>First Name</label>
                                                <input type="text" name="firstname" class="form-control" value="<?php echo htmlspecialchars($emp_details['firstname']); ?>" required <?php if (!$edit_mode) echo 'disabled'; ?>>
                                            </div>
                                            <div class="form-group" style="flex:1;">
                                                <label>Last Name</label>
                                                <input type="text" name="lastname" class="form-control" value="<?php echo htmlspecialchars($emp_details['lastname']); ?>" required <?php if (!$edit_mode) echo 'disabled'; ?>>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Employee Status</label>
                                            <select name="status" class="form-control" <?php if (!$edit_mode) echo 'disabled'; ?>>
                                                <option value="Active"
                                                    <?php if ($emp_details['employee_status'] === 'Active') echo 'selected'; ?>>Active</option>
                                                <option value="Inactive"
                                                    <?php if ($emp_details['employee_status'] === 'Inactive') echo 'selected'; ?>>Inactive</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Professional Assignment Role</label>
                                            <select name="role" class="form-control" <?php if (!$edit_mode) echo 'disabled'; ?>>
                                                <option value="Admin" <?php if ($emp_details['role'] === 'Admin') echo 'selected'; ?>>Admin</option>
                                                <option value="Tailor" <?php if ($emp_details['role'] === 'Tailor') echo 'selected'; ?>>Tailor</option>
                                                <option value="Service" <?php if ($emp_details['role'] === 'Service') echo 'selected'; ?>>Service</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Email Address Alignment</label>
                                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($emp_details['email']); ?>" required <?php if (!$edit_mode) echo 'disabled'; ?>>
                                        </div>
                                        <div class="form-group">
                                            <label>Phone Line Contact Number</label>
                                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($emp_details['phone']); ?>" required <?php if (!$edit_mode) echo 'disabled'; ?>>
                                        </div>
                                        <?php if ($edit_mode): ?>
                                            <button type="submit" class="btn-save">Save Profile Updates</button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            <?php endif;
                        else: ?>
                            <div class="empty-pane-graphic"><i class="fa-solid fa-user" style="font-size: 48px; color: var(--text-muted);"></i>
                                <h3>No staff profile selected</h3>
                                <p>Select an entry from the list left pane to load data profile options.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <?php if (isset($_GET['msg'])): ?>
        <?php
        // Securely prepare the message output to prevent XSS
        $raw_msg = $_GET['msg'];
        $display_msg = ($raw_msg === 'update_applied') ? 'Update applied' : htmlspecialchars($raw_msg);
        ?>
        <div id="system-toast" class="toast-popup show">
            <?php echo $display_msg; ?>
        </div>

        <script>
            // Wait 3 seconds, then hide the toast
            setTimeout(function() {
                var toast = document.getElementById('system-toast');
                if (toast) {
                    toast.classList.remove('show');
                }
            }, 3000);

            // Optional: Clean up the URL so the popup doesn't reappear on page refresh
            if (window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('msg');
                window.history.replaceState(null, null, url.toString());
            }
        </script>
    <?php endif; ?>
    <script>
        function togglePassword() {
            const password = document.getElementById("employeePassword");
            const icon = document.getElementById("passwordIcon");

            if (password.type === "password") {
                password.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                password.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>

</html>
<?php $conn->close(); ?>