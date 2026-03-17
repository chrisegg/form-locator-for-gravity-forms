<style>
    .form-locator-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .form-locator-stat-card {
        background: #fff;
        border: 1px solid #c3c4c7;
        border-radius: 4px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    
    .form-locator-stat-number {
        font-size: 32px;
        font-weight: 600;
        color: #1d2327;
        line-height: 1;
        margin-bottom: 8px;
    }
    
    .form-locator-stat-label {
        font-size: 14px;
        color: #646970;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .form-locator-table-wrapper {
        background: #fff;
        border: 1px solid #c3c4c7;
        border-radius: 4px;
        overflow: hidden;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    
    .form-locator-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }
    
    .form-locator-table th {
        background: #f6f7f7;
        border-bottom: 1px solid #c3c4c7;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #1d2327;
    }
    
    .form-locator-table td {
        padding: 12px;
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
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 500;
        margin: 2px 4px 2px 0;
        color: #fff;
    }
    
    .form-id-shortcode {
        background: #7c3aed;
    }
    
    .form-id-block {
        background: #059669;
    }
    
    .form-id-pagebuilder {
        background: #ea580c;
    }
    
    .form-status {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: 500;
        margin-left: 4px;
        text-transform: uppercase;
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
        padding: 40px 20px;
        color: #646970;
        font-style: italic;
    }
</style>

<div class="wrap gform_page">
    <h1 class="wp-heading-inline">
        <i class="gform-icon gform-icon--search"></i>
        <?php esc_html_e('Form Locator', 'form-locator-for-gravity-forms'); ?>
    </h1>
    
    <hr class="wp-header-end">
    
    <div class="gform-settings-panel">
        <header class="gform-settings-panel__header">
            <h4 class="gform-settings-panel__title"><?php esc_html_e('Scan Results', 'form-locator-for-gravity-forms'); ?></h4>
        </header>
        
        <div class="gform-settings-panel__content">
            <div class="form-locator-stats">
                <div class="form-locator-stat-card">
                    <div class="form-locator-stat-number"><?php echo esc_html($total_posts_scanned); ?></div>
                    <div class="form-locator-stat-label"><?php esc_html_e('Posts Scanned', 'form-locator-for-gravity-forms'); ?></div>
                </div>
                <div class="form-locator-stat-card">
                    <div class="form-locator-stat-number"><?php echo esc_html(count($gf_pages)); ?></div>
                    <div class="form-locator-stat-label"><?php esc_html_e('Pages with Forms', 'form-locator-for-gravity-forms'); ?></div>
                </div>
            </div>
            
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
        </div>
    </div>
    
    <?php if (!empty($gf_pages)): ?>
        <div class="gform-settings-panel">
            <header class="gform-settings-panel__header">
                <h4 class="gform-settings-panel__title"><?php esc_html_e('Pages Using Gravity Forms', 'form-locator-for-gravity-forms'); ?></h4>
            </header>
            
            <div class="gform-settings-panel__content">
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
            </div>
        </div>
    <?php else: ?>
        <div class="gform-settings-panel">
            <div class="gform-settings-panel__content">
                <div class="no-forms-message">
                    <p><?php esc_html_e('No pages or posts were found that contain Gravity Forms.', 'form-locator-for-gravity-forms'); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
