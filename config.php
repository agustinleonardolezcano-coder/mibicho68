<?php
/**
 * Cooperativa FLB — Configuración del sistema
 * NOTA: Este archivo NO genera ninguna salida (sin echo, sin espacios antes de <?php)
 */

// ── Base de datos ──
define('DB_HOST', 'sql303.iceiy.com');
define('DB_NAME', 'icei_41413480_cooperativa_flb');
define('DB_USER', 'icei_41413480');
define('DB_PASS', 'laurachino007');

// ── MercadoPago ──
define('MP_PUBLIC_KEY',   'TEST-a1cbcc07-6abb-48cd-885e-d59c9925bb26');
define('MP_ACCESS_TOKEN', 'TEST-3004825064547856-031521-42b3e37b98a53a5fee853827b48dcc5c-1130408113');
define('MP_MODE',         'sandbox');

// ── Sitio ──
define('BASE_URL',    'https://cooperativafrayluisbeltran.iceiy.com');
define('SITE_NAME',   'Cooperativa Fray Luis Beltrán');
define('SITE_SHORT',  'FLB');

// ── Sesión ──
define('SESSION_NAME',    'flb_session');
define('CSRF_TOKEN_NAME', '_csrf_token');

// ── Configuración PHP sin .htaccess (compatible LiteSpeed) ──
if (!defined('PHP_CONFIGURED')) {
    define('PHP_CONFIGURED', true);
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.use_strict_mode', '1');
    @ini_set('display_errors', '0');
    @ini_set('log_errors', '1');
}
