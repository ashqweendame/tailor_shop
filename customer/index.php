<?php
include '../database/database.php';

$order_found = false;
$search_query = '';
$error_message = '';
$order_items = [];
$order_data = null;

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}


// Process Tracking Queries
if (isset($_GET['tracking_code']) && !empty(trim($_GET['tracking_code']))) {
  $search_query = trim($_GET['tracking_code']);

  $trackingCode = trim($search_query);

  $stmt = $conn->prepare("
    SELECT
        o.order_id,
        o.order_code,
        o.order_status,
        o.est_pickup_date AS due_date,
        c.firstname AS cust_first,
        c.lastname AS cust_last,
        e.firstname AS emp_first,
        e.lastname AS emp_last,
        g.suit_type AS garment_name,
        g.garment_type,
        g.garment_component,
        od.measurement_value,
        od.service_type,
        od.order_price AS garment_price,
        f.fabric_color,
        f.fabric_pattern,
        f.fabric_material
    FROM orders o
    JOIN order_details od ON o.order_id = od.order_id
    JOIN customer c ON od.customer_id = c.customer_id
    JOIN employee e ON od.employee_id = e.employee_id
    JOIN garment g ON od.garment_id = g.garment_id
    JOIN fabric f ON g.fabric_id = f.fabric_id
    WHERE o.order_code = ?
");

  $stmt->bind_param("s", $trackingCode);
  $stmt->execute();

  $result = $stmt->get_result();
  $order_items = $result->fetch_all(MYSQLI_ASSOC);

  $stmt->close();

  if (count($order_items) > 0) {
    $order_found = true;
    $order_data = $order_items[0];
  } else {
    $error_message = "Invalid tracking code or order not found.";
  }
}

// Step timeline configurations
$stages = ["Pending", "Assigned", "Ready", "Collected"];
$status = $order_data['order_status'] ?? 'Pending';
$current_stage_idx = array_search($status, $stages);
if ($current_stage_idx === false) {
  $current_stage_idx = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Swift Tailoring · Track Your Order</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;1,400;1,500&family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500;700&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="index.css?v=<?= time(); ?>" />
</head>

<body>

  <nav class="nav">
    <div class="nav-inner">
      <a class="nav-brand" href="index.php">
        <div class="nav-logo-icon">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="6" cy="6" r="3" />
            <circle cx="6" cy="18" r="3" />
            <line x1="20" y1="4" x2="8.12" y2="15.88" />
            <line x1="14.47" y1="14.48" x2="20" y2="20" />
            <line x1="8.12" y1="8.12" x2="12" y2="12" />
          </svg>
        </div>
        <span class="nav-brand-name">Swift Tailoring</span>
      </a>
      <a class="btn-login" href="../login/login.php">Login</a>
    </div>
  </nav>

  <section class="hero">
    <div class="hero-overlay"></div>

    <div class="hero-scissors" aria-hidden="true">
      <svg width="450" height="450" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="0.7" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="6" cy="6" r="3" />
        <circle cx="6" cy="18" r="3" />
        <line x1="20" y1="4" x2="8.12" y2="15.88" />
        <line x1="14.47" y1="14.48" x2="20" y2="20" />
        <line x1="8.12" y1="8.12" x2="12" y2="12" />
      </svg>
    </div>

    <div class="hero-content">
      <p class="hero-label">Swift Tailoring</p>
      <h1 class="hero-title">Track Your Order</h1>
      <p class="hero-sub">Enter the tracking code from your receipt to check the progress of your order.</p>
      <div class="search-wrap">
        <form action="index.php" method="GET" class="search-box">
          <input type="text" name="tracking_code" placeholder="Enter Tracking Code" autocomplete="off" value="<?= htmlspecialchars($search_query); ?>" required />
          <button type="submit" class="btn-track">Track Order</button>
        </form>
        <?php if (!empty($error_message)): ?>
          <p class="search-error" style="display: block;"><?= $error_message; ?></p>
        <?php endif; ?>
        <p class="search-hint">Try: TAI-KK4M-SW4, TAI-C4XR-NH2, ALT-XI9X-PB5</p>
      </div>
    </div>
  </section>

  <div class="result-area">

    <?php if (!$order_found): ?>
      <div id="placeholder" class="placeholder-grid">
        <div class="expect-card">
          <div class="expect-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="6" cy="6" r="3" />
              <circle cx="6" cy="18" r="3" />
              <line x1="20" y1="4" x2="8.12" y2="15.88" />
              <line x1="14.47" y1="14.48" x2="20" y2="20" />
              <line x1="8.12" y1="8.12" x2="12" y2="12" />
            </svg>
          </div>

          <strong>Assigned</strong>
          <p>Your item has been logged and assigned to a tailor. Active alterations are underway.</p>
        </div>
        <div class="expect-card">
          <div class="expect-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
              <polyline points="22 4 12 14.01 9 11.01" />
            </svg>
          </div>
          <strong>Ready</strong>
          <p>Your item is ready for collection.</p>
        </div>
        <div class="expect-card">
          <div class="expect-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
              <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
              <line x1="12" y1="22.08" x2="12" y2="12" />
            </svg>
          </div>
          <strong>Completed</strong>
          <p>Your item is complete and collected.</p>
        </div>
      </div>
    <?php else: ?>

      <div id="ticket" class="ticket-wrap" style="display: block;">
        <div class="ticket">

          <div class="ticket-header">
            <div class="ticket-header-row1">
              <div class="ticket-title-group">
                <span class="ticket-item-name"><?= htmlspecialchars(str_replace('_', ' ', !empty($order_data['garment_name']) ? $order_data['garment_name'] : 'Tailored Suit Custom')); ?></span>
                <span class="ticket-badge stage-<?= $current_stage_idx; ?>"><?= htmlspecialchars($status); ?></span>
              </div>
              <span class="ticket-eta-label">Est. Pickup Date</span>
            </div>
            <div class="ticket-header-row2">
              <span class="ticket-code">
                <?= htmlspecialchars($order_data['order_code']); ?>
              </span> <span class="ticket-date"><?= date("M d, Y", strtotime($order_data['due_date'])); ?></span>
            </div>
            <div class="ticket-header-row3">
              <span class="ticket-customer">For <?= htmlspecialchars($order_data['cust_first'] . ' ' . $order_data['cust_last']); ?> · <?= htmlspecialchars(str_replace('_', ' ', $order_data['service_type'])); ?></span>
              <span class="ticket-tailor">Tailor: <strong><?= htmlspecialchars($order_data['emp_first'] . ' ' . $order_data['emp_last']); ?></strong></span>
            </div>
          </div>

          <div class="ticket-timeline">
            <div class="timeline">
              <?php
              foreach ($stages as $i => $stageName):
                $filled = ($i <= $current_stage_idx);
                $done   = ($i < $current_stage_idx);
                $lLine  = ($i === 0) ? 'transparent' : ($filled ? '#4B2558' : '#D8D0E8');
                $rLine  = ($i === count($stages) - 1) ? 'transparent' : ($done ? '#4B2558' : '#D8D0E8');
              ?>
                <div class="tl-step">
                  <div class="tl-row">
                    <div class="tl-line" style="background: <?= $lLine; ?>;"></div>
                    <div class="tl-dot <?= $filled ? 'filled' : ''; ?>"></div>
                    <div class="tl-line" style="background: <?= $rLine; ?>;"></div>
                  </div>
                  <span class="tl-label <?= $filled ? 'tl-active' : ''; ?>"><?= $stageName; ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="ticket-body">
            <h2 class="garment-heading">Garment Summary</h2>
            <div class="garment-cards">
              <?php
              $total_sum = 0;
              $grouped = []; // group rows by garment_type, summing prices

              foreach ($order_items as $item) {
                $gtype = $item['garment_type'];
                $price = (float)$item['garment_price'];

                if (!isset($grouped[$gtype])) {
                  $grouped[$gtype] = $item;        // keep first row for color/pattern/material
                  $grouped[$gtype]['garment_price'] = $price;
                } else {
                  $grouped[$gtype]['garment_price'] += $price; // add up the price
                }
              }

              foreach ($grouped as $item):
                $price = (float)$item['garment_price'];
                $total_sum += $price;
              ?>
                <div class="garment-card" style="display: flex; justify-content: space-between; align-items: stretch; gap: 16px;">

                  <div class="gc-garment" style="flex: 1; min-width: 100px;">
                    <span class="gc-key" style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted);">Garment</span>
                    <span class="gc-val" style="font-size: 16px; font-weight: 600; color: var(--ink);"><?= htmlspecialchars(str_replace('_', ' ', $item['garment_type'])); ?></span>
                  </div>

                  <div class="gc-attrs" style="flex: 2; display: flex; flex-direction: column; gap: 4px; justify-content: center; border-left: 1px solid var(--lavender); padding-left: 16px;">

                    <?php if ($item['service_type'] === 'Tailoring'): ?>

                      <div>
                        <span class="gc-key" style="font-weight: 500;">Color: </span>
                        <span class="gc-val"><?= htmlspecialchars(str_replace('_', ' ', $item['fabric_color'])); ?></span>
                      </div>

                      <div>
                        <span class="gc-key" style="font-weight: 500;">Pattern: </span>
                        <span class="gc-val"><?= htmlspecialchars(str_replace('_', ' ', $item['fabric_pattern'])); ?></span>
                      </div>

                      <div>
                        <span class="gc-key" style="font-weight: 500;">Material: </span>
                        <span class="gc-val"><?= htmlspecialchars(str_replace('_', ' ', $item['fabric_material'])); ?></span>
                      </div>

                    <?php else: ?>

                      <?php
                      foreach ($order_items as $detail) {
                        if ($detail['garment_type'] === $item['garment_type']) {
                      ?>

                          <div>
                            <span class="gc-key" style="font-weight: 500;">
                              <?= htmlspecialchars(str_replace('_', ' ', $detail['garment_component'])); ?>:
                            </span>
                            <span class="gc-val">
                              <?= htmlspecialchars($detail['measurement_value']); ?>
                            </span>
                          </div>

                      <?php
                        }
                      }
                      ?>

                    <?php endif; ?>

                  </div>

                  <div class="gc-price" style="display: flex; flex-direction: column; justify-content: flex-end; align-items: flex-end; text-align: right; min-width: 80px;">
                    <span class="gc-key" style="font-size: 11px; color: var(--muted);">Price</span>
                    <span class="gc-pval" style="font-size: 18px; font-weight: 700; color: var(--violet);">₱<?= number_format($price, 2); ?></span>
                  </div>

                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="ticket-total">
            <?php
            if ($status === 'Collected') {
              echo '<span class="total-label">Total Amount Paid</span>';
            } else {
              echo '<span class="total-label">Total Amount Due</span>';
            }
            ?>
            <span class="total-value">₱<?= number_format($total_sum, 2); ?></span>
          </div>
        </div>
        <div class="contact-footer-accent">
          <p class="contact-heading">Questions About Your Order?</p>
          <p class="contact-sub">Please contact us with your <strong>Order #</strong> handy:</p>

          <div class="contact-rows-inline">
            <div class="contact-item">
              <div class="contact-icon-wrapper">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.99 12 19.79 19.79 0 0 1 1.98 3.18 2 2 0 0 1 3.95 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 5.99 5.99l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
                </svg>
              </div>
              <span class="contact-text mono">09914362261</span>
            </div>

            <span class="footer-divider">|</span>

            <div class="contact-item">
              <div class="contact-icon-wrapper">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                  <polyline points="22,6 12,13 2,6" />
                </svg>
              </div>
              <span class="contact-text">support@swifttailoring.com</span>
            </div>
          </div>

          <div class="contact-brand-accent">
            <p class="brand-script">Swift Tailoring</p>
            <p class="brand-tagline">Elegance In Motion</p>
            <p class="brand-address">University of San Carlos, Talamban Campus</p>
            <p class="brand-hours">Hours: Mon-Fri 8AM-7PM | Sat 9AM-5PM</p>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>

</body>

</html>