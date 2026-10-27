<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Make sure this file name exactly matches your database connection file!
include '../database/database.php';
header('Content-Type: application/json');
//Random Order_Code Generator
function generateOrderCode($service_type, $conn)
{
    $prefix = ($service_type === 'Tailoring') ? 'TAI' : 'ALT';

    do {
        $middle = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)); // 4 random letters/numbers
        $end = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);

        $order_code = "{$prefix}-{$middle}-{$end}";

        $check = $conn->prepare("SELECT order_id FROM orders WHERE order_code = ?");
        $check->bind_param("s", $order_code);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();
    } while ($exists);

    return $order_code;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. Safe Extraction & Type-Casting
    $firstname       = trim($_POST['firstname'] ?? '');
    $lastname        = trim($_POST['lastname'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $service_type    = $_POST['service_type'] ?? 'Alteration';
    $suit_type       = $_POST['suit_type'] ?? 'Two_Piece'; // Safe default matching your enum
    $est_pickup_date = $_POST['est_pickup_date'] ?? '';
    $employee_id     = intval($_POST['employee_id'] ?? 1); // Defaults to employee #1 if unassigned
    $total_amount    = floatval($_POST['total_amount'] ?? 0.00);
    $order_status    = $_POST['order_status'] ?? 'Assigned'; // Default status for new orders

    $specs  = $_POST['spec'] ?? [];
    $prices = $_POST['price'] ?? [];

    $conn->begin_transaction();

    try {
        // STEP A: Customer Check / Creation
        $stmt = $conn->prepare("
            SELECT customer_id
            FROM customer
            WHERE firstname = ?
            AND lastname = ?
            AND email = ?
            AND phone = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "ssss",
            $firstname,
            $lastname,
            $email,
            $phone
        );

        $stmt->execute();
        $result = $stmt->get_result();

        if ($customer = $result->fetch_assoc()) {
            // Exact customer already exists
            $customer_id = $customer['customer_id'];
        } else {
            // Create a new customer
            $stmtInsert = $conn->prepare("
                INSERT INTO customer
                (firstname, lastname, email, phone)
                VALUES (?, ?, ?, ?)
            ");

            $stmtInsert->bind_param(
                "ssss",
                $firstname,
                $lastname,
                $email,
                $phone
            );

            $stmtInsert->execute();
            $customer_id = $conn->insert_id;
        }

        // STEP B: Fabric Custom Configuration (Tailoring gets new entry, Alteration defaults to 1)
        $fabric_id = 1;
        if ($service_type === 'Tailoring') {
            $fabric_pattern  = $_POST['fabric_pattern'] ?? 'NA_Alt';
            $fabric_material = $_POST['fabric_material'] ?? 'NA_Alt';
            $fabric_color    = $_POST['fabric_color'] ?? 'NA_Alt';

            $stmtFabric = $conn->prepare("INSERT INTO fabric (fabric_pattern, fabric_material, fabric_color) VALUES (?, ?, ?)");
            $stmtFabric->bind_param("sss", $fabric_pattern, $fabric_material, $fabric_color);
            $stmtFabric->execute();
            $fabric_id = $conn->insert_id;
        }

        // STEP C: Initialize Master Order

        //Insert here order_code generator
        $order_code = generateOrderCode($service_type, $conn);


        $stmtOrder = $conn->prepare("
            INSERT INTO orders
            (order_code, order_status, total_amount, est_pickup_date)
            VALUES (?, ?, ?, ?)
        ");

        $stmtOrder->bind_param(
            "ssds",
            $order_code,
            $order_status,
            $total_amount,
            $est_pickup_date
        );
        $stmtOrder->execute();
        $order_id = $conn->insert_id;

        // STEP D: Process Component Item-Lines
        $stmtGarment = $conn->prepare("INSERT INTO garment (fabric_id, suit_type, garment_type, garment_component) VALUES (?, ?, ?, ?)");
        $stmtDetails = $conn->prepare("INSERT INTO order_details (order_id, customer_id, employee_id, garment_id, service_type, measurement_value, order_price) VALUES (?, ?, ?, ?, ?, ?, ?)");

        foreach ($specs as $garment_type => $components_array) {
            foreach ($components_array as $component_name => $measurement_value) {
                // Ignore empty inputs if your form sends blank measurement text blocks
                if (empty(trim($measurement_value))) continue;

                $order_price = floatval($prices[$garment_type][$component_name] ?? 0.00);

                // 1. Insert into Garment
                $stmtGarment->bind_param("isss", $fabric_id, $suit_type, $garment_type, $component_name);
                $stmtGarment->execute();
                $garment_id = $conn->insert_id;

                // 2. Insert into Order Details Matrix Ledger
                $stmtDetails->bind_param("iiiissd", $order_id, $customer_id, $employee_id, $garment_id, $service_type, $measurement_value, $order_price);
                $stmtDetails->execute();
            }
        }

        $conn->commit();
        echo json_encode([
            "success" => true,
            "order_code" => $order_code
        ]);

        exit;
    } catch (Exception $e) {

        $conn->rollback();

        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);

        exit;
    }
}
