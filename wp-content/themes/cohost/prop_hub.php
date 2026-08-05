<?php
// ============================================================
// Property Hub — WordPress admin page
// LBS
// ============================================================

// --- DB credentials for the pm_* tables ---
// NOTE: the SQL dumps for pm_prop_hub / pm_cleaners / pm_contractors are
// all under database `rentals`, not `props` (where onboarding_tasks
// lives). Adjust DB_NAME below if your local WAMP setup differs.
function lbs_prop_hub_db() {
    static $pdo = null;
    if ($pdo === null) {
        $DB_HOST = 'localhost';
        $DB_NAME = 'rentals';
        $DB_USER = 'root';
        $DB_PASS = 'password';

        $pdo = new PDO(
            "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
            $DB_USER,
            $DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}

// --- Register the admin menu page ---
add_action('admin_menu', 'lbs_add_prop_hub_page');

function lbs_add_prop_hub_page() {
    add_menu_page(
        'Property Hub',
        'Property Hub',
        'manage_options',
        'lbs-prop-hub',
        'lbs_render_prop_hub_page',
        'dashicons-admin-multisite',
        4
    );
}

// --- Status option definitions (value => [label, colors]) ---
function lbs_prop_status_options() {
    return [
        0 => ['label' => 'Cancelled',  'bg' => '#FBE4E4', 'fg' => '#C4554D', 'dot' => '#E03E3E'],
        1 => ['label' => 'Live',       'bg' => '#DDF3E4', 'fg' => '#0F7B6C', 'dot' => '#2EAE4E'],
        2 => ['label' => 'Onboarding', 'bg' => '#FDECC8', 'fg' => '#97701D', 'dot' => '#F5A623'],
    ];
}

// --- Column definitions: pm_prop_hub, in display order ---
// type: 'num' | 'status' | 'title' | 'text' | 'link'
// 'key' is the db field this column reads from. The same key can appear
// more than once (e.g. Property is repeated at the end).
function lbs_prop_hub_columns() {
    return [
        ['key' => 'num',       'label' => '#',                'type' => 'num'],
        ['key' => 'status',    'label' => 'Status',           'type' => 'status'],
        ['key' => 'client',    'label' => 'Client',           'type' => 'text'],
        ['key' => 'name',      'label' => 'Property',         'type' => 'title'],
        ['key' => 'close',     'label' => 'Close CRM',        'type' => 'link', 'chip' => 'CRM'],
        ['key' => 'zip',       'label' => 'ZIP',               'type' => 'text'],
        ['key' => 'shorturl',  'label' => 'Airbnb',           'type' => 'link', 'chip' => 'Listing'],
        ['key' => 'turno',     'label' => 'Turno',            'type' => 'link', 'chip' => 'Turno'],
        ['key' => 'gdrive',    'label' => 'Google Drive',     'type' => 'link', 'chip' => 'Folder'],
        ['key' => 'hub',       'label' => 'Hub #',            'type' => 'text'],
        ['key' => 'hosp',      'label' => 'Hospitable ID',    'type' => 'text'],
        ['key' => 'hosp_msg',  'label' => 'Messaging Rules',  'type' => 'link', 'chip' => 'Rules'],
        ['key' => 'a_listing', 'label' => 'Airbnb Listing ID','type' => 'text'],
        ['key' => 'v_list',    'label' => 'VRBO Listing',     'type' => 'link', 'chip' => 'Listing'],
        ['key' => 'v_ins',     'label' => 'VRBO Insurance',   'type' => 'link', 'chip' => 'Insurance'],
        ['key' => 'v_fees',    'label' => 'VRBO Fees',        'type' => 'link', 'chip' => 'Fees'],
        ['key' => 'v_live',    'label' => 'VRBO Live',        'type' => 'link', 'chip' => 'Live'],
        ['key' => 'pricelabs', 'label' => 'PriceLabs',        'type' => 'text'],
        ['key' => 'compset',   'label' => 'CompSet',          'type' => 'link', 'chip' => 'CompSet'],
        ['key' => 'hostco',    'label' => 'Host.co',          'type' => 'link', 'chip' => 'Store'],
        ['key' => 'name',      'label' => 'Property',         'type' => 'title'],
    ];
}

// --- Column definitions: pm_cleaners, in display order ---
function lbs_cleaners_columns() {
    return [
        ['key' => 'id',        'label' => 'ID',          'type' => 'num'],
        ['key' => 'name',      'label' => 'Cleaner',     'type' => 'title'],
        ['key' => 'prop_id',   'label' => 'Property #',  'type' => 'num'],
        ['key' => 'role',      'label' => 'Role',        'type' => 'text'],
        ['key' => 'manager',   'label' => 'Manager',     'type' => 'text'],
        ['key' => 'staff',     'label' => 'Staff',       'type' => 'text'],
        ['key' => 'scheduler', 'label' => 'Scheduler',   'type' => 'text'],
        ['key' => 'chat',      'label' => 'Chat',        'type' => 'text'],
        ['key' => 'turnover',  'label' => 'Turnover',    'type' => 'text'],
        ['key' => 'close',     'label' => 'Close CRM',   'type' => 'link', 'chip' => 'CRM'],
        ['key' => 'photos',    'label' => 'Photos',      'type' => 'link', 'chip' => 'Folder'],
    ];
}

// --- Column definitions: pm_contractors, in display order ---
function lbs_contractors_columns() {
    return [
        ['key' => 'id',      'label' => 'ID',        'type' => 'num'],
        ['key' => 'name',    'label' => 'Contractor','type' => 'title'],
        ['key' => 'title',   'label' => 'Trade',     'type' => 'text'],
        ['key' => 'prop_id', 'label' => 'Property #','type' => 'num'],
        ['key' => 'phone',   'label' => 'Phone',     'type' => 'text'],
        ['key' => 'address', 'label' => 'Address',   'type' => 'text'],
        ['key' => 'payment', 'label' => 'Payment',   'type' => 'text'],
        ['key' => 'note',    'label' => 'Note',      'type' => 'text'],
        ['key' => 'close',   'label' => 'Close CRM', 'type' => 'link', 'chip' => 'CRM'],
    ];
}

// --- Render a cell for num / title / text / link types (shared by all tables) ---
function lbs_render_generic_cell($row, $col) {
    $key = $col['key'];
    $value = isset($row[$key]) ? $row[$key] : null;
    $value = is_string($value) ? trim($value) : $value;

    if ($col['type'] === 'num') {
        echo '<td class="ph-cell ph-num">' . esc_html($value) . '</td>';
        return;
    }

    if ($value === '' || $value === null) {
        echo '<td class="ph-cell ph-empty"><span class="ph-dash">—</span></td>';
        return;
    }

    switch ($col['type']) {
        case 'title':
            echo '<td class="ph-cell ph-title"><span class="ph-title-icon">▤</span>' . esc_html($value) . '</td>';
            break;

        case 'link':
            if (preg_match('#^https?://#i', $value)) {
                $chip = isset($col['chip']) ? $col['chip'] : 'Open';
                echo '<td class="ph-cell"><a class="ph-chip" href="' . esc_url($value) . '" target="_blank" rel="noopener noreferrer">'
                    . esc_html($chip) . ' <span class="ph-chip-arrow">&#8599;</span></a></td>';
            } else {
                // Not a full URL (e.g. a bare folder ID) — show as plain text.
                echo '<td class="ph-cell ph-text" title="' . esc_attr($value) . '">' . esc_html($value) . '</td>';
            }
            break;

        case 'text':
        default:
            echo '<td class="ph-cell ph-text" title="' . esc_attr($value) . '">' . esc_html($value) . '</td>';
            break;
    }
}

// --- Render a cell for the pm_prop_hub table (adds the status dropdown) ---
function lbs_render_prop_cell($row, $col) {
    if ($col['type'] !== 'status') {
        lbs_render_generic_cell($row, $col);
        return;
    }

    $value = isset($row['status']) ? $row['status'] : null;
    $options = lbs_prop_status_options();
    $current = isset($options[(int) $value]) ? (int) $value : 0;
    $opt = $options[$current];

    echo '<td class="ph-cell ph-status-cell">';
    echo '<div class="ph-status-wrap" data-num="' . esc_attr($row['num']) . '">';
    echo '<button type="button" class="ph-pill ph-status-trigger" data-status="' . $current . '" '
        . 'style="background:' . esc_attr($opt['bg']) . ';color:' . esc_attr($opt['fg']) . ';">'
        . '<span class="ph-dot" style="background:' . esc_attr($opt['dot']) . ';"></span>'
        . esc_html($opt['label']) . '</button>';
    echo '<div class="ph-status-menu">';
    foreach ($options as $val => $o) {
        $selected = $val === $current ? ' ph-status-selected' : '';
        echo '<div class="ph-status-option' . $selected . '" data-status="' . $val . '" '
            . 'data-bg="' . esc_attr($o['bg']) . '" data-fg="' . esc_attr($o['fg']) . '" data-dot="' . esc_attr($o['dot']) . '">'
            . '<span class="ph-status-option-pill" style="background:' . esc_attr($o['bg']) . ';color:' . esc_attr($o['fg']) . ';">'
            . '<span class="ph-dot" style="background:' . esc_attr($o['dot']) . ';"></span>' . esc_html($o['label']) . '</span>'
            . '<span class="ph-status-check">&#10003;</span>'
            . '</div>';
    }
    echo '</div>'; // .ph-status-menu
    echo '</div>'; // .ph-status-wrap
    echo '</td>';
}

// --- Fetch all rows + columns for a pm_* table ---
function lbs_fetch_table_rows($sqlTable, $columns, $orderBy) {
    $selectFields = array_unique(array_column($columns, 'key'));
    if (!in_array($orderBy, $selectFields, true)) {
        $selectFields[] = $orderBy;
    }

    try {
        $pdo = lbs_prop_hub_db();
        $stmt = $pdo->query(
            'SELECT ' . implode(', ', $selectFields) . '
             FROM ' . $sqlTable . '
             ORDER BY ' . $orderBy
        );
        return ['rows' => $stmt->fetchAll(), 'error' => null];
    } catch (PDOException $e) {
        return ['rows' => [], 'error' => $e->getMessage()];
    }
}

// --- Render one Notion-style table section (heading + table + count) ---
function lbs_render_table_section($heading, $sourceTable, $rows, $columns, $dbError, $cellCallback) {
    ?>
    <h2 class="ph-section-title"><?= esc_html($heading) ?></h2>
    <p class="ph-subtitle">Pulled live from <code><?= esc_html($sourceTable) ?></code>.</p>

    <?php if ($dbError): ?>
        <div class="notice notice-error"><p>Could not load <?= esc_html($heading) ?>: <?= esc_html($dbError) ?></p></div>
    <?php elseif (empty($rows)): ?>
        <div class="ph-empty-state">No <?= esc_html(strtolower($heading)) ?> yet.</div>
    <?php else: ?>
        <div class="ph-table-shell">
            <div class="ph-table-scroll">
                <table class="ph-table">
                    <thead>
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <th class="ph-th"><?= esc_html($col['label']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr class="ph-row">
                                <?php foreach ($columns as $col): ?>
                                    <?php call_user_func($cellCallback, $row, $col); ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="ph-count"><?= count($rows) ?> <?= count($rows) === 1 ? 'row' : 'rows' ?></div>
    <?php endif;
}

// --- Main page render callback ---
function lbs_render_prop_hub_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $propColumns = lbs_prop_hub_columns();
    $propData = lbs_fetch_table_rows('pm_prop_hub', $propColumns, 'num');

    $cleanerColumns = lbs_cleaners_columns();
    $cleanerData = lbs_fetch_table_rows('pm_cleaners', $cleanerColumns, 'id');

    $contractorColumns = lbs_contractors_columns();
    $contractorData = lbs_fetch_table_rows('pm_contractors', $contractorColumns, 'id');

    $nonce = wp_create_nonce('lbs_prop_hub_status');
    $ajaxUrl = admin_url('admin-ajax.php');
    ?>
    <div class="wrap ph-wrap">
        <h1>Property Hub</h1>

        <div class="ph-quicklinks">
            <a class="button" href="<?= esc_url(admin_url('admin.php?page=lbs-onboarding')) ?>">Onboarding checklist</a>
        </div>

        <?php
        lbs_render_table_section('Properties', 'pm_prop_hub', $propData['rows'], $propColumns, $propData['error'], 'lbs_render_prop_cell');
        lbs_render_table_section('Cleaners', 'pm_cleaners', $cleanerData['rows'], $cleanerColumns, $cleanerData['error'], 'lbs_render_generic_cell');
        lbs_render_table_section('Contractors', 'pm_contractors', $contractorData['rows'], $contractorColumns, $contractorData['error'], 'lbs_render_generic_cell');
        ?>
    </div>

    <style>
        .ph-wrap {
            font-family: ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
            color: #37352F;
        }
        .ph-section-title {
            margin-top: 44px;
            margin-bottom: 2px;
            font-size: 19px;
        }
        .ph-wrap > .ph-section-title:first-of-type {
            margin-top: 30px;
        }
        .ph-subtitle {
            color: #9B9A97;
            font-size: 13px;
            margin-top: 0;
            margin-bottom: 18px;
        }
        .ph-subtitle code {
            background: #F1F1EF;
            color: #37352F;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 12px;
        }
        .ph-quicklinks {
            margin-bottom: 10px;
        }
        .ph-empty-state {
            color: #9B9A97;
            font-size: 14px;
            padding: 40px 0;
            text-align: center;
            border: 1px dashed #E9E9E7;
            border-radius: 8px;
            max-width: 900px;
        }

        /* Centered Notion-style database table */
        .ph-table-shell {
            display: flex;
            justify-content: center;
            width: 100%;
        }
        .ph-table-scroll {
            max-width: 100%;
            overflow-x: auto;
            overflow-y: visible;
            border: 1px solid #E9E9E7;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(15, 15, 15, 0.04);
        }
        .ph-table {
            border-collapse: separate;
            border-spacing: 0;
            background: #FFFFFF;
            font-size: 13px;
        }
        .ph-th {
            position: sticky;
            top: 0;
            background: #FBFBFA;
            color: #9B9A97;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            text-align: left;
            padding: 9px 14px;
            border-bottom: 1px solid #E9E9E7;
            border-right: 1px solid #F1F1EF;
            white-space: nowrap;
        }
        .ph-th:last-child {
            border-right: none;
        }
        .ph-row:hover .ph-cell {
            background: #F7F6F5;
        }
        .ph-cell {
            padding: 8px 14px;
            border-bottom: 1px solid #F1F1EF;
            border-right: 1px solid #F1F1EF;
            white-space: nowrap;
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
        }
        .ph-status-cell {
            overflow: visible; /* let the dropdown escape the cell */
        }
        .ph-row:last-child .ph-cell {
            border-bottom: none;
        }
        .ph-cell:last-child {
            border-right: none;
        }
        .ph-num {
            color: #9B9A97;
            font-variant-numeric: tabular-nums;
            text-align: right;
            width: 36px;
        }
        .ph-title {
            font-weight: 500;
            color: #37352F;
        }
        .ph-title-icon {
            color: #A9A9A6;
            margin-right: 6px;
        }
        .ph-text {
            color: #37352F;
        }
        .ph-empty .ph-dash {
            color: #D8D8D5;
        }

        .ph-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 2px 9px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .ph-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .ph-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 9px;
            border-radius: 4px;
            background: #DDEBF1;
            color: #337EA9;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
        }
        .ph-chip:hover {
            background: #C7E0EA;
            color: #276583;
        }
        .ph-chip-arrow {
            font-size: 11px;
        }

        .ph-count {
            text-align: center;
            color: #9B9A97;
            font-size: 12px;
            margin-top: 10px;
        }

        /* Status dropdown, Notion-style */
        .ph-status-wrap {
            position: relative;
            display: inline-block;
        }
        .ph-status-trigger {
            border: 1px solid transparent;
            cursor: pointer;
        }
        .ph-status-trigger:hover {
            border-color: rgba(0, 0, 0, 0.08);
        }
        .ph-status-menu {
            display: none;
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            z-index: 50;
            min-width: 160px;
            background: #FFFFFF;
            border: 1px solid #E9E9E7;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(15, 15, 15, 0.12), 0 0 0 1px rgba(15, 15, 15, 0.02);
            padding: 4px;
        }
        .ph-status-menu.open {
            display: block;
        }
        .ph-status-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 6px 8px;
            border-radius: 5px;
            cursor: pointer;
        }
        .ph-status-option:hover {
            background: #F1F1EF;
        }
        .ph-status-option-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 2px 9px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .ph-status-check {
            font-size: 11px;
            color: #37352F;
            visibility: hidden;
        }
        .ph-status-option.ph-status-selected .ph-status-check {
            visibility: visible;
        }
    </style>

    <script>
    (function () {
        const ajaxUrl = <?= json_encode($ajaxUrl) ?>;
        const nonce = <?= json_encode($nonce) ?>;

        function closeAllMenus() {
            document.querySelectorAll('.ph-status-menu.open').forEach(function (m) {
                m.classList.remove('open');
            });
        }

        document.querySelectorAll('.ph-status-wrap').forEach(function (wrap) {
            const btn = wrap.querySelector('.ph-status-trigger');
            const menu = wrap.querySelector('.ph-status-menu');
            const num = wrap.dataset.num;

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const isOpen = menu.classList.contains('open');
                closeAllMenus();
                if (!isOpen) {
                    menu.classList.add('open');
                }
            });

            menu.querySelectorAll('.ph-status-option').forEach(function (opt) {
                opt.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const newStatus = opt.dataset.status;
                    const prevStatus = btn.dataset.status;

                    if (newStatus === prevStatus) {
                        closeAllMenus();
                        return;
                    }

                    const prevHtml = btn.innerHTML;
                    const prevStyle = btn.getAttribute('style');

                    // Optimistic UI update
                    btn.dataset.status = newStatus;
                    btn.setAttribute('style', 'background:' + opt.dataset.bg + ';color:' + opt.dataset.fg + ';');
                    btn.innerHTML = '<span class="ph-dot" style="background:' + opt.dataset.dot + ';"></span>'
                        + opt.querySelector('.ph-status-option-pill').textContent.trim();

                    menu.querySelectorAll('.ph-status-option').forEach(function (o) {
                        o.classList.toggle('ph-status-selected', o === opt);
                    });
                    closeAllMenus();

                    const body = new URLSearchParams();
                    body.append('action', 'lbs_update_prop_status');
                    body.append('nonce', nonce);
                    body.append('num', num);
                    body.append('status', newStatus);

                    fetch(ajaxUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: body.toString()
                    })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (!data.success) {
                            btn.dataset.status = prevStatus;
                            btn.setAttribute('style', prevStyle);
                            btn.innerHTML = prevHtml;
                            alert('Could not update status: ' + (data.data && data.data.error ? data.data.error : 'unknown error'));
                        }
                    })
                    .catch(function () {
                        btn.dataset.status = prevStatus;
                        btn.setAttribute('style', prevStyle);
                        btn.innerHTML = prevHtml;
                        alert('Network error - could not save status.');
                    });
                });
            });
        });

        document.addEventListener('click', closeAllMenus);
    })();
    </script>
    <?php
}

// --- AJAX handler for updating a property's status ---
add_action('wp_ajax_lbs_update_prop_status', 'lbs_update_prop_status');

function lbs_update_prop_status() {
    check_ajax_referer('lbs_prop_hub_status', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['error' => 'Not allowed'], 403);
    }

    $num = isset($_POST['num']) ? (int) $_POST['num'] : 0;
    $status = isset($_POST['status']) ? (int) $_POST['status'] : null;
    $validStatuses = array_keys(lbs_prop_status_options());

    if ($num <= 0 || !in_array($status, $validStatuses, true)) {
        wp_send_json_error(['error' => 'Invalid property or status value'], 400);
    }

    $pdo = lbs_prop_hub_db();
    $stmt = $pdo->prepare('UPDATE pm_prop_hub SET status = :status WHERE num = :num');
    $stmt->execute([':status' => $status, ':num' => $num]);

    wp_send_json_success(['num' => $num, 'status' => $status]);
}