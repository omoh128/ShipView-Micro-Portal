/**
 * ShipView Micro-Portal — shipview.js
 * Fetches shipment data from the WP REST API and renders the control-room grid.
 * Auto-refreshes every 30 minutes (configurable via ShipViewConfig.refreshMs).
 */

/* global ShipViewConfig */
( function () {
    'use strict';

    /* ── Config ────────────────────────────────────────────────── */
    const cfg        = window.ShipViewConfig || {};
    const REST_URL   = cfg.restUrl  || '/wp-json/shipview/v1/shipments';
    const NONCE      = cfg.nonce    || '';
    const REFRESH_MS = cfg.refreshMs || 30 * 60 * 1000;

    /* ── State ─────────────────────────────────────────────────── */
    let allShipments  = [];
    let countdownSecs = REFRESH_MS / 1000;
    let timerInterval = null;
    let refreshTimer  = null;

    /* ── DOM refs ──────────────────────────────────────────────── */
    const tbody      = document.getElementById( 'sv-tbody' );
    const emptyEl    = document.getElementById( 'sv-empty' );
    const searchEl   = document.getElementById( 'sv-search' );
    const filterEl   = document.getElementById( 'sv-filter-status' );
    const refreshBtn = document.getElementById( 'sv-refresh-btn' );
    const timerEl    = document.getElementById( 'sv-timer' );
    const drawer     = document.getElementById( 'sv-drawer' );
    const drawerBody = document.getElementById( 'sv-drawer-content' );
    const overlay    = document.getElementById( 'sv-overlay' );
    const closeBtn   = document.getElementById( 'sv-drawer-close' );

    /* ── Status label map ──────────────────────────────────────── */
    const STATUS = {
        pending:     { icon: '⏳', label: 'Pending',          cls: 'sv-badge--pending'     },
        in_transit:  { icon: '✈', label: 'In Transit',       cls: 'sv-badge--in_transit'  },
        customs:     { icon: '🛃', label: 'Customs',          cls: 'sv-badge--customs'     },
        out_for_del: { icon: '🚚', label: 'Out for Delivery', cls: 'sv-badge--out_for_del' },
        delivered:   { icon: '✔', label: 'Delivered',        cls: 'sv-badge--delivered'   },
        exception:   { icon: '⚠', label: 'Exception',        cls: 'sv-badge--exception'   },
        returned:    { icon: '↩', label: 'Returned',         cls: 'sv-badge--returned'    },
    };

    /* ═══════════════════════════════════════════════════════════
       FETCH
    ═══════════════════════════════════════════════════════════ */
    async function fetchShipments( extraParams = {} ) {
        const url = new URL( REST_URL );
        Object.entries( extraParams ).forEach( ( [k, v] ) => url.searchParams.set( k, v ) );

        const res = await fetch( url.toString(), {
            headers: {
                'X-WP-Nonce': NONCE,
                'Accept':     'application/json',
            },
        } );

        if ( ! res.ok ) throw new Error( `HTTP ${ res.status }` );
        return res.json();
    }

    /* ═══════════════════════════════════════════════════════════
       RENDER — stats
    ═══════════════════════════════════════════════════════════ */
    function renderStats( stats, generated ) {
        document.getElementById( 'stat-total'     ).textContent = stats.total      ?? '—';
        document.getElementById( 'stat-transit'   ).textContent = stats.in_transit ?? '—';
        document.getElementById( 'stat-delivered' ).textContent = stats.delivered  ?? '—';
        document.getElementById( 'stat-exception' ).textContent = stats.exception  ?? '—';
        document.getElementById( 'stat-overdue'   ).textContent = stats.overdue    ?? '—';

        if ( generated ) {
            const d = new Date( generated );
            document.getElementById( 'stat-updated' ).textContent =
                d.toLocaleTimeString( [], { hour: '2-digit', minute: '2-digit' } );
        }
    }

    /* ── ETA class helper ───────────────────────────────────── */
    function etaClass( ship ) {
        if ( ship.overdue )                   return 'sv-eta--overdue';
        if ( ! ship.eta )                     return '';
        const diff = ( new Date( ship.eta ) - Date.now() ) / 86400000;
        return diff <= 2 ? 'sv-eta--soon' : 'sv-eta--ok';
    }

    /* ── Build badge HTML ───────────────────────────────────── */
    function badge( statusKey ) {
        const s = STATUS[ statusKey ];
        if ( ! s ) return `<span class="sv-badge">${ escHtml( statusKey ) }</span>`;
        return `<span class="sv-badge ${ s.cls }">${ s.icon } ${ s.label }</span>`;
    }

    /* ── Escape HTML ────────────────────────────────────────── */
    function escHtml( str ) {
        const d = document.createElement( 'div' );
        d.textContent = str ?? '';
        return d.innerHTML;
    }

    /* ═══════════════════════════════════════════════════════════
       RENDER — table rows
    ═══════════════════════════════════════════════════════════ */
    function renderRows( shipments ) {
        if ( ! shipments.length ) {
            tbody.innerHTML = '';
            emptyEl.style.display = 'flex';
            return;
        }
        emptyEl.style.display = 'none';

        tbody.innerHTML = shipments.map( ( s, i ) => {
            const rowCls =
                s.status === 'exception' ? 'sv-row--exception' :
                s.overdue               ? 'sv-row--overdue'   : '';

            const etaCls = etaClass( s );
            const route  = s.origin && s.destination
                ? `<span class="sv-route"><span>${ escHtml( s.origin ) }</span><span class="sv-route-arrow">→</span><span>${ escHtml( s.destination ) }</span></span>`
                : `<span class="sv-route" style="color:var(--text-dim)">—</span>`;

            const delay = i * 30; // stagger animation

            return `
            <tr class="${ rowCls }" data-id="${ s.id }" style="animation-delay:${ delay }ms">
                <td><span class="sv-awb">${ escHtml( s.awb || s.title ) }</span></td>
                <td>${ escHtml( s.carrier ) }</td>
                <td>${ route }</td>
                <td>${ escHtml( s.client ) }</td>
                <td>${ badge( s.status ) }</td>
                <td class="${ etaCls }">${ escHtml( s.eta_human ) }</td>
                <td style="text-align:right">${ s.weight ? escHtml( s.weight ) : '—' }</td>
                <td><span class="sv-notes" title="${ escHtml( s.notes ) }">${ escHtml( s.notes ) }</span></td>
            </tr>`;
        } ).join( '' );

        // Attach row-click listeners for drawer
        tbody.querySelectorAll( 'tr[data-id]' ).forEach( row => {
            row.addEventListener( 'click', () => {
                const id   = parseInt( row.dataset.id, 10 );
                const ship = allShipments.find( s => s.id === id );
                if ( ship ) openDrawer( ship );
            } );
        } );
    }

    /* ═══════════════════════════════════════════════════════════
       DETAIL DRAWER
    ═══════════════════════════════════════════════════════════ */
    function openDrawer( ship ) {
        const updatedDate = ship.updated
            ? new Date( ship.updated ).toLocaleString()
            : '—';

        drawerBody.innerHTML = `
            <div class="sv-detail-awb">${ escHtml( ship.awb || ship.title ) }</div>
            <div class="sv-detail-title">${ escHtml( ship.title ) }</div>
            ${ badge( ship.status ) }
            <div class="sv-detail-grid" style="margin-top:20px">
                ${ field( 'Carrier',     ship.carrier     ) }
                ${ field( 'Client',      ship.client      ) }
                ${ field( 'Origin',      ship.origin      ) }
                ${ field( 'Destination',ship.destination  ) }
                ${ field( 'ETA',        ship.eta_human || '—', etaClass(ship) ) }
                ${ field( 'Weight',     ship.weight ? ship.weight + ' kg' : '—' ) }
            </div>
            ${ ship.notes ? `<div class="sv-detail-notes">${ escHtml( ship.notes ) }</div>` : '' }
            <div class="sv-detail-updated">Last updated: ${ updatedDate }</div>
        `;

        drawer.setAttribute( 'aria-hidden', 'false' );
        overlay.classList.add( 'active' );
    }

    function field( label, value, extraCls = '' ) {
        return `<div class="sv-detail-field">
            <label>${ label }</label>
            <span class="${ extraCls }">${ escHtml( value ) || '—' }</span>
        </div>`;
    }

    function closeDrawer() {
        drawer.setAttribute( 'aria-hidden', 'true' );
        overlay.classList.remove( 'active' );
    }

    closeBtn.addEventListener( 'click', closeDrawer );
    overlay.addEventListener(  'click', closeDrawer );
    document.addEventListener( 'keydown', e => { if ( e.key === 'Escape' ) closeDrawer(); } );

    /* ═══════════════════════════════════════════════════════════
       FILTER + SEARCH (client-side on cached data)
    ═══════════════════════════════════════════════════════════ */
    function applyFilters() {
        const q      = searchEl.value.trim().toLowerCase();
        const status = filterEl.value;

        let result = allShipments;

        if ( status !== 'all' ) {
            result = result.filter( s => s.status === status );
        }

        if ( q ) {
            result = result.filter( s =>
                ( s.awb         || '' ).toLowerCase().includes( q ) ||
                ( s.title       || '' ).toLowerCase().includes( q ) ||
                ( s.client      || '' ).toLowerCase().includes( q ) ||
                ( s.carrier     || '' ).toLowerCase().includes( q ) ||
                ( s.origin      || '' ).toLowerCase().includes( q ) ||
                ( s.destination || '' ).toLowerCase().includes( q )
            );
        }

        renderRows( result );
    }

    let searchDebounce;
    searchEl.addEventListener( 'input', () => {
        clearTimeout( searchDebounce );
        searchDebounce = setTimeout( applyFilters, 200 );
    } );
    filterEl.addEventListener( 'change', applyFilters );

    /* ═══════════════════════════════════════════════════════════
       COUNTDOWN TIMER
    ═══════════════════════════════════════════════════════════ */
    function startCountdown() {
        clearInterval( timerInterval );
        countdownSecs = REFRESH_MS / 1000;

        timerInterval = setInterval( () => {
            countdownSecs = Math.max( 0, countdownSecs - 1 );
            const m = String( Math.floor( countdownSecs / 60 ) ).padStart( 2, '0' );
            const s = String( countdownSecs % 60 ).padStart( 2, '0' );
            timerEl.textContent = `${ m }:${ s }`;
        }, 1000 );
    }

    /* ═══════════════════════════════════════════════════════════
       MAIN LOAD + SCHEDULE
    ═══════════════════════════════════════════════════════════ */
    async function load( isManual = false ) {
        if ( isManual ) {
            refreshBtn.classList.add( 'refreshing' );
        }

        try {
            const data = await fetchShipments();
            allShipments = data.shipments || [];
            renderStats( data.stats || {}, data.generated );
            applyFilters();
            startCountdown();
        } catch ( err ) {
            console.error( '[ShipView] Fetch error:', err );
            tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--accent-red);font-family:var(--font-mono);font-size:12px">⚠ Failed to load shipments. Will retry at next interval.</td></tr>`;
        } finally {
            refreshBtn.classList.remove( 'refreshing' );
        }

        // Schedule next auto-refresh
        clearTimeout( refreshTimer );
        refreshTimer = setTimeout( load, REFRESH_MS );
    }

    refreshBtn.addEventListener( 'click', () => load( true ) );

    /* ── Boot ─────────────────────────────────────────────── */
    load();

} )();
