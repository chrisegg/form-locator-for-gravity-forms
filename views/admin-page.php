<div class='wrap'>
    <h1>Gravity Forms Pages</h1>
    <p><strong>Total Posts Scanned:</strong> <?= $total_posts_scanned; ?></p>
    <p><strong>Pages Using Gravity Forms:</strong> <?= count($gf_pages); ?></p>
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
                    <td><?= $data['ID']; ?></td>
                    <td><?= $data['Type']; ?></td>
                    <td><a href='<?= get_edit_post_link($data['ID']); ?>' target='_blank'><?= $data['Title']; ?></a></td>
                    <td>
                        <?php foreach ($data['Form IDs'] as $form_id): ?>
                            <span style='color: purple;'>Form ID:<?= $form_id; ?></span>
                            <?= $this->display_form_status_message($form_id, $this->check_gravity_form_status($form_id)); ?>
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <?php foreach ($data['Block Form IDs'] as $form_id): ?>
                            <span style='color: green;'>Form ID:<?= $form_id; ?></span>
                            <?= $this->display_form_status_message($form_id, $this->check_gravity_form_status($form_id)); ?>
                        <?php endforeach; ?>
                    </td>
                    <td><?= $data['Has Login Form'] ? '<span style="color: blue; font-weight: 600;">Yes</span>' : 'No'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
