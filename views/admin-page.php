<script src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/chart.min.js' ); ?>"></script>
<script src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/chartjs-plugin-datalabels.min.js' ); ?>"></script>
<style>
    /* Hide GF framework page title (h2.gf_admin_page_title) - keep only our h1 with search icon */
    .gf_admin_page_title {
        display: none !important;
    }
    
    /* Dashboard Layout */
    .dashboard-container {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .left-column {
        display: grid;
        grid-template-rows: auto 1fr;
        gap: 15px;
    }
    
    .stat-blocks {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 15px;
    }
    
    .stat-block {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 12px;
        padding: 16px 20px;
        text-align: left;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        min-height: 80px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    
    .stat-block:nth-child(2) {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        box-shadow: 0 4px 15px rgba(240, 147, 251, 0.2);
    }
    
    .stat-block:nth-child(3) {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        box-shadow: 0 4px 15px rgba(79, 172, 254, 0.2);
    }
    
    .stat-block:nth-child(4) {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        box-shadow: 0 4px 15px rgba(67, 233, 123, 0.2);
    }
    
    .stat-block:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }
    
    .stat-block:nth-child(2):hover {
        box-shadow: 0 8px 25px rgba(240, 147, 251, 0.3);
    }
    
    .stat-block:nth-child(3):hover {
        box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
    }
    
    .stat-block:nth-child(4):hover {
        box-shadow: 0 8px 25px rgba(67, 233, 123, 0.3);
    }
    
    .stat-block::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: translate(20px, -20px);
    }
    
    .stat-block::after {
        content: '';
        position: absolute;
        bottom: 8px;
        right: 12px;
        width: 20px;
        height: 20px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 4px;
        z-index: 1;
    }
    
    .stat-block:first-child::after {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 24 24'%3E%3Cpath d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z'/%3E%3C/svg%3E");
        background-size: 14px;
        background-repeat: no-repeat;
        background-position: center;
        background-color: transparent;
    }
    
    .stat-block:nth-child(2)::after {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 24 24'%3E%3Cpath d='M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z'/%3E%3C/svg%3E");
        background-size: 14px;
        background-repeat: no-repeat;
        background-position: center;
        background-color: transparent;
    }
    
    .stat-block:nth-child(3)::after {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 24 24'%3E%3Cpath d='M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z'/%3E%3C/svg%3E");
        background-size: 14px;
        background-repeat: no-repeat;
        background-position: center;
        background-color: transparent;
    }
    
    .stat-block:nth-child(4)::after {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 24 24'%3E%3Cpath d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z'/%3E%3C/svg%3E");
        background-size: 14px;
        background-repeat: no-repeat;
        background-position: center;
        background-color: transparent;
    }
    
    .stat-number {
        font-size: 24px;
        font-weight: 700;
        color: #ffffff;
        line-height: 1;
        margin-bottom: 4px;
        position: relative;
        z-index: 2;
    }
    
    .stat-label {
        font-size: 11px;
        color: rgba(255, 255, 255, 0.9);
        text-transform: uppercase;
        letter-spacing: 0.8px;
        font-weight: 600;
        position: relative;
        z-index: 2;
        margin-bottom: 2px;
    }
    
    .stat-change {
        font-size: 10px;
        color: rgba(255, 255, 255, 0.7);
        font-weight: 500;
        position: relative;
        z-index: 2;
    }
    
    .chart-container {
        background: #fff;
        border: 1px solid #c3c4c7;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,.05);
        min-height: 350px;
        position: relative;
        display: flex;
        flex-direction: column;
        overflow: visible;
    }
    
    .chart-container canvas {
        max-height: 280px !important;
        max-width: 100% !important;
    }
    
    .chart-title {
        font-size: 16px;
        font-weight: 600;
        color: #1d2327;
        margin-bottom: 15px;
        text-align: center;
    }
    
    /* Tab Navigation */
    .nav-tab-wrapper {
        border-bottom: 1px solid #c3c4c7;
        margin-bottom: 20px;
    }
    
    .nav-tab {
        background: #f6f7f7;
        border: 1px solid #c3c4c7;
        border-bottom: none;
        color: #646970;
        text-decoration: none;
        padding: 12px 20px;
        margin-right: 5px;
        border-radius: 4px 4px 0 0;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    
    .nav-tab:hover {
        background: #fff;
        color: #1d2327;
    }
    
    .nav-tab-active {
        background: #fff;
        color: #1d2327;
        border-bottom: 1px solid #fff;
        margin-bottom: -1px;
        font-weight: 600;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    /* Table Styles */
    .form-locator-table-wrapper {
        background: #fff;
        border: 1px solid #c3c4c7;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,.05);
    }
    
    .form-locator-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }
    
    .form-locator-table th {
        background: #f6f7f7;
        border-bottom: 1px solid #c3c4c7;
        padding: 15px 12px;
        text-align: left;
        font-weight: 600;
        color: #1d2327;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .form-locator-table td {
        padding: 15px 12px;
        border-bottom: 1px solid #f0f0f1;
        vertical-align: top;
    }
    
    .form-locator-table tr:last-child td {
        border-bottom: none;
    }
    
    .form-locator-table tr:hover {
        background: #f6f7f7;
    }
    
    .form-id-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        margin: 2px 4px 2px 0;
        color: #fff;
    }
    
    .form-id-shortcode {
        background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
    }
    
    .form-id-block {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    }
    
    .form-id-pagebuilder {
        background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
    }
    
    .form-status {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
        margin-left: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-active {
        background: #dcfce7;
        color: #166534;
    }
    
    .status-inactive {
        background: #fef3c7;
        color: #92400e;
    }
    
    .status-trashed {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .status-deleted {
        background: #f3f4f6;
        color: #374151;
    }
    
    .status-unknown {
        background: #e5e7eb;
        color: #6b7280;
    }
    
    .login-form-indicator {
        color: #2563eb;
        font-weight: 600;
    }
    
    .no-forms-message {
        text-align: center;
        padding: 60px 20px;
        color: #646970;
        font-style: italic;
        font-size: 16px;
    }
    
    /* Pie chart legend - 3 columns (custom HTML legend) */
    .forms-chart-legend {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6px 20px;
        margin: 15px 0 0 0;
        padding: 0 4px 0 0;
        list-style: none;
    }
    
    .forms-legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #646970;
        font-size: 12px;
    }
    
    .forms-legend-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    
    .forms-legend-label {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    @media (max-width: 1200px) {
        .forms-chart-legend {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .forms-chart-legend {
            grid-template-columns: 1fr;
        }
    }
    
    /* Responsive Design */
    @media (max-width: 1200px) {
        .dashboard-container {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 1200px) {
        .stat-blocks {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .stat-blocks {
            grid-template-columns: 1fr;
            gap: 10px;
        }
        
        .chart-container {
            height: 300px;
        }
        
        .stat-block {
            min-height: 70px;
            padding: 14px 18px;
        }
        
        .stat-number {
            font-size: 20px;
        }
        
        .stat-label {
            font-size: 10px;
        }
    }
</style>

<div class="wrap gform_page">
    <h1 class="wp-heading-inline">
        <i class="gform-icon gform-icon--search"></i>
        <?php esc_html_e('Form Locator', 'form-locator-for-gravity-forms'); ?>
    </h1>
    
    <hr class="wp-header-end">
    
    <!-- Tab Navigation -->
    <div class="nav-tab-wrapper">
        <a href="#dashboard" class="nav-tab nav-tab-active" id="dashboard-tab">
            <?php esc_html_e('Dashboard', 'form-locator-for-gravity-forms'); ?>
        </a>
        <a href="#details" class="nav-tab" id="details-tab">
            <?php esc_html_e('Form Details', 'form-locator-for-gravity-forms'); ?>
        </a>
    </div>
    
    <!-- Dashboard Tab Content -->
    <div id="dashboard-content" class="tab-content active">
        <div class="dashboard-container">
            <!-- Left Column -->
            <div class="left-column">
                <!-- Stat Blocks -->
                <div class="stat-blocks">
                    <div class="stat-block">
                        <div class="stat-number"><?php echo esc_html($total_posts_scanned); ?></div>
                        <div class="stat-label"><?php esc_html_e('Posts Scanned', 'form-locator-for-gravity-forms'); ?></div>
                        <div class="stat-change"><?php esc_html_e('Content scanned', 'form-locator-for-gravity-forms'); ?></div>
                    </div>
                    <div class="stat-block">
                        <div class="stat-number"><?php echo esc_html(count($gf_pages)); ?></div>
                        <div class="stat-label"><?php esc_html_e('Embedded Forms', 'form-locator-for-gravity-forms'); ?></div>
                        <div class="stat-change"><?php esc_html_e('Pages with forms', 'form-locator-for-gravity-forms'); ?></div>
                    </div>
                    <div class="stat-block">
                        <div class="stat-number"><?php echo esc_html($active_forms ?? 0); ?></div>
                        <div class="stat-label"><?php esc_html_e('Active Forms', 'form-locator-for-gravity-forms'); ?></div>
                        <div class="stat-change"><?php esc_html_e('Ready to use', 'form-locator-for-gravity-forms'); ?></div>
                    </div>
                    <div class="stat-block">
                        <div class="stat-number"><?php echo esc_html($inactive_forms ?? 0); ?></div>
                        <div class="stat-label"><?php esc_html_e('Inactive Forms', 'form-locator-for-gravity-forms'); ?></div>
                        <div class="stat-change"><?php esc_html_e('Deactivated', 'form-locator-for-gravity-forms'); ?></div>
                    </div>
                </div>
                
                <!-- Line Chart -->
                <div class="chart-container">
                    <div class="chart-title"><?php esc_html_e('Embedded Form Entries Over Time', 'form-locator-for-gravity-forms'); ?></div>
                    <div style="position: relative; height: 280px; width: 100%;">
                        <canvas id="entriesChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Pie Chart -->
            <div class="chart-container">
                <div class="chart-title"><?php esc_html_e('Entries by Form', 'form-locator-for-gravity-forms'); ?></div>
                <div id="formsChartWrapper" style="position: relative; height: 280px; width: 100%;">
                    <canvas id="formsChart"></canvas>
                </div>
                <div id="formsChartLegend" class="forms-chart-legend"></div>
            </div>
        </div>
    </div>
    
    <!-- Details Tab Content -->
    <div id="details-content" class="tab-content">
        <?php if ($total_posts_scanned > 0): ?>
            <div class="notice notice-success inline">
                <p>
                    <strong><?php esc_html_e('Scan Complete:', 'form-locator-for-gravity-forms'); ?></strong>
                    <?php 
                    if (count($gf_pages) > 0) {
                        printf(
                            esc_html__('Found %d pages using Gravity Forms.', 'form-locator-for-gravity-forms'),
                            count($gf_pages)
                        );
                    } else {
                        esc_html_e('No pages with Gravity Forms were found.', 'form-locator-for-gravity-forms');
                    }
                    ?>
                </p>
            </div>
        <?php else: ?>
            <div class="notice notice-warning inline">
                <p>
                    <strong><?php esc_html_e('No Content Found:', 'form-locator-for-gravity-forms'); ?></strong>
                    <?php esc_html_e('No content was found to scan.', 'form-locator-for-gravity-forms'); ?>
                </p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($gf_pages)): ?>
            <div class="form-locator-table-wrapper">
                <table class="form-locator-table">
                    <thead>
                        <tr>
                            <th style="width: 8%;"><?php esc_html_e('Post ID', 'form-locator-for-gravity-forms'); ?></th>
                            <th style="width: 10%;"><?php esc_html_e('Type', 'form-locator-for-gravity-forms'); ?></th>
                            <th style="width: 25%;"><?php esc_html_e('Title', 'form-locator-for-gravity-forms'); ?></th>
                            <th style="width: 19%;"><?php esc_html_e('Shortcode Forms', 'form-locator-for-gravity-forms'); ?></th>
                            <th style="width: 19%;"><?php esc_html_e('Block Forms', 'form-locator-for-gravity-forms'); ?></th>
                            <th style="width: 19%;"><?php esc_html_e('Page Builder Forms', 'form-locator-for-gravity-forms'); ?></th>
                            <th style="width: 10%;"><?php esc_html_e('Login Form', 'form-locator-for-gravity-forms'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gf_pages as $data): ?>
                            <tr>
                                <td><?php echo esc_html($data['ID']); ?></td>
                                <td><?php echo esc_html(ucfirst($data['Type'])); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($data['ID'])); ?>" target="_blank" class="row-title">
                                        <?php echo esc_html($data['Title']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if (!empty($data['Form IDs'])): ?>
                                        <?php foreach ($data['Form IDs'] as $form_id): ?>
                                            <div style="margin-bottom: 4px;">
                                                <span class="form-id-badge form-id-shortcode"><?php echo esc_html($form_id); ?></span>
                                                <?php echo $this->display_form_status_message($form_id, $this->check_gravity_form_status($form_id)); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span style="color: #646970;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($data['Block Form IDs'])): ?>
                                        <?php foreach ($data['Block Form IDs'] as $form_id): ?>
                                            <div style="margin-bottom: 4px;">
                                                <span class="form-id-badge form-id-block"><?php echo esc_html($form_id); ?></span>
                                                <?php echo $this->display_form_status_message($form_id, $this->check_gravity_form_status($form_id)); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span style="color: #646970;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($data['Page Builder Form IDs'])): ?>
                                        <?php foreach ($data['Page Builder Form IDs'] as $form_id): ?>
                                            <div style="margin-bottom: 4px;">
                                                <span class="form-id-badge form-id-pagebuilder"><?php echo esc_html($form_id); ?></span>
                                                <?php echo $this->display_form_status_message($form_id, $this->check_gravity_form_status($form_id)); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span style="color: #646970;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($data['Has Login Form']): ?>
                                        <span class="login-form-indicator"><?php esc_html_e('Yes', 'form-locator-for-gravity-forms'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #646970;"><?php esc_html_e('No', 'form-locator-for-gravity-forms'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-forms-message">
                <p><?php esc_html_e('No pages or posts were found that contain Gravity Forms.', 'form-locator-for-gravity-forms'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Register Chart.js plugins
Chart.register(ChartDataLabels);

document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabs = document.querySelectorAll('.nav-tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs and contents
            tabs.forEach(t => t.classList.remove('nav-tab-active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('nav-tab-active');
            
            // Show corresponding content
            const targetId = this.getAttribute('href').substring(1) + '-content';
            const targetContent = document.getElementById(targetId);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });
    
    // Chart data from PHP
    const monthlyData = <?php echo json_encode($monthly_stats ?? ['labels' => [], 'data' => [], 'datasets' => []]); ?>;
    const formData = <?php echo json_encode($form_stats ?? ['labels' => [], 'data' => []]); ?>;
    
    // Line Chart Configuration
    const entriesCtx = document.getElementById('entriesChart');
    if (entriesCtx) {
        if (monthlyData.labels && monthlyData.labels.length > 0 && monthlyData.datasets && monthlyData.datasets.length > 0) {
            new Chart(entriesCtx, {
                type: 'line',
                data: {
                    labels: monthlyData.labels,
                    datasets: monthlyData.datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                color: '#646970',
                                font: {
                                    size: 11
                                },
                                boxWidth: 12,
                                boxHeight: 12
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                color: '#646970',
                                precision: 0
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                color: '#646970'
                            }
                        }
                    },
                    onResize: function(chart, size) {
                        // Prevent infinite resizing
                        if (size.height > 300) {
                            chart.canvas.style.height = '300px';
                        }
                    }
                }
            });
        } else {
            // Show no data message
            const ctx = entriesCtx.getContext('2d');
            ctx.font = '16px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            ctx.fillStyle = '#646970';
            ctx.textAlign = 'center';
            const message = monthlyData.labels && monthlyData.labels.length > 0 ? 
                '<?php esc_html_e('No embedded form entries found', 'form-locator-for-gravity-forms'); ?>' : 
                '<?php esc_html_e('No embedded forms detected', 'form-locator-for-gravity-forms'); ?>';
            ctx.fillText(message, entriesCtx.width/2, entriesCtx.height/2);
        }
    }
    
    // Pie Chart Configuration
    const formsCtx = document.getElementById('formsChart');
    if (formsCtx && formData.labels.length > 0) {
        // Calculate total for percentage calculations
        const total = formData.data.reduce((sum, value) => sum + value, 0);
        
        new Chart(formsCtx, {
            type: 'doughnut',
            data: {
                labels: formData.labels,
                datasets: [{
                    data: formData.data,
                    backgroundColor: [
                        '#3b82f6',
                        '#10b981', 
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6',
                        '#06b6d4',
                        '#84cc16',
                        '#f97316',
                        '#ec4899',
                        '#6366f1'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} entries (${percentage}%)`;
                            }
                        }
                    },
                    datalabels: {
                        color: function(context) {
                            // Use white text for darker backgrounds, dark text for lighter ones
                            const bgColor = context.dataset.backgroundColor[context.dataIndex];
                            return '#fff';
                        },
                        font: {
                            weight: 'bold',
                            size: function(context) {
                                const percentage = ((context.parsed / total) * 100);
                                // Smaller font for smaller segments
                                return percentage > 10 ? 12 : percentage > 5 ? 10 : 9;
                            }
                        },
                        formatter: function(value, context) {
                            const percentage = ((value / total) * 100).toFixed(1);
                            // Show percentage if slice is large enough (>3%)
                            return percentage > 3 ? `${percentage}%` : '';
                        },
                        anchor: function(context) {
                            const percentage = ((context.parsed / total) * 100);
                            // For smaller segments, anchor to end for better visibility
                            return percentage > 8 ? 'center' : 'end';
                        },
                        align: function(context) {
                            const percentage = ((context.parsed / total) * 100);
                            return percentage > 8 ? 'center' : 'end';
                        },
                        offset: function(context) {
                            const percentage = ((context.parsed / total) * 100);
                            // Add offset for smaller segments to move labels outside
                            return percentage > 8 ? 0 : 10;
                        }
                    }
                },
                cutout: '60%',
                onResize: function(chart, size) {
                    // Prevent infinite resizing
                    if (size.height > 300) {
                        chart.canvas.style.height = '300px';
                    }
                }
            }
        });
        
        // Populate custom 3-column legend
        const legendEl = document.getElementById('formsChartLegend');
        if (legendEl && formData.labels.length > 0) {
            const legendTotal = formData.data.reduce((sum, val) => sum + val, 0);
            const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1'];
            const escapeHtml = (str) => {
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            };
            legendEl.innerHTML = formData.labels.map((label, i) => {
                const pct = legendTotal > 0 ? ((formData.data[i] / legendTotal) * 100).toFixed(1) : '0';
                const color = colors[i % colors.length];
                return `<div class="forms-legend-item"><span class="forms-legend-dot" style="background-color:${color}"></span><span class="forms-legend-label">${escapeHtml(label)} (${pct}%)</span></div>`;
            }).join('');
        }
    } else if (formsCtx) {
        // Show message when no data
        const ctx = formsCtx.getContext('2d');
        ctx.font = '16px Arial';
        ctx.fillStyle = '#646970';
        ctx.textAlign = 'center';
        ctx.fillText('<?php esc_html_e('No entry data available', 'form-locator-for-gravity-forms'); ?>', formsCtx.width/2, formsCtx.height/2);
    }
});
</script>
