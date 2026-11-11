<?php
/* Password-protected usage totals for Slide Merge. */
declare(strict_types=1);
require __DIR__ . '/lib.php';

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'");

session_name('sm_stats');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

$password = (string) (sm_config()['stats_password'] ?? '');
$passwordSet = $password !== '' && $password !== 'CHANGE_ME';
$message = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (isset($_POST['logout'])) {
        $_SESSION = [];
        session_destroy();
        header('Location: stats.php');
        exit;
    }
    if ($passwordSet && hash_equals($password, (string) ($_POST['password'] ?? ''))) {
        session_regenerate_id(true);
        $_SESSION['sm_ok'] = true;
        header('Location: stats.php');
        exit;
    }
    sleep(1);
    $message = 'That password is incorrect.';
}

$signedIn = $passwordSet && !empty($_SESSION['sm_ok']);
$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$n = fn($x) => number_format((int) $x);

$error = '';
$data = null;
if ($signedIn) {
    if (!sm_configured()) {
        $error = 'Add your database details to config.php to start counting.';
    } else {
        try {
            $tz = new DateTimeZone((string) (sm_config()['timezone'] ?? 'UTC'));
            $rows = sm_with_table(fn(PDO $db) => $db->query(
                "SELECT DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') AS hour_utc, COUNT(*) AS merges, SUM(presentations) AS made
                 FROM sm_merges GROUP BY hour_utc ORDER BY hour_utc"
            )->fetchAll());
            $add = function (array &$bucket, string $key, array $r): void {
                $bucket[$key]['merges'] = ($bucket[$key]['merges'] ?? 0) + (int) $r['merges'];
                $bucket[$key]['made'] = ($bucket[$key]['made'] ?? 0) + (int) $r['made'];
            };
            $days = [];
            $months = [];
            $total = ['merges' => 0, 'made' => 0];
            $first = null;
            foreach ($rows as $r) {
                $local = (new DateTimeImmutable($r['hour_utc'], new DateTimeZone('UTC')))->setTimezone($tz);
                $first ??= $local;
                $add($days, $local->format('Y-m-d'), $r);
                $add($months, $local->format('Y-m'), $r);
                $total['merges'] += (int) $r['merges'];
                $total['made'] += (int) $r['made'];
            }
            $today = new DateTimeImmutable('now', $tz);
            $recent = [];
            $week = 0;
            for ($i = 0; $i < 30; $i++) {
                $d = $today->modify("-$i days");
                $k = $d->format('Y-m-d');
                $recent[] = ['label' => $d->format('D, M j'), 'merges' => $days[$k]['merges'] ?? 0, 'made' => $days[$k]['made'] ?? 0];
                if ($i < 7) {
                    $week += $days[$k]['merges'] ?? 0;
                }
            }
            krsort($months);
            $data = compact('total', 'first', 'recent', 'months', 'week');
        } catch (Throwable $ex) {
            error_log('Slide Merge stats failed: ' . $ex->getMessage());
            $error = 'Couldn’t connect to the database. Check the details in config.php.';
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Slide Merge Usage</title>
<style>
:root { --bg: #eef1f4; --panel: #fbfcfd; --line: #d3dae1; --ink: #17212b; --muted: #566575; --accent: #1c5fc9; --bad: #b3261e; --bad-soft: #fae1df; color-scheme: light }
@media (prefers-color-scheme: dark) { :root { --bg: #12181f; --panel: #1a222b; --line: #303c49; --ink: #e8edf2; --muted: #9cabba; --accent: #79aaf5; --bad: #f4a29b; --bad-soft: #43201e; color-scheme: dark } }
* { box-sizing: border-box }
body { margin: 0; background: var(--bg); color: var(--ink); font: 15px/1.45 "Segoe UI", system-ui, -apple-system, sans-serif }
main { max-width: 860px; margin: 0 auto; padding: 28px max(16px, env(safe-area-inset-right)) 40px max(16px, env(safe-area-inset-left)); display: grid; gap: 18px }
header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px }
h1 { margin: 0; font-size: 24px }
h2 { margin: 0 0 8px; font-size: 15px }
p { margin: 0 }
.muted { color: var(--muted); font-size: 13.5px }
.tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px }
.tile, section, form.login { background: var(--panel); border: 1px solid var(--line); border-radius: 6px; padding: 14px 16px; min-width: 0 }
.tile b { display: block; font-size: 28px; font-variant-numeric: tabular-nums }
.tile span { color: var(--muted); font-size: 13px }
.cols { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 18px; align-items: start }
table { width: 100%; border-collapse: collapse; font-size: 14px }
th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid var(--line) }
th { font-size: 12px; color: var(--muted); font-weight: 600 }
td.num, th.num { text-align: right; font-variant-numeric: tabular-nums }
tr.zero td { color: var(--muted) }
.wrap { overflow-x: auto }
form.login { display: grid; gap: 10px; max-width: 360px }
input[type=password] { font: inherit; padding: 8px 10px; border: 1px solid var(--line); border-radius: 4px; background: var(--bg); color: var(--ink) }
button { font: inherit; font-weight: 600; padding: 8px 14px; border-radius: 4px; border: 1px solid var(--accent); background: var(--accent); color: #fff; cursor: pointer; justify-self: start }
button.plain { background: transparent; color: var(--ink); border-color: var(--line) }
.err { background: var(--bad-soft); color: var(--bad); padding: 8px 10px; border-radius: 4px }
@media (max-width: 640px) { .cols { grid-template-columns: minmax(0, 1fr) } }
</style>
</head>
<body>
<main>
<header>
  <h1>Slide Merge usage</h1>
  <?php if ($signedIn): ?>
    <form method="post"><button class="plain" name="logout" value="1">Sign out</button></form>
  <?php endif; ?>
</header>

<?php if (!$passwordSet): ?>
  <p class="err">Set a stats password in config.php to open this page.</p>
<?php elseif (!$signedIn): ?>
  <form class="login" method="post">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" autocomplete="current-password" required autofocus>
    <?php if ($message): ?><p class="err"><?= $e($message) ?></p><?php endif; ?>
    <button type="submit">View usage</button>
  </form>
<?php elseif ($error): ?>
  <p class="err"><?= $e($error) ?></p>
<?php else: ?>
  <div class="tiles">
    <div class="tile"><b><?= $n($data['total']['merges']) ?></b><span>Merges downloaded</span></div>
    <div class="tile"><b><?= $n($data['total']['made']) ?></b><span>Presentations made</span></div>
    <div class="tile"><b><?= $n($data['week']) ?></b><span>Merges in the last 7 days</span></div>
  </div>
  <p class="muted"><?= $data['first'] ? 'Counting since ' . $e($data['first']->format('F j, Y')) . '.' : 'No merges recorded yet.' ?> Each merge is one zip download. Times are in <?= $e(sm_config()['timezone'] ?? 'UTC') ?>.</p>
  <div class="cols">
    <section>
      <h2>Last 30 days</h2>
      <div class="wrap"><table>
        <thead><tr><th>Day</th><th class="num">Merges</th><th class="num">Presentations</th></tr></thead>
        <tbody>
        <?php foreach ($data['recent'] as $r): ?>
          <tr class="<?= $r['merges'] ? '' : 'zero' ?>"><td><?= $e($r['label']) ?></td><td class="num"><?= $n($r['merges']) ?></td><td class="num"><?= $n($r['made']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </section>
    <section>
      <h2>By month</h2>
      <?php if (!$data['months']): ?>
        <p class="muted">Nothing yet.</p>
      <?php else: ?>
      <div class="wrap"><table>
        <thead><tr><th>Month</th><th class="num">Merges</th><th class="num">Presentations</th></tr></thead>
        <tbody>
        <?php foreach ($data['months'] as $k => $m): ?>
          <tr><td><?= $e(DateTimeImmutable::createFromFormat('!Y-m', $k)->format('F Y')) ?></td><td class="num"><?= $n($m['merges']) ?></td><td class="num"><?= $n($m['made']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php endif; ?>
    </section>
  </div>
<?php endif; ?>
</main>
</body>
</html>
