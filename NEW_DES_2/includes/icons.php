<?php
/**
 * KLD Facility Reservation System — SVG Icon Helper
 * File: includes/icons.php
 *
 * Provides a single icon() function that returns a named Lucide-style inline SVG.
 * All icons are:
 *  - Stroke-based (no fill) with stroke-width 2
 *  - currentColor — automatically inherit the surrounding text color
 *  - Accessible: aria-hidden="true" (decorative; labels come from adjacent text)
 *  - Sized via the $size parameter (px, applied to width & height)
 *
 * Usage: <?php echo icon('user', 18); ?>
 */

function icon(string $name, int $size = 18, string $class = ''): string {
    $cls   = $class ? " class=\"{$class}\"" : '';
    $attrs = "width=\"{$size}\" height=\"{$size}\" viewBox=\"0 0 24 24\""
           . " fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\""
           . " stroke-linecap=\"round\" stroke-linejoin=\"round\""
           . " aria-hidden=\"true\"{$cls}";

    $paths = [

        // Navigation
        'user' =>
            '<circle cx="12" cy="8" r="4"/>'
          . '<path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>',

        'users' =>
            '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>'
          . '<circle cx="9" cy="7" r="4"/>'
          . '<path d="M23 21v-2a4 4 0 0 0-3-3.87"/>'
          . '<path d="M16 3.13a4 4 0 0 1 0 7.75"/>',

        'log-out' =>
            '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>'
          . '<polyline points="16 17 21 12 16 7"/>'
          . '<line x1="21" y1="12" x2="9" y2="12"/>',

        'menu' =>
            '<line x1="3" y1="6"  x2="21" y2="6"/>'
          . '<line x1="3" y1="12" x2="21" y2="12"/>'
          . '<line x1="3" y1="18" x2="21" y2="18"/>',

        'x' =>
            '<line x1="18" y1="6"  x2="6"  y2="18"/>'
          . '<line x1="6"  y1="6"  x2="18" y2="18"/>',

        // Sidebar navigation
        'landmark' =>
            '<line x1="3"  y1="22" x2="21" y2="22"/>'
          . '<line x1="6"  y1="18" x2="6"  y2="11"/>'
          . '<line x1="10" y1="18" x2="10" y2="11"/>'
          . '<line x1="14" y1="18" x2="14" y2="11"/>'
          . '<line x1="18" y1="18" x2="18" y2="11"/>'
          . '<polygon points="12 2 20 7 4 7"/>',

        'bar-chart-2' =>
            '<line x1="18" y1="20" x2="18" y2="10"/>'
          . '<line x1="12" y1="20" x2="12" y2="4"/>'
          . '<line x1="6"  y1="20" x2="6"  y2="14"/>',

        'clipboard-list' =>
            '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>'
          . '<rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>'
          . '<line x1="9" y1="12" x2="15" y2="12"/>'
          . '<line x1="9" y1="16" x2="13" y2="16"/>',

        'settings' =>
            '<circle cx="12" cy="12" r="3"/>'
          . '<path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>',

        'shield' =>
            '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',

        // Facility / amenity icons
        'map-pin' =>
            '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>'
          . '<circle cx="12" cy="10" r="3"/>',

        'wind' =>
            '<path d="M9.59 4.59A2 2 0 1 1 11 8H2"/>'
          . '<path d="M12.59 19.41A2 2 0 1 0 14 16H2"/>'
          . '<path d="M6.91 12.91A2 2 0 1 0 9.17 16H2"/>',

        'lock' =>
            '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>'
          . '<path d="M7 11V7a5 5 0 0 1 10 0v4"/>',

        // Utility
        'calendar' =>
            '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>'
          . '<line x1="16" y1="2" x2="16" y2="6"/>'
          . '<line x1="8"  y1="2" x2="8"  y2="6"/>'
          . '<line x1="3"  y1="10" x2="21" y2="10"/>',

        'bell' =>
            '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>'
          . '<path d="M13.73 21a2 2 0 0 1-3.46 0"/>',

        'alert-circle' =>
            '<circle cx="12" cy="12" r="10"/>'
          . '<line x1="12" y1="8"  x2="12" y2="12"/>'
          . '<line x1="12" y1="16" x2="12.01" y2="16"/>',
    ];

    if (!isset($paths[$name])) {
        return "<svg {$attrs}><circle cx=\"12\" cy=\"12\" r=\"10\"/></svg>";
    }

    return "<svg {$attrs}>{$paths[$name]}</svg>";
}
