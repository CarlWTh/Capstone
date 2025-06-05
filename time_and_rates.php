
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time & Rates / Bandwidth Control - Bottle Recycling System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Using the existing CSS variables and styles from styles.css */
        :root {
            --primary-color: #00a698;
            --primary-dark: #008e82;
            --accent-color: #ff7c43;
            --accent-dark: #e86e3a;
            --text-color: #2c3e50;
            --light-text: #7f8c8d;
            --danger-color: #e74c3c;
            --success-color: #27ae60;
            --bg-color: #f7f9fa;
            --card-bg: #ffffff;
            --input-bg: #f8f9fa;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            transition: var(--transition);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo h1 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
        }

        .sidebar nav ul {
            list-style: none;
            padding: 1rem 0;
        }

        .sidebar nav li {
            margin-bottom: 0.5rem;
        }

        .sidebar nav a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            transition: var(--transition);
        }

        .sidebar nav a:hover,
        .sidebar nav li.active a {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar nav a i {
            margin-right: 1rem;
            font-size: 1.25rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .main-header h2 {
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .main-header h2 i {
            margin-right: 0.75rem;
            color: var(--primary-color);
        }

        /* Cards */
        .card {
            background-color: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .card-header h3 i {
            margin-right: 0.75rem;
            color: var(--primary-color);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: var(--input-bg);
            color: var(--text-color);
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 166, 152, 0.1);
        }

        /* Time Picker Grid */
        .time-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .time-slot {
            background-color: var(--input-bg);
            border: 2px solid #ddd;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .time-slot:hover {
            border-color: var(--primary-color);
            background-color: rgba(0, 166, 152, 0.05);
        }

        .time-slot.active {
            border-color: var(--primary-color);
            background-color: rgba(0, 166, 152, 0.1);
        }

        .time-slot h4 {
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .time-slot p {
            color: var(--light-text);
            font-size: 0.875rem;
        }

        /* Rate Cards */
        .rate-cards {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .rate-card {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2rem;
            border-radius: 16px;
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .rate-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
        }

        .rate-card h3 {
            font-size: 1.75rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .rate-card .price {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .rate-card .unit {
            font-size: 1.2rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .rate-card .description {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: 0.5rem;
            position: relative;
            z-index: 1;
        }

        /* Rate Configuration Form */
        .rate-config {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        /* Bandwidth Controls */
        .bandwidth-control {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .bandwidth-slider {
            background-color: var(--input-bg);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #ddd;
        }

        .slider-container {
            margin: 1rem 0;
        }

        .slider {
            width: 100%;
            height: 8px;
            border-radius: 5px;
            background: #ddd;
            outline: none;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .slider:hover {
            opacity: 1;
        }

        .slider::-webkit-slider-thumb {
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary-color);
            cursor: pointer;
        }

        .slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary-color);
            cursor: pointer;
            border: none;
        }

        .bandwidth-display {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .bandwidth-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        /* Status Indicators */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .status-card {
            background-color: var(--card-bg);
            padding: 1rem;
            border-radius: 12px;
            border-left: 4px solid var(--primary-color);
            box-shadow: var(--shadow);
        }

        .status-card.warning {
            border-left-color: var(--accent-color);
        }

        .status-card.danger {
            border-left-color: var(--danger-color);
        }

        .status-card.success {
            border-left-color: var(--success-color);
        }

        .status-card h4 {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }

        .status-card h4 i {
            margin-right: 0.5rem;
        }

        .status-value {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .status-label {
            color: var(--light-text);
            font-size: 0.875rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 166, 152, 0.2);
        }

        .btn-secondary {
            background-color: #f8f9fa;
            color: var(--text-color);
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background-color: #e9ecef;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .btn-group {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: var(--primary-color);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }

            .main-content {
                margin-left: 70px;
                padding: 1rem;
            }

            .main-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .time-grid {
                grid-template-columns: 1fr;
            }

            .rate-cards {
                grid-template-columns: 1fr;
            }

            .bandwidth-control {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 0.5rem;
            }

            .card-body {
                padding: 1rem;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <h1></h1>
                    <span class="logo-short"></span>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
            </div>
            <nav>
                <ul class="list-unstyled">
                    <li class="active">
                        <a href="dashboard.php" class="">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="">
                        <a href="bottle_deposits.php">
                            <i class="bi bi-recycle" ></i>
                            <span>Bottle Deposits</span>
                        </a>
                    </li>
                    <li>
                        <a href="vouchers.php">
                            <i class="bi bi-ticket-perforated"></i>
                            <span>Vouchers</span>
                        </a>
                    </li>
                    <li>
                        <a href="bins.php">
                            <i class="bi bi-trash"></i>
                            <span>Trash Bins</span>
                        </a>
                    </li>
                    <li>
                        <a href="student_sessions.php">
                            <i class="bi bi-phone"></i>
                            <span class="menu-text">Student Sessions</span>
                        </a>
                    </li>
                    <li>
                        <a href="sessions.php">
                            <i class="bi bi-wifi"></i>
                            <span>Internet Sessions</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php">
                            <i class="bi bi-people"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="activity_logs.php">
                            <i class="bi bi-clock-history"></i>
                            <span>Activity Logs</span>
                        </a>
                    </li>
                    <li>
                        <a href="time_and_rates.php">
                            <i class="bi bi-clock"></i>
                            <span>TIME AND RATES</span>
                        </a>
                    </li>
                    <li>
                        <a href="logout.php">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="main-header">
                <h2><i class="fas fa-clock"></i>Time & Rates Management</h2>
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="saveSettings()">
                        <i class="fas fa-save"></i>Save Changes
                    </button>
                    <button class="btn btn-secondary" onclick="resetSettings()">
                        <i class="fas fa-undo"></i>Reset
                    </button>
                </div>
            </div>

            <!-- Time Slots Configuration -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-business-time"></i>Operating Hours</h3>
                    <label class="toggle-switch">
                        <input type="checkbox" id="enableTimeSlots" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="card-body">
                    <div class="time-grid">
                        <div class="time-slot active" onclick="toggleTimeSlot(this)">
                            <h4>Morning</h4>
                            <p>6:00 AM - 12:00 PM</p>
                            <small>Peak Hours</small>
                        </div>
                        <div class="time-slot active" onclick="toggleTimeSlot(this)">
                            <h4>Afternoon</h4>
                            <p>12:00 PM - 6:00 PM</p>
                            <small>Regular Hours</small>
                        </div>
                        <div class="time-slot" onclick="toggleTimeSlot(this)">
                            <h4>Evening</h4>
                            <p>6:00 PM - 10:00 PM</p>
                            <small>Off-Peak Hours</small>
                        </div>
                        <div class="time-slot" onclick="toggleTimeSlot(this)">
                            <h4>Night</h4>
                            <p>10:00 PM - 6:00 AM</p>
                            <small>Maintenance</small>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <div class="form-group">
                            <label>Daily Start Time</label>
                            <input type="time" value="06:00">
                        </div>
                        <div class="form-group">
                            <label>Daily End Time</label>
                            <input type="time" value="22:00">
                        </div>
                        <div class="form-group">
                            <label>Time Zone</label>
                            <select>
                                <option>Asia/Manila (GMT+8)</option>
                                <option>UTC (GMT+0)</option>
                                <option>America/New_York (GMT-5)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rate Configuration -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-clock"></i>Processing Time Rate</h3>
                </div>
                <div class="card-body">
                    <div class="rate-cards">
                        <div class="rate-card">
                            <h3><i class="fas fa-bottle-water"></i>Bottle Processing</h3>
                            <div class="price" id="processingTime">2</div>
                            <div class="unit">minutes per bottle</div>
                            <div class="description">Standard processing time for all bottle types</div>
                        </div>
                    </div>

                    <div class="rate-config">
                        <div class="form-group">
                            <label>Processing Time (minutes)</label>
                            <input type="number" id="processingTimeInput" value="5" step="0.5" min="1" max="60" oninput="updateProcessingTime(this.value)">
                        </div>
                        <div class="form-group">
                            <label>Peak Hour Multiplier</label>
                            <input type="number" value="1.2" step="0.1" min="1.0" max="3.0">
                        </div>
                        <div class="form-group">
                            <label>Off-Peak Multiplier</label>
                            <input type="number" value="0.8" step="0.1" min="0.5" max="1.0">
                        </div>
                        <div class="form-group">
                            <label>Maintenance Multiplier</label>
                            <input type="number" value="2.0" step="0.1" min="1.0" max="5.0">
                        </div>
                    </div>

                    <div style="margin-top: 2rem; padding: 1rem; background-color: var(--input-bg); border-radius: 8px;">
                        <h4 style="margin-bottom: 1rem; color: var(--text-color);"><i class="fas fa-info-circle"></i> Time Estimates</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div>
                                <strong>Peak Hours:</strong><br>
                                <span id="peakTime">6.0 minutes per bottle</span>
                            </div>
                            <div>
                                <strong>Regular Hours:</strong><br>
                                <span id="regularTime">5.0 minutes per bottle</span>
                            </div>
                            <div>
                                <strong>Off-Peak Hours:</strong><br>
                                <span id="offPeakTime">4.0 minutes per bottle</span>
                            </div>
                            <div>
                                <strong>Maintenance:</strong><br>
                                <span id="maintenanceTime">10.0 minutes per bottle</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bandwidth Control Section -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-wifi"></i>Bandwidth Control</h3>
                    <div class="status-value" style="color: var(--success-color);">
                        <i class="fas fa-circle"></i> Online
                    </div>
                </div>
                <div class="card-body">
                    <!-- Status Grid -->
                    <div class="status-grid">
                        <div class="status-card success">
                            <h4><i class="fas fa-download"></i>Download Speed</h4>
                            <div class="status-value" id="downloadSpeed">85.2</div>
                            <div class="status-label">Mbps</div>
                        </div>
                        <div class="status-card warning">
                            <h4><i class="fas fa-upload"></i>Upload Speed</h4>
                            <div class="status-value" id="uploadSpeed">22.8</div>
                            <div class="status-label">Mbps</div>
                        </div>
                        <div class="status-card">
                            <h4><i class="fas fa-signal"></i>Latency</h4>
                            <div class="status-value" id="latency">12</div>
                            <div class="status-label">ms</div>
                        </div>
                        <div class="status-card">
                            <h4><i class="fas fa-users"></i>Active Users</h4>
                            <div class="status-value" id="activeUsers">24</div>
                            <div class="status-label">connected</div>
                        </div>
                    </div>

                    <!-- Bandwidth Controls -->
                    <div class="bandwidth-control">
                        <div class="bandwidth-slider">
                            <h4><i class="fas fa-download"></i>Download Bandwidth Limit</h4>
                            <div class="slider-container">
                                <input type="range" min="0" max="100" value="80" class="slider" id="downloadLimit" oninput="updateBandwidth('download', this.value)">
                            </div>
                            <div class="bandwidth-display">
                                <span>Limit:</span>
                                <span class="bandwidth-value" id="downloadValue">80 Mbps</span>
                            </div>
                        </div>

                        <div class="bandwidth-slider">
                            <h4><i class="fas fa-upload"></i>Upload Bandwidth Limit</h4>
                            <div class="slider-container">
                                <input type="range" min="0" max="50" value="25" class="slider" id="uploadLimit" oninput="updateBandwidth('upload', this.value)">
                            </div>
                            <div class="bandwidth-display">
                                <span>Limit:</span>
                                <span class="bandwidth-value" id="uploadValue">25 Mbps</span>
                            </div>
                        </div>

                        <div class="bandwidth-slider">
                            <h4><i class="fas fa-users"></i>Max Concurrent Users</h4>
                            <div class="slider-container">
                                <input type="range" min="1" max="100" value="50" class="slider" id="userLimit" oninput="updateBandwidth('users', this.value)">
                            </div>
                            <div class="bandwidth-display">
                                <span>Limit:</span>
                                <span class="bandwidth-value" id="userValue">50 users</span>
                            </div>
                        </div>

                        <div class="bandwidth-slider">
                            <h4><i class="fas fa-chart-line"></i>Quality of Service</h4>
                            <div class="form-group">
                                <select onchange="updateQoS(this.value)">
                                    <option value="high">High Priority</option>
                                    <option value="medium" selected>Medium Priority</option>
                                    <option value="low">Low Priority</option>
                                    <option value="auto">Automatic</option>
                                </select>
                            </div>
                            <div class="bandwidth-display">
                                <span>Current:</span>
                                <span class="bandwidth-value" id="qosValue">Medium Priority</span>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Controls -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 2rem;">
                        <div class="form-group">
                            <label>Traffic Shaping</label>
                            <label class="toggle-switch">
                                <input type="checkbox" id="trafficShaping" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label>Auto-scaling</label>
                            <label class="toggle-switch">
                                <input type="checkbox" id="autoScaling">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label>Monitoring Interval</label>
                            <select>
                                <option>Real-time</option>
                                <option>30 seconds</option>
                                <option selected>1 minute</option>
                                <option>5 minutes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Alert Threshold (%)</label>
                            <input type="number" value="90" min="50" max="100">
                        </div>
                    </div>

                    <div class="btn-group" style="margin-top: 2rem;">
                        <button class="btn btn-primary" onclick="applyBandwidthSettings()">
                            <i class="fas fa-check"></i>Apply Settings
                        </button>
                        <button class="btn btn-secondary" onclick="testConnection()">
                            <i class="fas fa-vial"></i>Test Connection
                        </button>
                        <button class="btn btn-danger" onclick="resetBandwidth()">
                            <i class="fas fa-power-off"></i>Reset Limits
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        // Time slot toggle functionality
function toggleTimeSlot(element) {
   element.classList.toggle('active');
}

// Bandwidth control functions
function updateBandwidth(type, value) {
   const valueElement = document.getElementById(type + 'Value');
   if (type === 'download' || type === 'upload') {
       valueElement.textContent = value + ' Mbps';
   } else if (type === 'users') {
       valueElement.textContent = value + ' users';
   }
}

function updateQoS(value) {
   const qosValue = document.getElementById('qosValue');
   const displayValue = {
       'high': 'High Priority',
       'medium': 'Medium Priority',
       'low': 'Low Priority',
       'auto': 'Automatic'
   };
   qosValue.textContent = displayValue[value];
}

function saveSettings() {
   // Simulate save operation
   showNotification('Settings saved successfully!', 'success');
}

function resetSettings() {
   // Reset all form values
   document.getElementById('downloadLimit').value = 80;
   document.getElementById('uploadLimit').value = 25;
   document.getElementById('userLimit').value = 50;
   updateBandwidth('download', 80);
   updateBandwidth('upload', 25);
   updateBandwidth('users', 50);
   showNotification('Settings reset to default values', 'info');
}

function applyBandwidthSettings() {
   showNotification('Bandwidth settings applied successfully!', 'success');
}

function testConnection() {
   showNotification('Connection test started...', 'info');
   // Simulate test
   setTimeout(() => {
       showNotification('Connection test completed - All systems operational', 'success');
   }, 2000);
}

function resetBandwidth() {
   if (confirm('Are you sure you want to reset all bandwidth limits?')) {
       resetSettings();
   }
}

function showNotification(message, type) {
   // Create notification element
   const notification = document.createElement('div');
   notification.className = `alert alert-${type}`;
   notification.textContent = message;
   notification.style.position = 'fixed';
   notification.style.top = '20px';
   notification.style.right = '20px';
   notification.style.zIndex = '9999';
   notification.style.minWidth = '300px';
   notification.style.padding = '1rem';
   notification.style.borderRadius = '8px';
   notification.style.boxShadow = 'var(--shadow)';
   
   // Set notification colors based on type
   if (type === 'success') {
       notification.style.backgroundColor = '#d4edda';
       notification.style.color = '#155724';
       notification.style.border = '1px solid #c3e6cb';
   } else if (type === 'info') {
       notification.style.backgroundColor = '#d1ecf1';
       notification.style.color = '#0c5460';
       notification.style.border = '1px solid #bee5eb';
   } else if (type === 'warning') {
       notification.style.backgroundColor = '#fff3cd';
       notification.style.color = '#856404';
       notification.style.border = '1px solid #ffeaa7';
   } else if (type === 'error') {
       notification.style.backgroundColor = '#f8d7da';
       notification.style.color = '#721c24';
       notification.style.border = '1px solid #f5c6cb';
   }

   document.body.appendChild(notification);

   // Auto remove after 3 seconds
   setTimeout(() => {
       if (notification.parentNode) {
           notification.parentNode.removeChild(notification);
       }
   }, 3000);
}

// Real-time status updates simulation
function updateStatus() {
   // Simulate fluctuating network values
   const downloadSpeed = (Math.random() * 20 + 70).toFixed(1);
   const uploadSpeed = (Math.random() * 10 + 20).toFixed(1);
   const latency = Math.floor(Math.random() * 15 + 8);
   const activeUsers = Math.floor(Math.random() * 10 + 20);

   document.getElementById('downloadSpeed').textContent = downloadSpeed;
   document.getElementById('uploadSpeed').textContent = uploadSpeed;
   document.getElementById('latency').textContent = latency;
   document.getElementById('activeUsers').textContent = activeUsers;
}

// Initialize status updates
setInterval(updateStatus, 5000);

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
   // Set initial values
   updateBandwidth('download', 80);
   updateBandwidth('upload', 25);
   updateBandwidth('users', 50);
   
   // Start status updates
   updateStatus();
   
   console.log('Bottle Recycling System Dashboard Initialized');
});

// Additional utility functions
function exportSettings() {
   const settings = {
       timeSlots: {
           enabled: document.getElementById('enableTimeSlots').checked,
           morning: document.querySelector('.time-slot:nth-child(1)').classList.contains('active'),
           afternoon: document.querySelector('.time-slot:nth-child(2)').classList.contains('active'),
           evening: document.querySelector('.time-slot:nth-child(3)').classList.contains('active'),
           night: document.querySelector('.time-slot:nth-child(4)').classList.contains('active')
       },
       bandwidth: {
           downloadLimit: document.getElementById('downloadLimit').value,
           uploadLimit: document.getElementById('uploadLimit').value,
           userLimit: document.getElementById('userLimit').value,
           trafficShaping: document.getElementById('trafficShaping').checked,
           autoScaling: document.getElementById('autoScaling').checked
       }
   };
   
   const dataStr = JSON.stringify(settings, null, 2);
   const dataBlob = new Blob([dataStr], {type: 'application/json'});
   
   const link = document.createElement('a');
   link.href = URL.createObjectURL(dataBlob);
   link.download = 'recycling-system-settings.json';
   link.click();
   
   showNotification('Settings exported successfully!', 'success');
}

function importSettings(event) {
   const file = event.target.files[0];
   if (!file) return;
   
   const reader = new FileReader();
   reader.onload = function(e) {
       try {
           const settings = JSON.parse(e.target.result);
           
           // Apply imported settings
           document.getElementById('enableTimeSlots').checked = settings.timeSlots.enabled;
           document.getElementById('downloadLimit').value = settings.bandwidth.downloadLimit;
           document.getElementById('uploadLimit').value = settings.bandwidth.uploadLimit;
           document.getElementById('userLimit').value = settings.bandwidth.userLimit;
           document.getElementById('trafficShaping').checked = settings.bandwidth.trafficShaping;
           document.getElementById('autoScaling').checked = settings.bandwidth.autoScaling;
           
           // Update displays
           updateBandwidth('download', settings.bandwidth.downloadLimit);
           updateBandwidth('upload', settings.bandwidth.uploadLimit);
           updateBandwidth('users', settings.bandwidth.userLimit);
           
           showNotification('Settings imported successfully!', 'success');
       } catch (error) {
           showNotification('Error importing settings: Invalid file format', 'error');
       }
   };
   reader.readAsText(file);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
   if (e.ctrlKey || e.metaKey) {
       switch(e.key) {
           case 's':
               e.preventDefault();
               saveSettings();
               break;
           case 'r':
               e.preventDefault();
               resetSettings();
               break;
       }
   }
});
    </script>