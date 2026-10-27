<?php
session_start();
include '../database/database.php';

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login/login.php");
    exit;
}

$employee_id = $_SESSION['employee_id'];

$tailors_res = $conn->query("SELECT employee_id, firstname, lastname FROM employee WHERE role = 'Tailor' ORDER BY firstname");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service | <?= htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']) ?></title>
    <link rel="stylesheet" href="service.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body class="no-scroll-page">

    <header class="navbar">
        <div class="logo-container">
            <span class="logo-icon">&#9986;</span>
            <h1 class="logo-text">Swift Tailoring</h1>
        </div>
        <a class="logout-btn" href="../login/login.php">Logout</a>
    </header>

    <main class="dashboard-container">
        <div class="grid-layout">

            <section class="card large-card text-center" style="background-image: url('https://images.pexels.com/photos/6065984/pexels-photo-6065984.jpeg');">
                <div class="purple-overlay"></div>
                <div class="card-content">
                    <h2 class="serif-title">SWIFT TAILORING</h2>
                    <p class="subtitle">Elegance in Motion</p>
                    <a href="javascript:void(0)" onclick="openOrderModal()" class="action-btn">CREATE ORDER</a>
                </div>
            </section>

            <section class="card small-card text-center" style="background-image: url('https://images.pexels.com/photos/1545348/pexels-photo-1545348.jpeg');">
                <div class="purple-overlay"></div>
                <div class="card-content">
                    <h2 class="serif-title">SUITS</h2>
                    <p class="subtitle">Classic Style and Formal Statements</p>
                    <a href="javascript:void(0)" onclick="toggleModal('suitsModal')" class="action-btn">DISCOVER MORE</a>
                </div>
            </section>

            <section class="card small-card text-center" style="background-image: url('https://images.pexels.com/photos/31517347/pexels-photo-31517347.jpeg');">
                <div class="purple-overlay"></div>
                <div class="card-content">
                    <h2 class="serif-title">ABOUT US</h2>
                    <p class="subtitle">Get in Touch with Our Workshop Terminal</p>
                    <a href="javascript:void(0)" onclick="toggleModal('aboutModal')" class="action-btn">CONTACT US</a>
                </div>
            </section>

        </div>
    </main>

    <div id="suitsModal" class="modal-overlay" onclick="closeModalOnOutsideClick(event, 'suitsModal')">
        <div class="modal-card catalog-card">
            <button class="carousel-arrow left-arrow">&lt;</button>
            <div class="catalog-grid">
                <div class="catalog-item">
                    <img src="https://images.pexels.com/photos/27691960/pexels-photo-27691960.jpeg" alt="Black Tuxedo">
                    <h3>Black Tuxedo</h3>
                    <p>₱5,000</p>
                </div>
                <div class="catalog-item">
                    <img src="https://images.pexels.com/photos/4246645/pexels-photo-4246645.jpeg" alt="Navy Plain Wool">
                    <h3>Navy Plain Wool</h3>
                    <p>₱4,000</p>
                </div>
                <div class="catalog-item">
                    <img src="https://plus.unsplash.com/premium_photo-1760612414300-4a1e49a8cae5?q=80&w=735&auto=format&fit=crop" alt="Brown Plain Wool">
                    <h3>Brown Plain Wool</h3>
                    <p>₱6,500</p>
                </div>
            </div>
            <button class="carousel-arrow right-arrow">&gt;</button>
        </div>
    </div>

    <div id="aboutModal" class="modal-overlay" onclick="closeModalOnOutsideClick(event, 'aboutModal')">
        <div class="modal-card about-info-card">
            <h2 class="about-us-title">ABOUT US</h2>
            <table class="about-us-table">
                <tr>
                    <td class="label">Email:</td>
                    <td class="value">swifttailoring@gmail.com</td>
                </tr>
                <tr>
                    <td class="label">Phone:</td>
                    <td class="value">63+ 991 436 2261</td>
                </tr>
                <tr>
                    <td class="label">Address:</td>
                    <td class="value">Unit 67, 14<sup>th</sup> Avenue St., Brgy. Talamban,<br>Mandaue City, 6014 Cebu, Philippines</td>
                </tr>
            </table>
        </div>
    </div>

    <div id="orderModal" class="modal-overlay" onclick="closeModalOnOutsideClick(event, 'orderModal')">
        <div class="modal-card order-form-card">
            <form id="tailorOrderForm" action="process_order.php" method="POST" style="height: 100%; display: flex; flex-direction: column; justify-content: space-between;">

                <div id="orderPage1" class="order-page">
                    <span class="page-indicator">Page 1</span>
                    <h2 class="form-main-title">Order Form</h2>
                    <div style="text-align:center;">
                        <div class="form-badge">Customer Details</div>
                    </div>

                    <div class="customer-details-container">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="firstname" placeholder="First Name" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="lastname" placeholder="Last Name" required>
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email"
                                placeholder="example@mail.com" required>
                        </div>

                        <div class="form-group">
                            <label>Phone Number</label>
                            <input
                                type="tel"
                                name="phone"
                                pattern="09[0-9]{9}"
                                maxlength="11"
                                oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                placeholder="09XXXXXXXXX"
                                title="Enter an 11 digit Philippine mobile number."
                                required>
                        </div>

                        <div class="service-toggle-group">
                            <div class="service-type-toggle">
                                <button type="button" id="btnAlteration" class="toggle-choice active" onclick="setServiceType('Alteration')">Alteration</button>
                                <button type="button" id="btnTailoring" class="toggle-choice" onclick="setServiceType('Tailoring')">Tailoring</button>
                            </div>
                            <span class="toggle-sub-label">Service</span>
                        </div>
                        <input type="hidden" name="service_type" id="input_service_type" value="Alteration">
                    </div>

                    <div class="form-page1-footer">
                        <button type="button" class="form-nav-btn next-btn" onclick="validatePage1()">Next</button>
                    </div>
                </div>

                <div id="orderPage2" class="order-page" style="display: none; position: relative;">
                    <span class="page-indicator">Page 2</span>
                    <h2 class="form-main-title">Order Form</h2>
                    <div style="text-align:center;">
                        <div id="page2Badge" class="form-badge">Alteration Details</div>
                    </div>

                    <div class="page2-flex-container">
                        <div class="left-scroll-panel">
                            <span class="panel-section-label">Suit Type</span>
                            <div class="suit-type-row">

                                <button type="button" class="suit-btn" data-suit="Two_Piece" onclick="selectSuitType('Two_Piece')">2-piece</button>
                                <button type="button" class="suit-btn active" data-suit="Single_Jacket" onclick="selectSuitType('Single_Jacket')">Single - Jacket</button>
                                <button type="button" class="suit-btn" data-suit="Single_Trouser" onclick="selectSuitType('Single_Trouser')">Single - Trouser</button>
                                <button type="button" class="suit-btn" data-suit="Tuxedo" onclick="selectSuitType('Tuxedo')">Tuxedo</button>
                            </div>
                            <input type="hidden" name="suit_type" id="input_suit_type" value="Single_Jacket">

                            <div id="tailorAttributesSection" style="display: none;">
                                <span class="panel-section-label">Color</span>
                                <div class="color-picker-row">
                                    <label class="color-bubble navy active"><input type="radio" name="fabric_color" value="Navy" checked></label>
                                    <label class="color-bubble black"><input type="radio" name="fabric_color" value="Black"></label>
                                    <label class="color-bubble gray"><input type="radio" name="fabric_color" value="Gray"></label>
                                    <label class="color-bubble brown"><input type="radio" name="fabric_color" value="Brown"></label>
                                    <label class="color-bubble tan"><input type="radio" name="fabric_color" value="Tan"></label>
                                </div>

                                <span class="panel-section-label">Pattern</span>
                                <div class="text-attribute-grid">
                                    <button type="button" class="attr-btn active" data-attr="fabric_pattern" data-val="Plain" onclick="selectAttr(this,'fabric_pattern','Plain')">Plain</button>
                                    <button type="button" class="attr-btn" data-attr="fabric_pattern" data-val="Pinstripe" onclick="selectAttr(this,'fabric_pattern','Pinstripe')">Pinstripe</button>
                                    <button type="button" class="attr-btn" data-attr="fabric_pattern" data-val="Windowpane" onclick="selectAttr(this,'fabric_pattern','Windowpane')">Windowpane</button>
                                    <button type="button" class="attr-btn" data-attr="fabric_pattern" data-val="Herringbone" onclick="selectAttr(this,'fabric_pattern','Herringbone')">Herringbone</button>
                                    <button type="button" class="attr-btn" data-attr="fabric_pattern" data-val="Glen_Plaid" onclick="selectAttr(this,'fabric_pattern','Glen_Plaid')">Glen Plaid</button>
                                </div>
                                <input type="hidden" name="fabric_pattern" id="input_fabric_pattern" value="Plain">

                                <span class="panel-section-label">Material</span>
                                <div class="text-attribute-grid">
                                    <button type="button" class="attr-btn active" data-attr="fabric_material" data-val="Wool" onclick="selectAttr(this,'fabric_material','Wool')">Wool</button>
                                    <button type="button" class="attr-btn" data-attr="fabric_material" data-val="Standard" onclick="selectAttr(this,'fabric_material','Standard')">Standard</button>
                                    <button type="button" class="attr-btn" data-attr="fabric_material" data-val="Cotton_Linen" onclick="selectAttr(this,'fabric_material','Cotton_Linen')">Cotton Linen</button>
                                    <button type="button" class="attr-btn" data-attr="fabric_material" data-val="Velvet" onclick="selectAttr(this,'fabric_material','Velvet')">Velvet</button>
                                    <button type="button" class="attr-btn" data-attr="fabric_material" data-val="Satin" onclick="selectAttr(this,'fabric_material','Satin')">Satin</button>
                                </div>
                                <input type="hidden" name="fabric_material" id="input_fabric_material" value="Wool">
                            </div>

                            <span class="panel-section-label">Garment Components and Service Rates</span>
                            <div class="component-selector-grid">
                                <button type="button" class="comp-toggle-btn active" data-comp="Collar" data-gtype="Jacket" onclick="toggleComponentRow('Collar')">Collar</button>
                                <button type="button" class="comp-toggle-btn active" data-comp="Lapel" data-gtype="Jacket" onclick="toggleComponentRow('Lapel')">Lapel</button>
                                <button type="button" class="comp-toggle-btn active" data-comp="Shoulder" data-gtype="Jacket" onclick="toggleComponentRow('Shoulder')">Shoulder</button>
                                <button type="button" class="comp-toggle-btn active" data-comp="Chest" data-gtype="Jacket" onclick="toggleComponentRow('Chest')">Chest</button>

                                <button type="button" class="comp-toggle-btn active" data-comp="Button" data-gtype="Jacket" onclick="toggleComponentRow('Button')">Button</button>
                                <button type="button" class="comp-toggle-btn active" data-comp="Button_Stance" data-gtype="Jacket" onclick="toggleComponentRow('Button_Stance')">Btn Stance</button>
                                <button type="button" class="comp-toggle-btn active" data-comp="Pocket" data-gtype="Jacket" onclick="toggleComponentRow('Pocket')">Pocket</button>
                                <button type="button" class="comp-toggle-btn active" data-comp="Breast_Pocket" data-gtype="Jacket" onclick="toggleComponentRow('Breast_Pocket')">Breast Pkt</button>

                                <button type="button" class="comp-toggle-btn active" data-comp="Sleeve" data-gtype="Jacket" onclick="toggleComponentRow('Sleeve')">Sleeve</button>
                                <button type="button" class="comp-toggle-btn active" data-comp="Vents" data-gtype="Jacket" onclick="toggleComponentRow('Vents')">Vents</button>
                                <button type="button" class="comp-toggle-btn active" data-comp="Lining" data-gtype="Jacket" onclick="toggleComponentRow('Lining')">Lining</button>
                                <button type="button" class="comp-toggle-btn active" data-comp="Canvassing" data-gtype="Jacket" onclick="toggleComponentRow('Canvassing')">Canvassing</button>

                                <button type="button" class="comp-toggle-btn" data-comp="Waistband" data-gtype="Trouser" onclick="toggleComponentRow('Waistband')">Waistband</button>
                                <button type="button" class="comp-toggle-btn" data-comp="Pleats" data-gtype="Trouser" onclick="toggleComponentRow('Pleats')">Pleats</button>
                                <button type="button" class="comp-toggle-btn" data-comp="Break" data-gtype="Trouser" onclick="toggleComponentRow('Break')">Break</button>
                                <button type="button" class="comp-toggle-btn" data-comp="Cuff" data-gtype="Trouser" onclick="toggleComponentRow('Cuff')">Cuff</button>
                            </div>

                            <div class="form-row-split" style="margin-top:15px; gap: 10px;">
                                <div class="form-group">
                                    <label style="font-size:0.75rem; color:#7B4B7B; font-weight:600; display:block; text-align:center; margin-bottom:4px;">Estimated Pick-up Date</label>
                                    <input type="date" name="est_pickup_date" id="est_pickup_date" style="width:100%; text-align:center;" required>
                                </div>
                                <div class="form-group">
                                    <label style="font-size:0.75rem; color:#7B4B7B; font-weight:600; display:block; text-align:center; margin-bottom:4px;">Assigned Tailor</label>
                                    <select name="employee_id" id="employee_id" style="width:100%;" required>
                                        <?php while ($tailor = $tailors_res->fetch_assoc()): ?>
                                            <option value="<?= $tailor['employee_id']; ?>"><?= htmlspecialchars($tailor['firstname'] . ' ' . $tailor['lastname']); ?> (Tailor)</option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="right-scroll-panel">
                            <span class="panel-title-hint">Item-Line</span>
                            <div class="item-line-scroll" style="flex: 1; overflow-y: auto; padding-right: 5px;">
                                <div id="jacketSpecsContainer" class="garment-spec-group">
                                    <div class="garment-group-header">GARMENT: <strong>JACKET</strong></div>
                                    <div class="spec-table-headings">
                                        <span>Component</span><span>Measurement Specification</span><span>Price</span>
                                    </div>
                                    <div id="jacketRowsBucket"></div>
                                </div>

                                <div id="trouserSpecsContainer" class="garment-spec-group" style="display:none; margin-top: 15px;">
                                    <div class="garment-group-header">GARMENT: <strong>TROUSER</strong></div>
                                    <div class="spec-table-headings">
                                        <span>Component</span><span>Measurement Specification</span><span>Price</span>
                                    </div>
                                    <div id="trouserRowsBucket"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="page2-footer-actions">
                        <button type="button" class="form-nav-btn back-btn" onclick="goToPage(1)">← Back</button>

                        <div class="total-price-sticky-box">
                            <div class="total-text-label">Total Price:</div>
                            <div class="total-calculated-badge">₱<span id="txtCalculatedTotal">0.00</span></div>
                            <input type="hidden" name="total_amount" id="input_total_amount" value="0.00">
                        </div>

                        <button type="button" class="form-nav-btn submit-btn" onclick="validateAndSubmit()">Create Order</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
    <div id="successModal" class="modal-overlay">
        <div class="modal-card success-modal">
            <h2 style="color: #2f1b4e;">Order Created Successfully</h2>
            <p style="color: #7a5e85">
                Please provide this Order Code to the customer.
            </p>
            <p>Your Order Code</p>

            <div style="display:flex;gap:10px;justify-content:center;margin:20px 0;">
                <input
                    type="text"
                    id="orderCode"
                    readonly
                    style="flex:1;text-align:center;font-weight:bold; font-size:1.2rem; border: 1px solid #ccc; border-radius: 4px; padding: 8px; background-color: #f9f9f9; color: #333;">

                <button class='btn-copy' type="button" onclick="copyOrderCode()">Copy</button>
            </div>

            <button class='next-btn form-nav-btn' onclick="closeSuccessModal()">&#10004; Done</button>
        </div>
    </div>

    <script>
        const suitConfigurations = {
            'Two_Piece': {
                jacket: true,
                trouser: true
            },
            'Tuxedo': {
                jacket: true,
                trouser: true
            },
            'Single_Jacket': {
                jacket: true,
                trouser: false
            },
            'Single_Trouser': {
                jacket: false,
                trouser: true
            }
        };

        const jacketComponents = ['Collar', 'Lapel', 'Shoulder', 'Chest', 'Button', 'Button_Stance', 'Pocket', 'Breast_Pocket', 'Sleeve', 'Vents', 'Lining', 'Canvassing'];
        const trouserComponents = ['Waistband', 'Pleats', 'Break', 'Cuff'];
        let activeComponents = {};

        function toggleModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        // Global Modal intercept handler outside framework boxes
        function closeModalOnOutsideClick(event, modalId) {
            if (event.target.id === modalId) {
                if (modalId === 'orderModal') {
                    closeAndResetOrderForm();
                } else {
                    document.getElementById(modalId).classList.remove('active');
                }
            }
        }

        function openOrderModal() {
            toggleModal('orderModal');
            goToPage(1);
            setServiceType('Alteration');
            selectSuitType('Single_Jacket');
        }

        // Safely resets form data inputs and structural layout styling completely
        function closeAndResetOrderForm() {
            document.getElementById('orderModal').classList.remove('active');
            const targetForm = document.getElementById('tailorOrderForm');

            if (targetForm) {
                targetForm.reset();

                // Reset standard values back to initial baseline configurations
                document.getElementById('input_service_type').value = 'Alteration';
                document.getElementById('input_suit_type').value = 'Single_Jacket';
                document.getElementById('input_fabric_pattern').value = 'Plain';
                document.getElementById('input_fabric_material').value = 'Wool';

                // Visual cleanup of custom active button designs
                document.querySelectorAll('.toggle-choice, .suit-btn, .comp-toggle-btn, .attr-btn').forEach(btn => {
                    btn.classList.remove('active');
                });

                // Clear the running table calculations buckets out completely
                document.getElementById('jacketRowsBucket').innerHTML = '';
                document.getElementById('trouserRowsBucket').innerHTML = '';
                document.getElementById('txtCalculatedTotal').innerText = '0.00';
                document.getElementById('input_total_amount').value = '0.00';
            }
        }

        function goToPage(pageNum) {
            document.getElementById('orderPage1').style.display = (pageNum === 1) ? 'flex' : 'none';
            document.getElementById('orderPage2').style.display = (pageNum === 2) ? 'flex' : 'none';
        }

        function setServiceType(type) {
            document.getElementById('input_service_type').value = type;
            const btnAlt = document.getElementById('btnAlteration');
            const btnTailor = document.getElementById('btnTailoring');
            const badge = document.getElementById('page2Badge');
            const tailorSection = document.getElementById('tailorAttributesSection');

            if (type === 'Tailoring') {
                btnTailor.classList.add('active');
                btnAlt.classList.remove('active');
                badge.innerText = 'Tailoring Details';
                tailorSection.style.display = 'block';
            } else {
                btnAlt.classList.add('active');
                btnTailor.classList.remove('active');
                badge.innerText = 'Alteration Details';
                tailorSection.style.display = 'none';
            }
        }

        function selectSuitType(suit) {
            document.getElementById('input_suit_type').value = suit;
            document.querySelectorAll('.suit-btn').forEach(b => b.classList.remove('active'));
            document.querySelector(`[data-suit="${suit}"]`).classList.add('active');

            const config = suitConfigurations[suit];
            document.getElementById('jacketSpecsContainer').style.display = config.jacket ? 'block' : 'none';
            document.getElementById('trouserSpecsContainer').style.display = config.trouser ? 'block' : 'none';

            document.querySelectorAll('.comp-toggle-btn').forEach(btn => {
                const compType = btn.getAttribute('data-gtype');
                if ((compType === 'Jacket' && !config.jacket) || (compType === 'Trouser' && !config.trouser)) {
                    btn.classList.remove('active');
                    btn.setAttribute('disabled', 'true');
                    activeComponents[btn.getAttribute('data-comp')] = false;
                } else {
                    btn.removeAttribute('disabled');
                    btn.classList.add('active');
                    activeComponents[btn.getAttribute('data-comp')] = true;
                }
            });
            renderItemLineRows();
        }

        function selectAttr(element, inputId, val) {
            document.getElementById('input_' + inputId).value = val;
            document.querySelectorAll(`[data-attr="${inputId}"]`).forEach(b => b.classList.remove('active'));
            element.classList.add('active');
        }

        function toggleComponentRow(comp) {
            const btn = document.querySelector(`[data-comp="${comp}"]`);
            if (btn.hasAttribute('disabled')) return;

            btn.classList.toggle('active');
            activeComponents[comp] = btn.classList.contains('active');
            renderItemLineRows();
        }

        function renderItemLineRows() {
            const jacketBucket = document.getElementById('jacketRowsBucket');
            const trouserBucket = document.getElementById('trouserRowsBucket');

            let cacheSpec = {},
                cachePrice = {};
            document.querySelectorAll('.item-row-input').forEach(i => cacheSpec[i.name] = i.value);
            document.querySelectorAll('.item-price-input').forEach(i => cachePrice[i.name] = i.value);

            jacketBucket.innerHTML = '';
            trouserBucket.innerHTML = '';

            jacketComponents.forEach(comp => {
                if (activeComponents[comp]) jacketBucket.innerHTML += createRowHTML(comp, 'Jacket', cacheSpec, cachePrice);
            });

            trouserComponents.forEach(comp => {
                if (activeComponents[comp]) trouserBucket.innerHTML += createRowHTML(comp, 'Trouser', cacheSpec, cachePrice);
            });
            calculateRunningTotal();
        }

        function createRowHTML(comp, gtype, cacheSpec, cachePrice) {
            const specName = `spec[${gtype}][${comp}]`;
            const priceName = `price[${gtype}][${comp}]`;
            const cleanLabel = comp.replace('_', ' ');
            return `
                <div class="spec-row-item-line" id="row_${gtype}_${comp}">
                    <div class="comp-badge">${cleanLabel}</div>
                    <input type="text" name="${specName}" class="item-row-input" placeholder="Value" value="${cacheSpec[specName] || ''}" required>
                    <input type="number" step="0.01" name="${priceName}" class="item-price-input" placeholder="Price" value="${cachePrice[priceName] || ''}" oninput="calculateRunningTotal()" required>
                </div>
            `;
        }

        function calculateRunningTotal() {
            let grandTotal = 0.00;
            document.querySelectorAll('.item-price-input').forEach(input => {
                let val = parseFloat(input.value);
                if (!isNaN(val)) grandTotal += val;
            });
            document.getElementById('txtCalculatedTotal').innerText = grandTotal.toFixed(2);
            document.getElementById('input_total_amount').value = grandTotal.toFixed(2);
        }

        // Color Bubbles Active State Event Initializer
        document.querySelectorAll('.color-bubble').forEach(bubble => {
            bubble.addEventListener('click', function() {
                document.querySelectorAll('.color-bubble').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
        // Set the minimum date for the estimated pick-up date to today
        document.addEventListener("DOMContentLoaded", function() {
            const today = new Date().toISOString().split("T")[0];
            document.getElementById("est_pickup_date").setAttribute("min", today);
        });

        async function validateAndSubmit() {

            const form = document.getElementById("tailorOrderForm");

            const fields = form.querySelectorAll("input, select, textarea");

            for (const field of fields) {

                if (field.type === "hidden") continue;

                if (field.offsetParent === null) continue;

                if (!field.checkValidity()) {

                    if (document.getElementById("orderPage1").contains(field)) {
                        goToPage(1);
                    } else {
                        goToPage(2);
                    }

                    field.reportValidity();
                    field.focus();
                    return;
                }
            }

            const formData = new FormData(form);

            try {

                const response = await fetch("process_order.php", {
                    method: "POST",
                    body: formData
                });

                const data = await response.json();

                if (data.success) {

                    closeAndResetOrderForm();

                    showSuccessModal(data.order_code);

                } else {

                    alert(data.message || "Unable to create order.");

                }

            } catch (error) {

                alert("Server error.");

                console.error(error);

            }
        }

        function validatePage1() {
            const page1 = document.getElementById("orderPage1");
            const fields = page1.querySelectorAll("input, select");

            for (const field of fields) {
                if (field.type === "hidden") continue;

                if (!field.checkValidity()) {
                    field.reportValidity();
                    field.focus();
                    return;
                }
            }

            goToPage(2);
        }

        function showSuccessModal(orderCode) {
            document.getElementById("orderCode").value = orderCode;
            document.getElementById("successModal").classList.add("active");
        }

        function closeSuccessModal() {

            document.getElementById("successModal").classList.remove("active");

            openOrderModal();

        }

        function copyOrderCode() {
            const input = document.getElementById("orderCode");

            input.select();
            input.setSelectionRange(0, 99999);

            navigator.clipboard.writeText(input.value);

            alert("Order code copied!");
        }
    </script>
</body>

</html>