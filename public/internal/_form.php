<?php
global $error_message, $order_no, $order_amount, $order_description, $buyer_name, $buyer_email, $buyer_tel, $payment_gateways, $emandate_option, $api_version, $merchant_info, $current_config, $payer_id_type, $payer_id, $frequency_mode, $application_reason;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bayarcash API v2 - Development Sandbox</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/desktop.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/v4-shims.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        #loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
        }
        .loader-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .spinner {
            width: 60px;
            height: 60px;
            animation: pulse 1.5s ease-in-out infinite, spin 6s linear infinite;
        }
        .spinner-shadow {
            position: absolute;
            width: 60px;
            height: 60px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: shadow-pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes shadow-pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.6; }
            50% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.4; }
        }
        .config-status {
            font-size: 0.85em;
            margin-top: 5px;
        }
        .override-active {
            color: #28a745;
            font-weight: bold;
        }
        .config-default {
            color: #6c757d;
        }
        /* Config Panel Styles */
        .config-panel {
            background: #5a67d8;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
            box-shadow: 0 4px 15px rgba(90, 103, 216, 0.3);
        }
        .config-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .config-panel-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .config-panel-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .config-panel-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }
        .config-status-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .config-status-item {
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
            padding: 12px;
            backdrop-filter: blur(10px);
        }
        .config-status-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.85;
            margin-bottom: 4px;
        }
        .config-status-value {
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .config-status-value.active {
            color: #90EE90;
        }
        .config-status-value .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
        }
        .config-status-value.active .status-dot {
            background: #90EE90;
            box-shadow: 0 0 8px rgba(144, 238, 144, 0.6);
        }
        /* Override Modal Styles */
        .override-modal .modal-content {
            border: none;
            border-radius: 16px;
            overflow: hidden;
        }
        .override-modal .modal-header {
            background: #5a67d8;
            color: white;
            border: none;
            padding: 20px 25px;
        }
        .override-modal .modal-header .close {
            color: white;
            opacity: 0.8;
            text-shadow: none;
        }
        .override-modal .modal-header .close:hover {
            opacity: 1;
        }
        .override-modal .modal-title {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .override-modal .modal-body {
            padding: 25px;
        }
        .override-modal .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .override-modal .form-control {
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .override-modal .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }
        .override-modal .current-config-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        .override-modal .current-config-box h6 {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .override-modal .config-value-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        .override-modal .config-value-label {
            font-weight: 600;
            color: #555;
            min-width: 100px;
        }
        .override-modal .config-value-text {
            color: #888;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }
        .override-modal .modal-footer {
            border-top: 1px solid #eee;
            padding: 15px 25px;
        }
        .override-modal .btn {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
        }
        .override-modal .btn-danger {
            background: #e53e3e;
            border: none;
        }
        .override-modal .btn-primary {
            background: #5a67d8;
            border: none;
        }
        .override-info-alert {
            background: #e6fffa;
            border: none;
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .override-info-alert i {
            color: #00897b;
            font-size: 1.2rem;
            margin-top: 2px;
        }
        .override-info-alert-text {
            color: #00695c;
            font-size: 0.9rem;
        }
        @media (max-width: 576px) {
            .config-status-grid {
                grid-template-columns: 1fr;
            }
            .config-panel {
                padding: 15px;
            }
        }
        .modal {
            display: none;
        }
        .modal.show {
            display: block !important;
        }
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1040;
            width: 100vw;
            height: 100vh;
            background-color: #000;
            opacity: 0.5;
        }
        .payment-type-switcher {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .switch-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 400px;
            margin: 0 auto;
        }
        .switch-label {
            font-weight: 500;
            font-size: 14px;
        }
        .switch-label.active {
            color: #28a745;
            font-weight: bold;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
            margin: 0 15px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 30px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #28a745;
        }
        input:checked + .slider:before {
            transform: translateX(30px);
        }
        .emandate-fields {
            background: #e8f4f8;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            border-left: 4px solid #17a2b8;
        }
        .emandate-fields.hidden {
            display: none;
        }
        .emandate-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 0.9em;
        }
        .payment-buttons.hidden {
            display: none;
        }
        .emandate-buttons.hidden {
            display: none;
        }
        /* Payment Channel Tabs */
        .channel-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 15px;
        }
        .channel-tab {
            padding: 8px 14px;
            border: none;
            background: #f0f0f0;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #666;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .channel-tab:hover {
            background: #e0e0e0;
        }
        .channel-tab.active {
            background: #5a67d8;
            color: white;
        }
        .channel-tab-content {
            display: none;
        }
        .channel-tab-content.active {
            display: block;
        }
        .channel-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        .channel-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: left;
            width: 100%;
        }
        .channel-btn:hover {
            border-color: #28a745;
            background: #f8fff9;
        }
        .channel-btn:active {
            transform: scale(0.98);
        }
        .channel-btn .channel-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .channel-btn .channel-icon.banking { background: #e3f2fd; color: #1976d2; }
        .channel-btn .channel-icon.card { background: #fce4ec; color: #c2185b; }
        .channel-btn .channel-icon.ewallet { background: #e8f5e9; color: #388e3c; }
        .channel-btn .channel-icon.bnpl { background: #fff3e0; color: #f57c00; }
        .channel-btn .channel-icon.intl { background: #e0f7fa; color: #0097a7; }
        .channel-btn .channel-icon.other { background: #f5f5f5; color: #616161; }
        .channel-btn .channel-info {
            flex: 1;
            min-width: 0;
        }
        .channel-btn .channel-name {
            font-weight: 600;
            font-size: 0.85rem;
            color: #333;
        }
        .channel-btn .channel-desc {
            font-size: 0.7rem;
            color: #888;
        }
        @media (max-width: 576px) {
            .channel-grid {
                grid-template-columns: 1fr;
            }
            .channel-tab {
                padding: 6px 12px;
                font-size: 0.75rem;
            }
        }
        /* Split Payment Styles */
        .split-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        .split-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .split-header label {
            margin: 0;
            font-weight: 600;
        }
        .split-toggle {
            position: relative;
            width: 44px;
            height: 24px;
        }
        .split-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .split-toggle .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .3s;
            border-radius: 24px;
        }
        .split-toggle .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }
        .split-toggle input:checked + .slider {
            background-color: #5a67d8;
        }
        .split-toggle input:checked + .slider:before {
            transform: translateX(20px);
        }
        .split-container {
            display: none;
        }
        .split-container.active {
            display: block;
        }
        .split-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
        }
        .split-item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .split-item-title {
            font-weight: 600;
            font-size: 0.85rem;
            color: #666;
        }
        .split-remove-btn {
            background: none;
            border: none;
            color: #e53e3e;
            cursor: pointer;
            padding: 2px 6px;
            font-size: 0.8rem;
        }
        .split-remove-btn:hover {
            color: #c53030;
        }
        .split-row {
            display: grid;
            grid-template-columns: 1fr 100px 80px;
            gap: 8px;
        }
        .split-row input, .split-row select {
            font-size: 0.85rem;
            padding: 6px 10px;
        }
        .split-add-btn {
            width: 100%;
            padding: 10px;
            border: 2px dashed #ccc;
            background: transparent;
            border-radius: 8px;
            color: #666;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .split-add-btn:hover {
            border-color: #5a67d8;
            color: #5a67d8;
        }
        .split-add-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .split-error {
            background: #fed7d7;
            color: #c53030;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }
        @media (max-width: 576px) {
            .split-row {
                grid-template-columns: 1fr;
            }
        }
        /* Channel Mode Selector */
        .channel-mode-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }
        .channel-mode-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .channel-mode-tab {
            flex: 1;
            min-width: 120px;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .channel-mode-tab:hover {
            border-color: #5a67d8;
            background: #f8f9ff;
        }
        .channel-mode-tab.active {
            border-color: #5a67d8;
            background: #5a67d8;
            color: white;
        }
        /* Multi-Channel Panel */
        .multi-channel-panel {
            background: #e8f4f8;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #17a2b8;
        }
        .multi-channel-panel.hidden {
            display: none;
        }
        .multi-channel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .selected-count {
            background: #17a2b8;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        .multi-channel-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        .multi-channel-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            margin: 0;
            transition: all 0.2s;
        }
        .multi-channel-item:hover {
            background: #d1ecf1;
        }
        .multi-channel-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #17a2b8;
        }
        /* All Channel Panel */
        .all-channel-panel {
            background: #d4edda;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #28a745;
        }
        .all-channel-panel.hidden {
            display: none;
        }
        .all-channel-info {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .all-channel-info i {
            font-size: 1.5rem;
            color: #28a745;
            margin-top: 2px;
        }
        .all-channel-info p {
            font-size: 0.9rem;
            color: #155724;
        }
        @media (max-width: 576px) {
            .channel-mode-tabs {
                flex-direction: column;
            }
            .channel-mode-tab {
                min-width: auto;
            }
            .multi-channel-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div id="loading-overlay">
    <div class="loader-container">
        <div class="spinner-shadow"></div>
        <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAMAAABEpIrGAAAAXVBMVEVHcEwjOIQjOYQjOYQjOYQjOYQjOYQjSIcklNYklNYklNYjeb4kkdMklNYklNYklNYjN4Mkl9kjWZ8jOYQjKHkklNYkk9Ukk9UjOYQjOIMjY6gkfcAklNYklNYjOYRzxrQZAAAAH3RSTlMA3mj/hR0yBRk9KQiW/+xn+v//wUHc97Kh8P//ylJTk+NkDgAAANxJREFUeAHV0QWOxDAQBMAJmCHM8P9nmm6iZeFRiQLtNsGfkHk5fFB45T8JEEoZIC6kug+URZC2y7UJbIWBWxmByiArMXCj1ubSGPEcaBtzi1+Brickr4vCBIPgvBqNN2FghkAta5O+BkOsSIEFkn7zgRESxT11f1DMePrFQeU4aHwRWG4bXgW6cD63a7BwL79ZRBZ2gRW7tXbkvraO+yRK9dnXOUxSKRF3uWOFV8egrwjG0UQSgrm4MZkbeKFlgWoK2lwOQLQrgiWuVaaSUSu4weh5kutFHIeEH+MAZaoPYZ1M9b0AAAAASUVORK5CYII=" alt="Loading" class="spinner">
    </div>
</div>
<div id="container" class="container col-4 mt-3 mb-4 container-width">

    <?php if ($config['environment'] === 'dev'): ?>
    <!-- Config Panel (Dev Only) -->
    <div class="config-panel">
        <div class="config-panel-header">
            <h6 class="config-panel-title">
                <i class="fas fa-sliders-h"></i> API Configuration <span id="config-mode-label">(Default)</span>
            </h6>
            <button type="button" class="config-panel-btn" id="config-button">
                <i class="fas fa-edit"></i> Override
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alert -->
    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            <?php if (!empty($errors) && count($errors) > 1): ?>
                <hr class="my-2">
                <strong>Details:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php elseif (!empty($errors) && count($errors) === 1): ?>
                <?php
                    // Only show detail if it's different from main message
                    $detail = htmlspecialchars($errors[0]);
                    $main = htmlspecialchars($error_message);
                    if (stripos($main, $detail) === false && stripos($detail, $main) === false):
                ?>
                    <hr class="my-2">
                    <small><?php echo $detail; ?></small>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Card -->
    <div class="card shadow">
        <div class="card-header">
            Transaction Details
        </div>
        <div class="card-body">
            <form method="POST" action="" class="mb-0 pb-0 bayarcash-form" id="payment-form">
                <!-- Hidden fields for browser storage overrides -->
                <input type="hidden" name="override_bearer_token" id="override_bearer_token" value="">
                <input type="hidden" name="override_portal_key" id="override_portal_key" value="">
                <input type="hidden" name="payment_method" id="payment_method" value="">
                <input type="hidden" name="payment_type" id="hidden_payment_type" value="payment">

                <!-- Payment Type Switcher -->
                <div class="payment-type-switcher">
                    <h6 class="mb-3 text-center"><i class="fas fa-credit-card"></i> Payment Type</h6>
                    <div class="switch-container">
                        <span class="switch-label active" id="payment-label">One-time Payment</span>
                        <label class="switch">
                            <input type="checkbox" id="payment-type-toggle">
                            <span class="slider"></span>
                        </label>
                        <span class="switch-label" id="emandate-label">eMandate Enrollment</span>
                    </div>
                </div>

                <div class="card-text">
                    <div class="row">
                        <div class="col">
                            <div class="form-group mb-3">
                                <label class="mb-1" for="order_no"><b>Order Number</b></label>
                                <input type="text" name="order_no" id="order_no" class="form-control" value="<?php echo htmlspecialchars($order_no); ?>" readonly>
                            </div>
                            <div class="form-group mb-3">
                                <label class="mb-1" for="order_amount"><b>Amount</b></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">RM</span>
                                    </div>
                                    <input type="number" name="order_amount" id="order_amount" class="form-control" value="<?php echo htmlspecialchars($order_amount); ?>">
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label class="mb-1" for="buyer_name"><b>Name</b></label>
                                <input type="text" name="buyer_name" id="buyer_name" class="form-control" value="<?php echo htmlspecialchars($buyer_name); ?>">
                            </div>
                            <div class="form-group mb-3">
                                <label class="mb-1" for="buyer_email"><b>Email</b></label>
                                <input type="email" name="buyer_email" id="buyer_email" class="form-control" value="<?php echo htmlspecialchars($buyer_email); ?>">
                            </div>
                            <div class="form-group mb-3">
                                <label class="mb-1" for="buyer_tel"><b>Telephone</b></label>
                                <input type="tel" name="buyer_tel" id="buyer_tel" class="form-control" value="<?php echo htmlspecialchars($buyer_tel); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Split Payment Section -->
                <div class="split-section" id="split-section">
                    <div class="split-header">
                        <label><i class="fas fa-code-branch"></i> Split Payment</label>
                        <label class="split-toggle">
                            <input type="checkbox" id="split-toggle">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <small class="text-muted d-block mb-2">Split payment to multiple recipients (max 6)</small>
                    <div class="split-container" id="split-container">
                        <div id="split-items"></div>
                        <button type="button" class="split-add-btn" id="split-add-btn">
                            <i class="fas fa-plus"></i> Add Recipient
                        </button>
                    </div>
                    <input type="hidden" name="splits" id="splits" value="<?php echo htmlspecialchars($_POST['splits'] ?? ''); ?>">
                </div>

                <!-- eMandate Specific Fields -->
                <div class="emandate-fields hidden" id="emandate-fields">
                    <div class="emandate-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>eMandate Information:</strong> This will set up a recurring direct debit mandate for future automatic payments.
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="mb-1" for="payer_id_type"><b>ID Type</b></label>
                                <select name="payer_id_type" id="payer_id_type" class="form-control">
                                    <option value="1" selected>MyKad (NRIC)</option>
                                </select>
                                <small class="text-muted">Currently supports MyKad only</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="mb-1" for="payer_id"><b>MyKad Number</b></label>
                                <input type="text" name="payer_id" id="payer_id" class="form-control" value="<?php echo htmlspecialchars($payer_id); ?>" maxlength="12" pattern="[0-9]{12}">
                                <small class="text-muted">12-digit MyKad number without dashes</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="mb-1" for="frequency_mode"><b>Frequency</b></label>
                                <select name="frequency_mode" id="frequency_mode" class="form-control">
                                    <option value="MT" selected>Monthly</option>
                                    <option value="WK">Weekly</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="mb-1" for="application_reason"><b>Application Reason</b></label>
                                <input type="text" name="application_reason" id="application_reason" class="form-control" value="<?php echo htmlspecialchars($application_reason); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Payment Channel Mode Selector -->
                <div class="channel-mode-section mb-3">
                    <label class="mb-2"><b><i class="fas fa-layer-group"></i> Channel Selection Mode</b></label>
                    <div class="channel-mode-tabs">
                        <button type="button" class="channel-mode-tab active" data-mode="single">
                            <i class="fas fa-hand-pointer"></i> Single Channel
                        </button>
                        <button type="button" class="channel-mode-tab" data-mode="multi">
                            <i class="fas fa-check-double"></i> Multi-Channel
                        </button>
                        <button type="button" class="channel-mode-tab" data-mode="all">
                            <i class="fas fa-globe"></i> All Channels
                        </button>
                    </div>
                    <small class="text-muted d-block mt-1" id="channel-mode-desc">Click a payment method to proceed directly</small>
                </div>

                <!-- Multi-Channel Selection Panel -->
                <div class="multi-channel-panel hidden" id="multi-channel-panel">
                    <div class="multi-channel-header">
                        <span><i class="fas fa-check-square"></i> Select Payment Channels</span>
                        <span class="selected-count" id="selected-count">0 selected</span>
                    </div>
                    <div class="multi-channel-grid" id="multi-channel-grid">
                        <label class="multi-channel-item"><input type="checkbox" value="1"> FPX Online Banking</label>
                        <label class="multi-channel-item"><input type="checkbox" value="4"> FPX Line of Credit</label>
                        <label class="multi-channel-item"><input type="checkbox" value="5"> DuitNow</label>
                        <label class="multi-channel-item"><input type="checkbox" value="6"> DuitNow QR</label>
                        <label class="multi-channel-item"><input type="checkbox" value="12"> Credit Card</label>
                        <label class="multi-channel-item"><input type="checkbox" value="22"> JCB</label>
                        <label class="multi-channel-item"><input type="checkbox" value="16"> Touch n Go</label>
                        <label class="multi-channel-item"><input type="checkbox" value="17"> Boost Wallet</label>
                        <label class="multi-channel-item"><input type="checkbox" value="18"> GrabPay</label>
                        <label class="multi-channel-item"><input type="checkbox" value="21"> Shopee Pay</label>
                        <label class="multi-channel-item"><input type="checkbox" value="7"> SPayLater</label>
                        <label class="multi-channel-item"><input type="checkbox" value="8"> Boost PayFlex</label>
                        <label class="multi-channel-item"><input type="checkbox" value="19"> GrabPay Later</label>
                        <label class="multi-channel-item"><input type="checkbox" value="20"> ShopBack</label>
                        <label class="multi-channel-item"><input type="checkbox" value="9"> QRIS Banking</label>
                        <label class="multi-channel-item"><input type="checkbox" value="10"> QRIS eWallet</label>
                        <label class="multi-channel-item"><input type="checkbox" value="11"> NETS</label>
                        <label class="multi-channel-item"><input type="checkbox" value="13"> Alipay</label>
                        <label class="multi-channel-item"><input type="checkbox" value="14"> WeChat Pay</label>
                        <label class="multi-channel-item"><input type="checkbox" value="15"> PromptPay</label>
                    </div>
                    <button type="button" class="btn btn-primary btn-block mt-3" id="multi-channel-submit">
                        <i class="fas fa-arrow-right"></i> Proceed with Selected Channels
                    </button>
                </div>

                <!-- All Channels Panel -->
                <div class="all-channel-panel hidden" id="all-channel-panel">
                    <div class="all-channel-info">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>All Available Channels</strong>
                            <p class="mb-0">Payment page will display all payment channels available for your portal.</p>
                        </div>
                    </div>
                    <button type="button" class="btn btn-success btn-block mt-3" id="all-channel-submit">
                        <i class="fas fa-arrow-right"></i> Proceed (Show All Channels)
                    </button>
                </div>

                <!-- Payment Gateway Buttons -->
                <div class="payment-buttons" id="payment-buttons">
                    <!-- Tabs -->
                    <div class="channel-tabs">
                        <button type="button" class="channel-tab active" data-tab="banking">Banking</button>
                        <button type="button" class="channel-tab" data-tab="cards">Cards</button>
                        <button type="button" class="channel-tab" data-tab="ewallet">eWallet</button>
                        <button type="button" class="channel-tab" data-tab="bnpl">BNPL</button>
                        <button type="button" class="channel-tab" data-tab="intl">International</button>
                        <button type="button" class="channel-tab" data-tab="other">Other</button>
                    </div>

                    <!-- Banking -->
                    <div class="channel-tab-content active" id="tab-banking">
                        <div class="channel-grid">
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="1">
                                <div class="channel-icon banking"><i class="fas fa-university"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">FPX Online Banking</div>
                                    <div class="channel-desc">CASA Account</div>
                                </div>
                            </button>
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="4">
                                <div class="channel-icon banking"><i class="fas fa-credit-card"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">FPX Line of Credit</div>
                                    <div class="channel-desc">Credit Card via FPX</div>
                                </div>
                            </button>
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="5">
                                <div class="channel-icon banking"><i class="fas fa-mobile-alt"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">DuitNow</div>
                                    <div class="channel-desc">Online Banking/Wallets</div>
                                </div>
                            </button>
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="6">
                                <div class="channel-icon banking"><i class="fas fa-qrcode"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">DuitNow QR</div>
                                    <div class="channel-desc">Scan & Pay</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Cards -->
                    <div class="channel-tab-content" id="tab-cards">
                        <div class="channel-grid">
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="12">
                                <div class="channel-icon card"><i class="fas fa-credit-card"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">Credit Card</div>
                                    <div class="channel-desc">Visa / Mastercard</div>
                                </div>
                            </button>
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="22">
                                <div class="channel-icon card"><i class="fab fa-cc-jcb"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">JCB</div>
                                    <div class="channel-desc">JCB Card</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- eWallet -->
                    <div class="channel-tab-content" id="tab-ewallet">
                        <div class="channel-grid">
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="16">
                                <div class="channel-icon ewallet"><i class="fas fa-wallet"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">Touch n Go</div>
                                    <div class="channel-desc">TnG eWallet</div>
                                </div>
                            </button>
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="17">
                                <div class="channel-icon ewallet"><i class="fas fa-wallet"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">Boost</div>
                                    <div class="channel-desc">Boost Wallet</div>
                                </div>
                            </button>
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="18">
                                <div class="channel-icon ewallet"><i class="fas fa-wallet"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">GrabPay</div>
                                    <div class="channel-desc">Grab Wallet</div>
                                </div>
                            </button>
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="21">
                                <div class="channel-icon ewallet"><i class="fas fa-wallet"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">Shopee Pay</div>
                                    <div class="channel-desc">Shopee Wallet</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- BNPL -->
                    <div class="channel-tab-content" id="tab-bnpl">
                        <div class="channel-grid">
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="7">
                                <div class="channel-icon bnpl"><i class="fas fa-clock"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">SPayLater</div>
                                    <div class="channel-desc">Shopee BNPL</div>
                                </div>
                            </button>
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="8">
                                <div class="channel-icon bnpl"><i class="fas fa-clock"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">Boost PayFlex</div>
                                    <div class="channel-desc">Boost BNPL</div>
                                </div>
                            </button>
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="19">
                                <div class="channel-icon bnpl"><i class="fas fa-clock"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">GrabPay Later</div>
                                    <div class="channel-desc">Grab BNPL</div>
                                </div>
                            </button>
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="20">
                                <div class="channel-icon bnpl"><i class="fas fa-clock"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">ShopBack</div>
                                    <div class="channel-desc">ShopBack BNPL</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- International -->
                    <div class="channel-tab-content" id="tab-intl">
                        <div class="channel-grid">
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="9">
                                <div class="channel-icon intl"><i class="fas fa-globe-asia"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">QRIS Banking</div>
                                    <div class="channel-desc">Indonesia</div>
                                </div>
                            </button>
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="10">
                                <div class="channel-icon intl"><i class="fas fa-globe-asia"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">QRIS eWallet</div>
                                    <div class="channel-desc">Indonesia</div>
                                </div>
                            </button>
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="11">
                                <div class="channel-icon intl"><i class="fas fa-globe-asia"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">NETS</div>
                                    <div class="channel-desc">Singapore</div>
                                </div>
                            </button>
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="13">
                                <div class="channel-icon intl"><i class="fab fa-alipay"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">Alipay</div>
                                    <div class="channel-desc">China</div>
                                </div>
                            </button>
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="14">
                                <div class="channel-icon intl"><i class="fab fa-weixin"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">WeChat Pay</div>
                                    <div class="channel-desc">China</div>
                                </div>
                            </button>
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="15">
                                <div class="channel-icon intl"><i class="fas fa-globe-asia"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">PromptPay</div>
                                    <div class="channel-desc">Thailand</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Other -->
                    <div class="channel-tab-content" id="tab-other">
                        <div class="channel-grid">
                            <button type="button" class="channel-btn payment-gateway-btn" data-gateway="2">
                                <div class="channel-icon other"><i class="fas fa-file-invoice"></i></div>
                                <div class="channel-info">
                                    <div class="channel-name">Manual Bank Transfer</div>
                                    <div class="channel-desc">Upload proof of payment</div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- eMandate Button -->
                <div class="emandate-buttons hidden" id="emandate-buttons">
                    <div class="row mt-4">
                        <div class="col-12 button mb-2">
                            <button type="button" class="btn btn-info btn-block mr-1 h-100 p-2" id="emandate-submit-btn">
                                <i class="fas fa-file-contract"></i>
                                Proceed with eMandate Enrollment
                                <i class="fa-duotone fa-solid fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($config['environment'] === 'dev'): ?>
    <div class="modal fade override-modal" id="configModal" tabindex="-1" role="dialog" aria-labelledby="configModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="configModalLabel">
                        <i class="fas fa-sliders-h"></i> Override Configuration
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="override-info-alert mb-4">
                        <i class="fas fa-info-circle"></i>
                        <div class="override-info-alert-text">
                            <strong>Browser Storage:</strong> These overrides are stored in your browser's local storage and will persist across sessions. Leave fields empty to use default config values.
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label for="override-bearer-input">
                            <i class="fas fa-key text-muted mr-1"></i> Bearer Token Override
                        </label>
                        <textarea class="form-control" id="override-bearer-input" rows="3" placeholder="Paste your bearer token here..."></textarea>
                        <small class="form-text text-muted">Overrides bayarcash_bearer_token from config-v2.php</small>
                    </div>

                    <div class="form-group mb-4">
                        <label for="override-portal-input">
                            <i class="fas fa-door-open text-muted mr-1"></i> Portal Key Override
                        </label>
                        <input type="text" class="form-control" id="override-portal-input" placeholder="Enter portal key...">
                        <small class="form-text text-muted">Overrides bayarcash_portal_key from config-v2.php</small>
                    </div>

                    <div class="current-config-box">
                        <h6><i class="fas fa-plug"></i> Current Connection <span id="connection-mode-label">(Default)</span></h6>
                        <div id="modal-merchant-info">
                            <span class="text-muted"><i class="fas fa-spinner fa-spin"></i> Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="clear-overrides">
                        <i class="fas fa-trash-alt"></i> Clear All
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="save-overrides">
                        <i class="fas fa-check"></i> Save Overrides
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- JS -->
<script type="text/javascript" src="../assets/js/jquery-3.2.0.min.js"></script>
<script type="text/javascript" src="../assets/js/bootstrap.min.js"></script>
<script>
    function updateConfigStatus() {
        const bearerOverride = localStorage.getItem('bayarcash_bearer_token_override');
        const portalOverride = localStorage.getItem('bayarcash_portal_key_override');
        const hasOverride = (bearerOverride && bearerOverride.trim() !== '') || (portalOverride && portalOverride.trim() !== '');

        const configModeLabel = document.getElementById('config-mode-label');
        if (configModeLabel) {
            configModeLabel.textContent = hasOverride ? '(Override)' : '(Default)';
        }

        const connectionModeLabel = document.getElementById('connection-mode-label');
        if (connectionModeLabel) {
            connectionModeLabel.textContent = hasOverride ? '(Override)' : '(Default)';
        }
    }

    function loadOverrideValues() {
        const bearerInput = document.getElementById('override-bearer-input');
        const portalInput = document.getElementById('override-portal-input');

        if (!bearerInput || !portalInput) return;

        const bearerOverride = localStorage.getItem('bayarcash_bearer_token_override') || '';
        const portalOverride = localStorage.getItem('bayarcash_portal_key_override') || '';

        bearerInput.value = bearerOverride;
        portalInput.value = portalOverride;
    }

    function setHiddenFormFields() {
        const bearerOverride = localStorage.getItem('bayarcash_bearer_token_override') || '';
        const portalOverride = localStorage.getItem('bayarcash_portal_key_override') || '';

        document.getElementById('override_bearer_token').value = bearerOverride;
        document.getElementById('override_portal_key').value = portalOverride;
    }

    function togglePaymentType() {
        const toggle = document.getElementById('payment-type-toggle');
        const paymentLabel = document.getElementById('payment-label');
        const emandateLabel = document.getElementById('emandate-label');
        const hiddenPaymentType = document.getElementById('hidden_payment_type');
        const emandateFields = document.getElementById('emandate-fields');
        const paymentButtons = document.getElementById('payment-buttons');
        const emandateButtons = document.getElementById('emandate-buttons');

        if (toggle.checked) {
            // eMandate mode
            paymentLabel.classList.remove('active');
            emandateLabel.classList.add('active');
            hiddenPaymentType.value = 'emandate';
            emandateFields.classList.remove('hidden');
            paymentButtons.classList.add('hidden');
            emandateButtons.classList.remove('hidden');
        } else {
            // Payment mode
            paymentLabel.classList.add('active');
            emandateLabel.classList.remove('active');
            hiddenPaymentType.value = 'payment';
            emandateFields.classList.add('hidden');
            paymentButtons.classList.remove('hidden');
            emandateButtons.classList.add('hidden');
        }
    }

    function submitForm(paymentMethod) {
        // Clear previous split errors
        clearSplitErrors();

        // Validate splits if enabled
        const splitToggle = document.getElementById('split-toggle');
        if (splitToggle && splitToggle.checked) {
            const items = document.querySelectorAll('#split-items .split-item');
            if (items.length === 0) {
                showSplitError('Please add at least one split recipient.');
                return;
            }

            const totalAmount = parseFloat(document.getElementById('order_amount').value) || 0;
            let totalFixed = 0;
            let totalPercentage = 0;
            let hasValidSplit = false;
            let hasError = false;

            items.forEach(function(item) {
                const email = item.querySelector('.split-email');
                const type = item.querySelector('.split-type').value;
                const valueField = item.querySelector('.split-value');
                const value = parseFloat(valueField.value) || 0;

                // Reset styles
                email.style.borderColor = '';
                valueField.style.borderColor = '';

                if (!email.value.trim()) {
                    email.style.borderColor = '#e53e3e';
                    hasError = true;
                }
                if (!valueField.value || value <= 0) {
                    valueField.style.borderColor = '#e53e3e';
                    hasError = true;
                }
                if (email.value.trim() && value > 0) {
                    hasValidSplit = true;
                    if (type === 'fixed') {
                        totalFixed += value;
                    } else {
                        totalPercentage += value;
                    }
                }
            });

            if (hasError) {
                showSplitError('Please fill in all split recipient details.');
                return;
            }

            if (!hasValidSplit) {
                showSplitError('Please add at least one valid split recipient.');
                return;
            }

            // Validate total fixed amount
            if (totalFixed > totalAmount) {
                showSplitError('Total fixed split amount (RM ' + totalFixed.toFixed(2) + ') exceeds order amount (RM ' + totalAmount.toFixed(2) + ').');
                return;
            }

            // Validate total percentage
            if (totalPercentage > 100) {
                showSplitError('Total percentage split (' + totalPercentage + '%) exceeds 100%.');
                return;
            }

            // Validate combined (fixed + percentage of remaining)
            const remainingAfterFixed = totalAmount - totalFixed;
            const percentageAmount = (totalPercentage / 100) * totalAmount;
            if (totalFixed + percentageAmount > totalAmount) {
                showSplitError('Combined split amounts exceed order total.');
                return;
            }
        }

        document.getElementById('payment_method').value = paymentMethod;
        setHiddenFormFields();
        updateSplitsData();
        document.getElementById('loading-overlay').style.display = 'block';
        localStorage.setItem('formSubmitTime', Date.now());
        document.getElementById('payment-form').submit();
    }

    function showSplitError(message) {
        const container = document.getElementById('split-container');
        if (!container) return;

        clearSplitErrors();
        const errorDiv = document.createElement('div');
        errorDiv.className = 'split-error';
        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
        container.insertBefore(errorDiv, container.firstChild);
    }

    function clearSplitErrors() {
        const errors = document.querySelectorAll('.split-error');
        errors.forEach(function(el) { el.remove(); });
        const fields = document.querySelectorAll('.split-email, .split-value');
        fields.forEach(function(el) { el.style.borderColor = ''; });
    }

    function generateRandomMyKad() {
        const year = String(Math.floor(Math.random() * 30) + 70).padStart(2, '0');
        const month = String(Math.floor(Math.random() * 12) + 1).padStart(2, '0');
        const day = String(Math.floor(Math.random() * 28) + 1).padStart(2, '0');

        const placeCodes = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16'];
        const placeCode = placeCodes[Math.floor(Math.random() * placeCodes.length)];

        const sequential = String(Math.floor(Math.random() * 999) + 1).padStart(3, '0');
        const gender = Math.floor(Math.random() * 10);

        return year + month + day + placeCode + sequential + gender;
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function fetchMerchantInfo() {
        const merchantInfo = document.getElementById('modal-merchant-info');
        if (!merchantInfo) return;

        const bearerOverride = localStorage.getItem('bayarcash_bearer_token_override');
        merchantInfo.innerHTML = '<span class="text-muted"><i class="fas fa-spinner fa-spin"></i> Loading...</span>';

        const requestData = new FormData();
        requestData.append('action', 'fetch_merchant_info');
        if (bearerOverride && bearerOverride.trim() !== '') {
            requestData.append('bearer_token', bearerOverride.trim());
        }

        fetch(window.location.href, {
            method: 'POST',
            body: requestData
        })
        .then(response => response.json())
        .then(data => {
            const portalOverride = localStorage.getItem('bayarcash_portal_key_override');
            const portalKey = portalOverride && portalOverride.trim() !== '' ? portalOverride.trim() : '<?php echo htmlspecialchars($current_config['bayarcash_portal_key']); ?>';

            if (data.success && data.merchant) {
                merchantInfo.innerHTML =
                    '<div style="margin-bottom:8px;">' +
                        '<i class="fas fa-store text-muted" style="margin-right:8px;"></i>' +
                        '<strong>' + escapeHtml(data.merchant.name) + '</strong> <span class="text-muted">(' + escapeHtml(data.merchant.email) + ')</span>' +
                    '</div>' +
                    '<div>' +
                        '<i class="fas fa-key text-muted" style="margin-right:8px;"></i>' +
                        '<code style="font-size:0.8rem;">' + escapeHtml(portalKey) + '</code>' +
                    '</div>';
            } else {
                merchantInfo.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> ' + escapeHtml(data.message || 'Unable to fetch') + '</span>';
            }
        })
        .catch(error => {
            merchantInfo.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> Connection error</span>';
        });
    }

    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check' : 'exclamation-triangle') + '"></i> ' + message +
            '<button type="button" class="close" onclick="this.parentElement.remove()" aria-label="Close">' +
            '<span aria-hidden="true">&times;</span>' +
            '</button>';

        const container = document.querySelector('.container');
        const firstCard = container.querySelector('.mb-3');
        container.insertBefore(alertDiv, firstCard.nextSibling);

        setTimeout(function() {
            if (alertDiv.parentNode) {
                alertDiv.style.opacity = '0';
                setTimeout(function() {
                    if (alertDiv.parentNode) {
                        alertDiv.parentNode.removeChild(alertDiv);
                    }
                }, 300);
            }
        }, 3000);
    }

    function openModal() {
        const modal = document.getElementById('configModal');
        if (!modal) return;

        loadOverrideValues();
        fetchMerchantInfo();
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop';
        backdrop.id = 'modal-backdrop';
        document.body.appendChild(backdrop);
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.style.paddingRight = '17px';
        document.body.classList.add('modal-open');
    }

    function closeModal() {
        const modal = document.getElementById('configModal');
        if (!modal) return;

        const backdrop = document.getElementById('modal-backdrop');
        modal.style.display = 'none';
        modal.classList.remove('show');
        if (backdrop) {
            backdrop.parentNode.removeChild(backdrop);
        }
        document.body.style.paddingRight = '';
        document.body.classList.remove('modal-open');
    }

    document.addEventListener('DOMContentLoaded', function() {
        setHiddenFormFields();

        const paymentTypeToggle = document.getElementById('payment-type-toggle');
        if (paymentTypeToggle) {
            paymentTypeToggle.addEventListener('change', togglePaymentType);
        }

        togglePaymentType();

        const paymentGatewayBtns = document.querySelectorAll('.payment-gateway-btn');
        paymentGatewayBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const gateway = this.getAttribute('data-gateway');
                submitForm(gateway);
            });
        });

        // Channel Mode Selector
        const channelModeTabs = document.querySelectorAll('.channel-mode-tab');
        const paymentButtonsSection = document.getElementById('payment-buttons');
        const multiChannelPanel = document.getElementById('multi-channel-panel');
        const allChannelPanel = document.getElementById('all-channel-panel');
        const channelModeDesc = document.getElementById('channel-mode-desc');

        const modeDescriptions = {
            'single': 'Click a payment method to proceed directly',
            'multi': 'Select multiple channels, then click proceed',
            'all': 'Payment page will show all available channels'
        };

        channelModeTabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                const mode = this.getAttribute('data-mode');

                // Update active tab
                channelModeTabs.forEach(function(t) { t.classList.remove('active'); });
                this.classList.add('active');

                // Update description
                channelModeDesc.textContent = modeDescriptions[mode];

                // Show/hide panels
                paymentButtonsSection.classList.remove('hidden');
                multiChannelPanel.classList.add('hidden');
                allChannelPanel.classList.add('hidden');

                if (mode === 'single') {
                    paymentButtonsSection.classList.remove('hidden');
                } else if (mode === 'multi') {
                    paymentButtonsSection.classList.add('hidden');
                    multiChannelPanel.classList.remove('hidden');
                } else if (mode === 'all') {
                    paymentButtonsSection.classList.add('hidden');
                    allChannelPanel.classList.remove('hidden');
                }
            });
        });

        // Multi-channel checkboxes
        const multiChannelGrid = document.getElementById('multi-channel-grid');
        const selectedCountEl = document.getElementById('selected-count');
        if (multiChannelGrid) {
            multiChannelGrid.addEventListener('change', function() {
                const checked = multiChannelGrid.querySelectorAll('input[type="checkbox"]:checked');
                selectedCountEl.textContent = checked.length + ' selected';
            });
        }

        // Multi-channel submit
        const multiChannelSubmit = document.getElementById('multi-channel-submit');
        if (multiChannelSubmit) {
            multiChannelSubmit.addEventListener('click', function() {
                const checked = multiChannelGrid.querySelectorAll('input[type="checkbox"]:checked');
                if (checked.length === 0) {
                    alert('Please select at least one payment channel.');
                    return;
                }
                const values = Array.from(checked).map(function(cb) { return cb.value; });
                submitForm(values.join(','));
            });
        }

        // All channels submit
        const allChannelSubmit = document.getElementById('all-channel-submit');
        if (allChannelSubmit) {
            allChannelSubmit.addEventListener('click', function() {
                submitForm('all');
            });
        }

        // Channel tabs
        const channelTabs = document.querySelectorAll('.channel-tab');
        channelTabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');

                // Update active tab
                channelTabs.forEach(function(t) { t.classList.remove('active'); });
                this.classList.add('active');

                // Show corresponding content
                document.querySelectorAll('.channel-tab-content').forEach(function(content) {
                    content.classList.remove('active');
                });
                document.getElementById('tab-' + tabName).classList.add('active');
            });
        });

        // Split Payment
        const splitToggle = document.getElementById('split-toggle');
        const splitContainer = document.getElementById('split-container');
        const splitItems = document.getElementById('split-items');
        const splitAddBtn = document.getElementById('split-add-btn');
        const splitsData = document.getElementById('splits');
        let splitCount = 0;
        const maxSplits = 6;

        if (splitToggle) {
            splitToggle.addEventListener('change', function() {
                if (this.checked) {
                    splitContainer.classList.add('active');
                    if (splitCount === 0) addSplitItem();
                } else {
                    splitContainer.classList.remove('active');
                }
                updateSplitsData();
            });
        }

        if (splitAddBtn) {
            splitAddBtn.addEventListener('click', function() {
                if (splitCount < maxSplits) {
                    addSplitItem();
                }
            });
        }

        // Restore splits from hidden field (after form error)
        function restoreSplits() {
            const splitsField = document.getElementById('splits');
            if (!splitsField || !splitsField.value) return;

            try {
                const splits = JSON.parse(splitsField.value);
                if (Array.isArray(splits) && splits.length > 0) {
                    // Enable toggle
                    if (splitToggle) {
                        splitToggle.checked = true;
                        splitContainer.classList.add('active');
                    }

                    // Add each split item
                    splits.forEach(function(split) {
                        if (splitCount >= maxSplits) return;
                        splitCount++;

                        const item = document.createElement('div');
                        item.className = 'split-item';
                        item.setAttribute('data-split-id', splitCount);
                        item.innerHTML =
                            '<div class="split-item-header">' +
                                '<span class="split-item-title">Recipient #' + splitCount + '</span>' +
                                '<button type="button" class="split-remove-btn" onclick="removeSplitItem(this)"><i class="fas fa-times"></i></button>' +
                            '</div>' +
                            '<div class="split-row">' +
                                '<input type="email" class="form-control split-email" value="' + (split.recipient_email || '') + '" onchange="updateSplitsData()">' +
                                '<select class="form-control split-type" onchange="updateSplitsData()">' +
                                    '<option value="fixed"' + (split.type === 'fixed' ? ' selected' : '') + '>RM</option>' +
                                    '<option value="percentage"' + (split.type === 'percentage' ? ' selected' : '') + '>%</option>' +
                                '</select>' +
                                '<input type="number" class="form-control split-value" value="' + (split.value || 10) + '" min="0" step="0.01" onchange="updateSplitsData()">' +
                            '</div>';

                        splitItems.appendChild(item);
                    });

                    updateAddButton();
                }
            } catch (e) {
                console.error('Error restoring splits:', e);
            }
        }

        restoreSplits();

        const splitEmails = [
            'notification@aplikasiniaga.com', 'aina@webimpian.com', 'ramzyabc@webimpian.com',
            'alwancs110@gmail.com', 'account@webimpian.com', 'hi.tweetislamik@gmail.com',
            'masjidpadangbongor@gmail.com', 'berimakan@dailymakan.com', 'herblissmsdnbhd@gmail.com',
            'notification@bayar.cash', 'exothickdusun@gmail.com', 'nuranisnadhirah1998@gmail.com',
            'nadia@webimpian.com', 'test@gmail.com', 'ramzy-debug-x@webimpian.com'
        ];

        function getUsedEmails() {
            const used = [];
            document.querySelectorAll('#split-items .split-email').forEach(function(el) {
                if (el.value) used.push(el.value);
            });
            return used;
        }

        function getRandomEmail() {
            const used = getUsedEmails();
            const available = splitEmails.filter(function(email) {
                return used.indexOf(email) === -1;
            });
            if (available.length === 0) return splitEmails[Math.floor(Math.random() * splitEmails.length)];
            return available[Math.floor(Math.random() * available.length)];
        }

        function getRandomType() {
            return Math.random() > 0.5 ? 'fixed' : 'percentage';
        }

        function getUsedSplitTotals() {
            let totalFixed = 0;
            let totalPercentage = 0;
            document.querySelectorAll('#split-items .split-item').forEach(function(item) {
                const type = item.querySelector('.split-type').value;
                const value = parseFloat(item.querySelector('.split-value').value) || 0;
                if (type === 'fixed') {
                    totalFixed += value;
                } else {
                    totalPercentage += value;
                }
            });
            return { fixed: totalFixed, percentage: totalPercentage };
        }

        function getRandomAmount(type) {
            const orderAmount = parseFloat(document.getElementById('order_amount').value) || 100;
            const used = getUsedSplitTotals();

            if (type === 'fixed') {
                const remaining = orderAmount - used.fixed;
                if (remaining <= 0) return 1;
                const maxAmount = Math.min(remaining, 10); // Max RM 10 per split
                const minAmount = Math.min(1, maxAmount);
                return Math.floor(Math.random() * (maxAmount - minAmount + 1)) + minAmount;
            } else {
                const remaining = 100 - used.percentage;
                if (remaining <= 0) return 1;
                const maxPercent = Math.min(remaining, 10); // Max 10% per split
                const minPercent = Math.min(1, maxPercent);
                return Math.floor(Math.random() * (maxPercent - minPercent + 1)) + minPercent;
            }
        }

        function addSplitItem() {
            if (splitCount >= maxSplits) return;
            splitCount++;

            const randomEmail = getRandomEmail();
            const randomType = getRandomType();
            const randomAmount = getRandomAmount(randomType);

            const item = document.createElement('div');
            item.className = 'split-item';
            item.setAttribute('data-split-id', splitCount);
            item.innerHTML =
                '<div class="split-item-header">' +
                    '<span class="split-item-title">Recipient #' + splitCount + '</span>' +
                    '<button type="button" class="split-remove-btn" onclick="removeSplitItem(this)"><i class="fas fa-times"></i></button>' +
                '</div>' +
                '<div class="split-row">' +
                    '<input type="email" class="form-control split-email" value="' + randomEmail + '" onchange="updateSplitsData()">' +
                    '<select class="form-control split-type" onchange="updateSplitsData()">' +
                        '<option value="fixed"' + (randomType === 'fixed' ? ' selected' : '') + '>RM</option>' +
                        '<option value="percentage"' + (randomType === 'percentage' ? ' selected' : '') + '>%</option>' +
                    '</select>' +
                    '<input type="number" class="form-control split-value" value="' + randomAmount + '" min="0" step="0.01" onchange="updateSplitsData()">' +
                '</div>';

            splitItems.appendChild(item);
            updateAddButton();
            updateSplitsData();
        }

        window.removeSplitItem = function(btn) {
            btn.closest('.split-item').remove();
            splitCount--;
            updateAddButton();
            renumberSplitItems();
            updateSplitsData();
        };

        function renumberSplitItems() {
            const items = splitItems.querySelectorAll('.split-item');
            items.forEach(function(item, index) {
                item.querySelector('.split-item-title').textContent = 'Recipient #' + (index + 1);
            });
        }

        function updateAddButton() {
            if (splitAddBtn) {
                splitAddBtn.disabled = splitCount >= maxSplits;
                splitAddBtn.innerHTML = splitCount >= maxSplits
                    ? '<i class="fas fa-ban"></i> Maximum 6 recipients reached'
                    : '<i class="fas fa-plus"></i> Add Recipient';
            }
        }

        window.updateSplitsData = function() {
            const toggle = document.getElementById('split-toggle');
            const splitsField = document.getElementById('splits');
            const itemsContainer = document.getElementById('split-items');

            if (!toggle || !toggle.checked || !splitsField) {
                if (splitsField) splitsField.value = '';
                return;
            }

            const splits = [];
            const items = itemsContainer.querySelectorAll('.split-item');
            items.forEach(function(item) {
                const email = item.querySelector('.split-email').value.trim();
                const type = item.querySelector('.split-type').value;
                const value = parseFloat(item.querySelector('.split-value').value) || 0;

                if (email && value > 0) {
                    splits.push({
                        recipient_email: email,
                        type: type,
                        value: value
                    });
                }
            });

            splitsField.value = splits.length > 0 ? JSON.stringify(splits) : '';
        };

        const emandateSubmitBtn = document.getElementById('emandate-submit-btn');
        if (emandateSubmitBtn) {
            emandateSubmitBtn.addEventListener('click', function() {
                submitForm('emandate');
            });
        }

        // MyKad field validation
        const payerIdField = document.getElementById('payer_id');
        if (payerIdField) {
            payerIdField.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');

                if (this.value.length > 12) {
                    this.value = this.value.substring(0, 12);
                }
            });

            payerIdField.addEventListener('dblclick', function() {
                this.value = generateRandomMyKad();
                showAlert('info', 'Random MyKad number generated!');
            });

            payerIdField.setAttribute('title', 'Double-click to generate random MyKad number');
        }

        const configButton = document.getElementById('config-button');
        if (configButton) {
            updateConfigStatus();

            configButton.addEventListener('click', function(e) {
                e.preventDefault();
                openModal();
            });

            const closeButtons = document.querySelectorAll('[data-dismiss="modal"]');
            closeButtons.forEach(function(button) {
                button.addEventListener('click', closeModal);
            });

            document.addEventListener('click', function(e) {
                if (e.target && e.target.id === 'modal-backdrop') {
                    closeModal();
                }
            });

            const saveButton = document.getElementById('save-overrides');
            if (saveButton) {
                saveButton.addEventListener('click', function() {
                    const bearerInput = document.getElementById('override-bearer-input');
                    const portalInput = document.getElementById('override-portal-input');

                    if (!bearerInput || !portalInput) return;

                    const bearerValue = bearerInput.value.trim();
                    const portalValue = portalInput.value.trim();

                    if (bearerValue) {
                        localStorage.setItem('bayarcash_bearer_token_override', bearerValue);
                    } else {
                        localStorage.removeItem('bayarcash_bearer_token_override');
                    }

                    if (portalValue) {
                        localStorage.setItem('bayarcash_portal_key_override', portalValue);
                    } else {
                        localStorage.removeItem('bayarcash_portal_key_override');
                    }

                    updateConfigStatus();
                    setHiddenFormFields();

                    fetchMerchantInfo();

                    closeModal();

                    showAlert('success', 'Configuration overrides saved successfully!');
                });
            }

            const clearButton = document.getElementById('clear-overrides');
            if (clearButton) {
                clearButton.addEventListener('click', function() {
                    if (confirm('Are you sure you want to clear all configuration overrides?')) {
                        localStorage.removeItem('bayarcash_bearer_token_override');
                        localStorage.removeItem('bayarcash_portal_key_override');

                        const bearerInput = document.getElementById('override-bearer-input');
                        const portalInput = document.getElementById('override-portal-input');

                        if (bearerInput) bearerInput.value = '';
                        if (portalInput) portalInput.value = '';

                        updateConfigStatus();
                        setHiddenFormFields();

                        fetchMerchantInfo();

                        closeModal();

                        showAlert('warning', 'All configuration overrides cleared!');
                    }
                });
            }
        }
    });

    window.addEventListener('load', function() {
        document.getElementById('loading-overlay').style.display = 'none';
    });

    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            document.getElementById('loading-overlay').style.display = 'none';
        }

        const submitTime = localStorage.getItem('formSubmitTime');
        if (submitTime) {
            const currentTime = Date.now();
            const timeDifference = currentTime - submitTime;

            if (timeDifference < 300000) {
                document.getElementById('loading-overlay').style.display = 'none';
            }

            localStorage.removeItem('formSubmitTime');
        }
    });
</script>
</body>
</html>