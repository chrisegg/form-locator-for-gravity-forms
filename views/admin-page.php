<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
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
        grid-template-columns: 1fr 1fr;
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
    
    .stat-block:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }
    
    .stat-block:nth-child(2):hover {
        box-shadow: 0 8px 25px rgba(240, 147, 251, 0.3);
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
        height: 350px;
        position: relative;
        display: flex;
        flex-direction: column;
        overflow: hidden;
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
    
    /* Responsive Design */
    @media (max-width: 1200px) {
        .dashboard-container {
            grid-template-columns: 1fr;
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
                        <div class="stat-change">+100%</div>
                    </div>
                    <div class="stat-block">
                        <div class="stat-number"><?php echo esc_html(count($gf_pages)); ?></div>
                        <div class="stat-label"><?php esc_html_e('Embedded Forms', 'form-locator-for-gravity-forms'); ?></div>
                        <div class="stat-change">This Month</div>
                    </div>
                </div>
                
                <!-- Line Chart -->
                <div class="chart-container">
                    <div class="chart-title"><?php esc_html_e('Form Entries Over Time', 'form-locator-for-gravity-forms'); ?></div>
                    <div style="position: relative; height: 280px; width: 100%;">
                        <canvas id="entriesChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Pie Chart -->
            <div class="chart-container">
                <div class="chart-title"><?php esc_html_e('Entries by Form', 'form-locator-for-gravity-forms'); ?></div>
                <div style="position: relative; height: 280px; width: 100%;">
                    <canvas id="formsChart"></canvas>
                </div>
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
                    <?php esc_html_e('No published posts were found to scan.', 'form-locator-for-gravity-forms'); ?>
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
    const monthlyData = <?php echo json_encode($monthly_stats ?? ['labels' => [], 'data' => []]); ?>;
    const formData = <?php echo json_encode($form_stats ?? ['labels' => [], 'data' => []]); ?>;
    
    // Line Chart Configuration
    const entriesCtx = document.getElementById('entriesChart');
    if (entriesCtx) {
        if (monthlyData.labels && monthlyData.labels.length > 0) {
            new Chart(entriesCtx, {
                type: 'line',
                data: {
                    labels: monthlyData.labels,
                    datasets: [{
                        label: '<?php esc_html_e('Form Entries', 'form-locator-for-gravity-forms'); ?>',
                        data: monthlyData.data,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                color: '#646970'
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
                    elements: {
                        point: {
                            hoverBackgroundColor: '#10b981'
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
            ctx.fillText('<?php esc_html_e('No entry data available', 'form-locator-for-gravity-forms'); ?>', entriesCtx.width/2, entriesCtx.height/2);
        }
    }
    
    // Pie Chart Configuration
    const formsCtx = document.getElementById('formsChart');
    if (formsCtx && formData.labels.length > 0) {
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
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            color: '#646970',
                            font: {
                                size: 12
                            }
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
