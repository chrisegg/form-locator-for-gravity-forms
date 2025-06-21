<style>
    .gf-locator-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .gf-locator-header h1 {
        margin: 0 0 15px 0;
        font-size: 24px;
        font-weight: 600;
    }
    
    .gf-locator-stats {
        display: flex;
        gap: 30px;
        flex-wrap: wrap;
    }
    
    .gf-locator-stat {
        background: rgba(255, 255, 255, 0.1);
        padding: 15px 20px;
        border-radius: 6px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .gf-locator-stat strong {
        display: block;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
        opacity: 0.9;
    }
    
    .gf-locator-stat .stat-value {
        font-size: 24px;
        font-weight: 700;
        margin: 0;
    }
    
    .gf-locator-status {
        margin-top: 15px;
        padding: 10px 15px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 4px;
        border-left: 4px solid #4CAF50;
    }
    
    .gf-locator-status strong {
        color: #4CAF50;
    }
</style>

<div class='wrap'>
    <div class="gf-locator-header">
        <h1>Gravity Forms Pages</h1>
        <div class="gf-locator-stats">
            <div class="gf-locator-stat">
                <strong>Total Posts Scanned</strong>
                <div class="stat-value"><?= esc_html($total_posts_scanned); ?></div>
            </div>
            <div class="gf-locator-stat">
                <strong>Pages Using Gravity Forms</strong>
                <div class="stat-value"><?= esc_html(count($gf_pages)); ?></div>
            </div>
        </div>
        
        <?php if ($total_posts_scanned > 0): ?>
            <div class="gf-locator-status">
                <strong>Scan Status:</strong> Scan complete. <?= count($gf_pages) > 0 ? 'Pages with Gravity Forms found.' : 'No pages with Gravity Forms were found.'; ?>
            </div>
        <?php else: ?>
            <div class="gf-locator-status">
                <strong>Scan Status:</strong> No posts were scanned. Please ensure there are published posts to scan.
            </div>
        <?php endif; ?>
    </div>

    <table class='widefat fixed' style='margin-top: 20px; border-collapse: collapse; width: 100%;'>
        <thead>
            <tr style='border-bottom: 2px solid #ddd;'>
                <th width='8%'>Post ID</th>
                <th width='8%'>Type</th>
                <th width='35%'>Title</th>
                <th width='15%'>Form Shortcodes</th>
                <th width='15%'>Form Blocks</th>
                <th width='10%'>Login Form</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($gf_pages as $data): ?>
                <tr style='border-bottom: 1px solid #ddd;'>
                    <td><?= esc_html($data['ID']); ?></td>
                    <td><?= esc_html($data['Type']); ?></td>
                    <td><a href='<?= esc_url(get_edit_post_link($data['ID'])); ?>' target='_blank'><?= esc_html($data['Title']); ?></a></td>
                    <td>
                        <?php foreach ($data['Form IDs'] as $form_id): ?>
                            <span style='color: purple;'>Form ID:<?= esc_html($form_id); ?></span>
                            <?= $this->display_form_status_message($form_id, $this->check_gravity_form_status($form_id)); ?>
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <?php foreach ($data['Block Form IDs'] as $form_id): ?>
                            <span style='color: green;'>Form ID:<?= esc_html($form_id); ?></span>
                            <?= $this->display_form_status_message($form_id, $this->check_gravity_form_status($form_id)); ?>
                        <?php endforeach; ?>
                    </td>
                    <td><?= $data['Has Login Form'] ? '<span style="color: blue; font-weight: 600;">Yes</span>' : 'No'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
