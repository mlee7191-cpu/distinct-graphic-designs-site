<?php
/*
 * Enquiry form handler for the home page (POST /enquire.php).
 *
 * For every valid enquiry it (1) saves a row to the MySQL table `enquiries` and (2) emails the full enquiry to the address in the
 * config. The two are independent backups of each other: if the database is down the email still goes out, and if the email fails
 * the row is still saved. Only when both fail does it answer 500, and the page then falls back to opening the visitor's email app.
 *
 * Config (database login, addresses, salt) lives in /home/discomau/enquiry-config.php, one level ABOVE public_html, and is never
 * committed. See enquiry-config.example.php. The table is created automatically on the first enquiry.
 *
 * Written for PHP 7.4+ (no PHP 8-only syntax), because the host's PHP version is not fixed.
 * Anti-spam: hidden honeypot field, minimum time on page, per-IP and site-wide hourly limits, same-origin check.
 */
declare(strict_types=1);

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');

function reply(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

/** Length in characters, not bytes. */
function chars(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

/** Trim to a maximum number of characters. */
function cut(string $s, int $max): string
{
    return function_exists('mb_substr') ? mb_substr($s, 0, $max, 'UTF-8') : substr($s, 0, $max);
}

/** A POST value as a string ('' if missing or an array). */
function post(string $key): string
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '';
}

/** Tidy user input: valid UTF-8 only, control characters removed (newlines kept when $multiline), trimmed and length-capped. */
function clean(string $value, int $max, bool $multiline = false): string
{
    if (preg_match('//u', $value) !== 1) {
        return '';
    }
    if ($multiline) {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = (string) preg_replace('/[^\P{Cc}\n\t]/u', '', $value);
    } else {
        $value = (string) preg_replace('/\p{Cc}+/u', ' ', $value);
    }
    return cut(trim($value), $max);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    reply(405, ['ok' => false]);
}

// Only accept posts from this site's own pages.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    $originHost = parse_url($origin, PHP_URL_HOST);
    $host = (string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''));
    if (!is_string($originHost) || strcasecmp($originHost, $host) !== 0) {
        reply(403, ['ok' => false]);
    }
}

$configFile = dirname(__DIR__) . '/enquiry-config.php';
$config = is_readable($configFile) ? require $configFile : null;
if (!is_array($config)) {
    error_log('enquire.php: config file missing or invalid at ' . $configFile);
    reply(500, ['ok' => false]);
}

// Honeypot: real visitors never see or fill this field. Pretend it worked so bots do not adapt.
if (post('website') !== '') {
    reply(200, ['ok' => true]);
}

$in = [
    'name'         => clean(post('name'), 100),
    'email'        => clean(post('email'), 254),
    'phone'        => cut((string) preg_replace('/[^0-9+()\-\s.]/', '', clean(post('phone'), 40)), 40),
    'service'      => clean(post('service'), 60),
    'details'      => clean(post('details'), 5000, true),
    'page'         => clean(post('page'), 200),
    'landing_page' => clean(post('landing_page'), 200),
    'referrer'     => clean(post('referrer'), 300),
    'utm_source'   => clean(post('utm_source'), 200),
    'utm_medium'   => clean(post('utm_medium'), 200),
    'utm_campaign' => clean(post('utm_campaign'), 200),
    'utm_term'     => clean(post('utm_term'), 200),
    'utm_content'  => clean(post('utm_content'), 200),
    'gclid'        => clean(post('gclid'), 200),
    'ph_distinct_id' => clean(post('ph_id'), 100),
];

if ($in['name'] === '' || $in['service'] === '' || chars($in['details']) < 5 || filter_var($in['email'], FILTER_VALIDATE_EMAIL) === false) {
    reply(422, ['ok' => false, 'message' => 'Please check your name, email, service and project details, then send again.']);
}
// Real people take longer than 3 seconds to write an enquiry. A human who trips this can simply send again.
if ((int) post('elapsed') < 3000) {
    reply(422, ['ok' => false, 'message' => 'That was very quick. Please check your details and send again.']);
}

$pdo = null;
$id = null;
try {
    $pdo = new PDO(
        'mysql:host=' . ($config['db_host'] ?? 'localhost') . ';dbname=' . ($config['db_name'] ?? '') . ';charset=utf8mb4',
        (string) ($config['db_user'] ?? ''),
        (string) ($config['db_pass'] ?? ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 5]
    );

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS enquiries (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    created_at DATETIME NOT NULL COMMENT 'UTC',
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    notes TEXT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(40) NOT NULL DEFAULT '',
    service VARCHAR(60) NOT NULL,
    details TEXT NOT NULL,
    page VARCHAR(200) NOT NULL DEFAULT '',
    landing_page VARCHAR(200) NOT NULL DEFAULT '',
    referrer VARCHAR(300) NOT NULL DEFAULT '',
    utm_source VARCHAR(200) NOT NULL DEFAULT '',
    utm_medium VARCHAR(200) NOT NULL DEFAULT '',
    utm_campaign VARCHAR(200) NOT NULL DEFAULT '',
    utm_term VARCHAR(200) NOT NULL DEFAULT '',
    utm_content VARCHAR(200) NOT NULL DEFAULT '',
    gclid VARCHAR(200) NOT NULL DEFAULT '',
    ph_distinct_id VARCHAR(100) NOT NULL DEFAULT '',
    ip_hash CHAR(64) NOT NULL,
    KEY created_at (created_at),
    KEY ip_hash_created (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL
    );

    // The IP is stored only as a salted hash, used for the rate limit. It cannot be turned back into an address.
    $ipHash = hash('sha256', (string) ($config['salt'] ?? '') . (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    $since = gmdate('Y-m-d H:i:s', time() - 3600);

    $perIp = $pdo->prepare('SELECT COUNT(*) FROM enquiries WHERE ip_hash = ? AND created_at > ?');
    $perIp->execute([$ipHash, $since]);
    $sitewide = $pdo->prepare('SELECT COUNT(*) FROM enquiries WHERE created_at > ?');
    $sitewide->execute([$since]);
    if ((int) $perIp->fetchColumn() >= 5 || (int) $sitewide->fetchColumn() >= 60) {
        reply(429, ['ok' => false]);
    }

    $row = ['created_at' => gmdate('Y-m-d H:i:s')] + $in + ['ip_hash' => $ipHash];
    $insert = $pdo->prepare(
        'INSERT INTO enquiries (' . implode(', ', array_keys($row)) . ') VALUES (' . implode(', ', array_fill(0, count($row), '?')) . ')'
    );
    $insert->execute(array_values($row));
    $id = (int) $pdo->lastInsertId();
} catch (Throwable $e) {
    // Do not lose the enquiry: carry on and send the email.
    error_log('enquire.php: database error: ' . $e->getMessage());
}

$to = (string) ($config['to'] ?? '');
$from = filter_var($config['from'] ?? '', FILTER_VALIDATE_EMAIL) !== false ? (string) $config['from'] : $to;

$body = implode("\r\n", [
    'New website enquiry' . ($id !== null ? ' #' . $id : ' (NOT saved to the database, this email is the only copy)'),
    '',
    'Name: ' . $in['name'],
    'Email: ' . $in['email'],
    'Phone: ' . ($in['phone'] !== '' ? $in['phone'] : 'Not provided'),
    'Service: ' . $in['service'],
    '',
    'Details:',
    str_replace("\n", "\r\n", $in['details']),
    '',
    '--',
    'Sent from page: ' . $in['page'],
    'First landing page this visit: ' . $in['landing_page'],
    'Referrer: ' . ($in['referrer'] !== '' ? $in['referrer'] : 'Direct or unknown'),
    'UTM source / medium / campaign: ' . $in['utm_source'] . ' / ' . $in['utm_medium'] . ' / ' . $in['utm_campaign'],
    'UTM term / content: ' . $in['utm_term'] . ' / ' . $in['utm_content'],
    'Google click ID: ' . ($in['gclid'] !== '' ? $in['gclid'] : 'None'),
    'PostHog ID: ' . ($in['ph_distinct_id'] !== '' ? $in['ph_distinct_id'] : 'None'),
    'Received (UTC): ' . gmdate('Y-m-d H:i:s'),
]);

$subject = '=?UTF-8?B?' . base64_encode('Website enquiry — ' . $in['service']) . '?=';
$headers = implode("\r\n", [
    'From: Distinct Graphic Designs <' . $from . '>',
    'Reply-To: ' . $in['email'],
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: quoted-printable',
]);
$mailed = $to !== '' && @mail($to, $subject, quoted_printable_encode($body), $headers, '-f' . $from);
if (!$mailed) {
    error_log('enquire.php: email failed for enquiry ' . ($id !== null ? '#' . $id : '(not saved)'));
}

if ($id === null && !$mailed) {
    reply(500, ['ok' => false]);
}
reply(200, ['ok' => true]);
