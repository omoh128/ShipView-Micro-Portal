<?php
/**
 * templates/tracking-page.php
 * Standalone full-page template – bypasses the theme entirely.
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php bloginfo( 'name' ); ?> — Shipment Tracker</title>
<?php wp_head(); ?>
</head>
<body class="shipview-body">

<div id="shipview-app">

    <!-- ═══════════════════  HEADER  ═══════════════════ -->
    <header class="sv-header">
        <div class="sv-header__brand">
            <svg class="sv-logo" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 22 L16 6 L30 22" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <rect x="8" y="22" width="16" height="7" rx="1.5" stroke="currentColor" stroke-width="2.5"/>
                <line x1="16" y1="22" x2="16" y2="29" stroke="currentColor" stroke-width="2.5"/>
                <line x1="8" y1="25.5" x2="24" y2="25.5" stroke="currentColor" stroke-width="2"/>
            </svg>
            <div>
                <span class="sv-header__title">SHIPVIEW</span>
                <span class="sv-header__sub">MICRO-PORTAL</span>
            </div>
        </div>

        <div class="sv-header__controls">
            <div class="sv-search-wrap">
                <svg class="sv-search-icon" viewBox="0 0 20 20" fill="none"><circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.8"/><path d="M13.5 13.5L17 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <input id="sv-search" class="sv-search" type="text" placeholder="Search AWB, client…" autocomplete="off">
            </div>
            <div class="sv-filter-wrap">
                <select id="sv-filter-status" class="sv-select">
                    <option value="all">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="in_transit">In Transit</option>
                    <option value="customs">Customs</option>
                    <option value="out_for_del">Out for Delivery</option>
                    <option value="delivered">Delivered</option>
                    <option value="exception">Exception</option>
                    <option value="returned">Returned</option>
                </select>
            </div>
            <button id="sv-refresh-btn" class="sv-btn sv-btn--ghost" title="Refresh now">
                <svg viewBox="0 0 20 20" fill="none"><path d="M17 10A7 7 0 1 1 13.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><polyline points="14,2 14,6 18,6" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            </button>
        </div>
    </header>

    <!-- ═══════════════════  STAT STRIP  ═══════════════════ -->
    <div class="sv-stats" id="sv-stats">
        <div class="sv-stat">
            <span class="sv-stat__val" id="stat-total">—</span>
            <span class="sv-stat__lbl">TOTAL</span>
        </div>
        <div class="sv-stat sv-stat--blue">
            <span class="sv-stat__val" id="stat-transit">—</span>
            <span class="sv-stat__lbl">IN TRANSIT</span>
        </div>
        <div class="sv-stat sv-stat--green">
            <span class="sv-stat__val" id="stat-delivered">—</span>
            <span class="sv-stat__lbl">DELIVERED</span>
        </div>
        <div class="sv-stat sv-stat--red">
            <span class="sv-stat__val" id="stat-exception">—</span>
            <span class="sv-stat__lbl">EXCEPTION</span>
        </div>
        <div class="sv-stat sv-stat--amber">
            <span class="sv-stat__val" id="stat-overdue">—</span>
            <span class="sv-stat__lbl">OVERDUE</span>
        </div>
        <div class="sv-stat sv-stat--dim">
            <span class="sv-stat__val" id="stat-updated">—</span>
            <span class="sv-stat__lbl">LAST SYNC</span>
        </div>
    </div>

    <!-- ═══════════════════  GRID  ═══════════════════ -->
    <main class="sv-main">
        <div class="sv-table-wrap">
            <table class="sv-table" id="sv-table">
                <thead>
                    <tr>
                        <th class="col-awb">AWB / REF</th>
                        <th class="col-carrier">CARRIER</th>
                        <th class="col-route">ROUTE</th>
                        <th class="col-client">CLIENT</th>
                        <th class="col-status">STATUS</th>
                        <th class="col-eta">ETA</th>
                        <th class="col-weight">KG</th>
                        <th class="col-notes">NOTES</th>
                    </tr>
                </thead>
                <tbody id="sv-tbody">
                    <tr class="sv-loading-row">
                        <td colspan="8">
                            <div class="sv-loader">
                                <span></span><span></span><span></span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="sv-empty" class="sv-empty" style="display:none">
            <svg viewBox="0 0 64 64" fill="none"><rect x="12" y="20" width="40" height="32" rx="3" stroke="currentColor" stroke-width="2"/><path d="M22 20V16a10 10 0 0 1 20 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="32" cy="36" r="4" stroke="currentColor" stroke-width="2"/></svg>
            <p>No shipments match your filter.</p>
        </div>
    </main>

    <!-- ═══════════════════  FOOTER  ═══════════════════ -->
    <footer class="sv-footer">
        <span>SHIPVIEW v<?php echo esc_html( SHIPVIEW_VERSION ); ?></span>
        <span id="sv-countdown">Next refresh in <strong id="sv-timer">30:00</strong></span>
        <span><?php bloginfo( 'name' ); ?></span>
    </footer>

    <!-- ═══════════════════  DETAIL DRAWER  ═══════════════════ -->
    <div id="sv-drawer" class="sv-drawer" aria-hidden="true">
        <div class="sv-drawer__inner">
            <button id="sv-drawer-close" class="sv-drawer__close" aria-label="Close">✕</button>
            <div id="sv-drawer-content"></div>
        </div>
    </div>
    <div id="sv-overlay" class="sv-overlay"></div>

</div><!-- #shipview-app -->

<?php wp_footer(); ?>
</body>
</html>
