<?php
declare(strict_types=1);

session_set_cookie_params([
    'httponly' => true,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();

// Set HOTEL_ADMIN_USERNAME and HOTEL_ADMIN_PASSWORD in the server environment before deployment.
$adminUsername = getenv('HOTEL_ADMIN_USERNAME') ?: 'admin';
$adminPassword = getenv('HOTEL_ADMIN_PASSWORD') ?: 'hotel123';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        http_response_code(400);
        $error = 'Your session expired. Please refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') === 'logout') {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $notice = 'You have been logged out.';
    } elseif (($_POST['action'] ?? '') === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (
            is_string($username)
            && is_string($password)
            && hash_equals($adminUsername, $username)
            && hash_equals($adminPassword, $password)
        ) {
            session_regenerate_id(true);
            $_SESSION['authenticated'] = true;
            $_SESSION['username'] = $adminUsername;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }

        $error = 'The username or password you entered is incorrect.';
    }
}

$isAuthenticated = !empty($_SESSION['authenticated']);
$username = htmlspecialchars((string) ($_SESSION['username'] ?? $adminUsername), ENT_QUOTES, 'UTF-8');
$csrfToken = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f7f9">
    <title><?= $isAuthenticated ? 'Overview' : 'Sign in' ?> | Haven Hotel</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #202b33;
            --muted: #78838b;
            --line: #e8ebed;
            --paper: #fff;
            --canvas: #f6f7f9;
            --green: #2d705c;
            --green-dark: #225847;
            --green-pale: #e9f2ee;
            --gold: #c99a55;
            --shadow: 0 14px 40px rgba(27, 44, 49, .07);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--canvas);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 14px;
        }
        button, input { font: inherit; }
        button { cursor: pointer; }
        .app { min-height: 100vh; }
        .sidebar {
            background: #193b35;
            color: #fff;
            display: flex;
            flex-direction: column;
            left: 0;
            min-height: 100vh;
            padding: 28px 18px 20px;
            position: fixed;
            top: 0;
            width: 246px;
        }
        .brand { align-items: center; display: flex; gap: 12px; padding: 0 10px 38px; }
        .brand-mark {
            align-items: center;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.19);
            border-radius: 12px;
            color: #f2d6a8;
            display: flex;
            font-family: Georgia, serif;
            font-size: 21px;
            height: 42px;
            justify-content: center;
            width: 42px;
        }
        .brand-name { font-family: Georgia, "Times New Roman", serif; font-size: 19px; letter-spacing: .2px; }
        .brand-caption { color: #adc1ba; font-size: 10px; letter-spacing: 1.8px; margin-top: 4px; text-transform: uppercase; }
        .nav-label { color: #8fa9a0; font-size: 10px; letter-spacing: 1.4px; margin: 0 11px 12px; text-transform: uppercase; }
        .nav { display: grid; gap: 5px; }
        .nav-item {
            align-items: center;
            border-radius: 9px;
            color: #c5d3ce;
            display: flex;
            gap: 13px;
            padding: 11px 12px;
            text-decoration: none;
        }
        .nav-item.active { background: rgba(255,255,255,.12); color: #fff; }
        .nav-icon { font-size: 17px; text-align: center; width: 19px; }
        .sidebar-bottom { margin-top: auto; }
        .help-card { border: 1px solid rgba(255,255,255,.13); border-radius: 11px; margin: 0 3px 20px; padding: 15px; }
        .help-card strong { display: block; font-size: 12px; margin-bottom: 6px; }
        .help-card span { color: #b2c5bd; font-size: 11px; line-height: 1.55; }
        .profile { align-items: center; border-top: 1px solid rgba(255,255,255,.12); display: flex; gap: 10px; padding: 18px 5px 0; }
        .avatar { align-items: center; background: #d7b781; border-radius: 50%; color: #193b35; display: flex; font-weight: 700; height: 34px; justify-content: center; width: 34px; }
        .profile-name { font-size: 12px; font-weight: 600; }
        .profile-role { color: #adc1ba; font-size: 10px; margin-top: 3px; }
        .main { margin-left: 246px; min-height: 100vh; padding: 30px 42px 45px; }
        .topbar { align-items: center; display: flex; justify-content: space-between; margin-bottom: 30px; }
        .breadcrumb { color: var(--muted); font-size: 12px; }
        .breadcrumb strong { color: var(--ink); font-weight: 600; }
        .topbar-right { align-items: center; display: flex; gap: 18px; }
        .today { color: var(--muted); font-size: 12px; }
        .logout {
            background: transparent;
            border: 1px solid var(--line);
            border-radius: 8px;
            color: #536169;
            padding: 9px 13px;
        }
        .logout:hover { background: #fff; color: var(--green-dark); }
        .welcome { align-items: flex-end; display: flex; justify-content: space-between; margin-bottom: 25px; }
        .eyebrow { color: var(--green); font-size: 10px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; }
        h1 { font-family: Georgia, "Times New Roman", serif; font-size: 32px; font-weight: 400; letter-spacing: -.5px; margin: 8px 0 5px; }
        .subtitle { color: var(--muted); font-size: 13px; }
        .date-chip { background: var(--paper); border: 1px solid var(--line); border-radius: 8px; color: #657079; font-size: 12px; padding: 10px 13px; }
        .stats { display: grid; gap: 15px; grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 22px; }
        .stat-card, .panel { background: var(--paper); border: 1px solid var(--line); border-radius: 12px; }
        .stat-card { min-height: 133px; padding: 18px; }
        .stat-top { align-items: center; color: var(--muted); display: flex; font-size: 12px; justify-content: space-between; }
        .stat-icon { align-items: center; background: var(--green-pale); border-radius: 8px; color: var(--green); display: flex; font-size: 16px; height: 31px; justify-content: center; width: 31px; }
        .stat-value { font-family: Georgia, "Times New Roman", serif; font-size: 29px; margin: 13px 0 6px; }
        .stat-note { color: var(--muted); font-size: 10px; }
        .positive { color: var(--green); font-weight: 600; }
        .content-grid { display: grid; gap: 19px; grid-template-columns: minmax(0, 1.65fr) minmax(265px, .85fr); }
        .panel { overflow: hidden; }
        .panel-head { align-items: center; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; padding: 17px 19px; }
        .panel-title { font-family: Georgia, "Times New Roman", serif; font-size: 17px; }
        .panel-link { background: none; border: 0; color: var(--green); font-size: 11px; font-weight: 600; }
        .table-wrap { overflow-x: auto; }
        table { border-collapse: collapse; min-width: 560px; text-align: left; width: 100%; }
        th { background: #fbfcfc; color: #89939a; font-size: 9px; font-weight: 600; letter-spacing: .8px; padding: 11px 17px; text-transform: uppercase; }
        td { border-top: 1px solid #f0f2f3; color: #4a565e; font-size: 11px; padding: 14px 17px; white-space: nowrap; }
        td:first-child { color: var(--ink); font-weight: 600; }
        .status { border-radius: 20px; display: inline-block; font-size: 9px; padding: 5px 9px; }
        .confirmed { background: #eaf3ed; color: #387153; }
        .checkin { background: #f8f0e2; color: #9a6d25; }
        .checkout { background: #edf0f7; color: #596c9c; }
        .occupancy { padding: 19px; }
        .occupancy-total { align-items: baseline; display: flex; gap: 7px; margin: 5px 0 19px; }
        .occupancy-total strong { font-family: Georgia, "Times New Roman", serif; font-size: 32px; font-weight: 400; }
        .occupancy-total span { color: var(--muted); font-size: 11px; }
        .bar-row { margin: 15px 0; }
        .bar-label { align-items: center; color: #58646b; display: flex; font-size: 11px; justify-content: space-between; margin-bottom: 7px; }
        .bar-label span:last-child { color: #7a858c; }
        .track { background: #edf0ef; border-radius: 9px; height: 6px; overflow: hidden; }
        .fill { background: var(--green); border-radius: 9px; height: 100%; }
        .arrival-list { padding: 4px 19px 10px; }
        .arrival { align-items: center; border-bottom: 1px solid #f0f2f3; display: flex; gap: 11px; padding: 12px 0; }
        .arrival:last-child { border-bottom: 0; }
        .arrival-initial { align-items: center; background: #f1eee8; border-radius: 50%; color: #947442; display: flex; font-size: 10px; font-weight: 700; height: 31px; justify-content: center; width: 31px; }
        .arrival-name { color: #344149; font-size: 11px; font-weight: 600; }
        .arrival-room { color: var(--muted); font-size: 10px; margin-top: 3px; }
        .arrival-time { color: #738078; font-size: 10px; margin-left: auto; }
        .alert { border-radius: 8px; font-size: 12px; margin: 0 0 16px; padding: 12px 14px; }
        .alert.error { background: #fff0ed; color: #9b4134; }
        .alert.success { background: #eaf3ed; color: #387153; }
        .login-page { align-items: center; background: #edf1ef; display: flex; justify-content: center; min-height: 100vh; padding: 24px; }
        .login-card { background: #fff; border: 1px solid #e6ebe8; border-radius: 16px; box-shadow: var(--shadow); max-width: 420px; padding: 38px; width: 100%; }
        .login-brand { align-items: center; color: var(--green-dark); display: flex; gap: 11px; margin-bottom: 34px; }
        .login-brand .brand-mark { background: #193b35; }
        .login-card h1 { font-size: 29px; margin: 0 0 8px; }
        .login-description { color: var(--muted); line-height: 1.6; margin-bottom: 25px; }
        .field { margin-bottom: 17px; }
        .field label { color: #455159; display: block; font-size: 11px; font-weight: 600; margin-bottom: 7px; }
        .field input { border: 1px solid #dfe5e2; border-radius: 8px; outline: none; padding: 12px; width: 100%; }
        .field input:focus { border-color: #7ba995; box-shadow: 0 0 0 3px rgba(45,112,92,.1); }
        .submit { background: var(--green); border: 0; border-radius: 8px; color: #fff; font-weight: 600; margin-top: 5px; padding: 13px; width: 100%; }
        .submit:hover { background: var(--green-dark); }
        .login-foot { color: #8b9691; font-size: 10px; line-height: 1.6; margin-top: 20px; text-align: center; }
        @media (max-width: 1050px) {
            .main { padding: 28px 26px; }
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .content-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 700px) {
            .sidebar { min-height: auto; padding: 13px 16px; position: static; width: 100%; }
            .brand { padding: 0; }
            .sidebar > .nav-label, .sidebar > .nav, .sidebar-bottom { display: none; }
            .main { margin-left: 0; padding: 22px 16px 35px; }
            .topbar { margin-bottom: 26px; }
            .welcome { align-items: flex-start; flex-direction: column; gap: 13px; }
            h1 { font-size: 28px; }
            .stats { gap: 10px; }
            .stat-card { min-height: 121px; padding: 14px; }
            .stat-value { font-size: 25px; }
            .today { display: none; }
            .login-card { padding: 28px 23px; }
        }
        @media (max-width: 370px) {
            .stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<?php if (!$isAuthenticated): ?>
    <main class="login-page">
        <section class="login-card" aria-labelledby="login-title">
            <div class="login-brand">
                <div class="brand-mark" aria-hidden="true">H</div>
                <div>
                    <div class="brand-name">Haven Hotel</div>
                    <div class="brand-caption">Management portal</div>
                </div>
            </div>
            <h1 id="login-title">Welcome back</h1>
            <p class="login-description">Sign in to manage your property and take care of your guests.</p>
            <?php if ($error !== ''): ?><div class="alert error" role="alert"><?= $e($error) ?></div><?php endif; ?>
            <?php if ($notice !== ''): ?><div class="alert success" role="status"><?= $e($notice) ?></div><?php endif; ?>
            <form method="post" action="">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" autocomplete="username" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button class="submit" type="submit">Sign in to dashboard</button>
            </form>
            <p class="login-foot">Demo login: <strong>admin</strong> / <strong>hotel123</strong><br>Set server environment variables before using this portal in production.</p>
        </section>
    </main>
<?php else: ?>
    <div class="app">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark" aria-hidden="true">H</div>
                <div>
                    <div class="brand-name">Haven Hotel</div>
                    <div class="brand-caption">Management portal</div>
                </div>
            </div>
            <div class="nav-label">Workspace</div>
            <nav class="nav" aria-label="Main navigation">
                <a class="nav-item active" href="#overview" aria-current="page"><span class="nav-icon">⌂</span>Overview</a>
                <a class="nav-item" href="#reservations"><span class="nav-icon">▤</span>Reservations</a>
                <a class="nav-item" href="#occupancy"><span class="nav-icon">▦</span>Rooms &amp; rates</a>
                <a class="nav-item" href="#arrivals"><span class="nav-icon">↗</span>Guest services</a>
            </nav>
            <div class="sidebar-bottom">
                <div class="help-card"><strong>Need a hand?</strong><span>Our support team is here to help you run a smoother stay.</span></div>
                <div class="profile">
                    <div class="avatar"><?= $e(strtoupper(substr($username, 0, 1))) ?></div>
                    <div><div class="profile-name"><?= $username ?></div><div class="profile-role">Hotel administrator</div></div>
                </div>
            </div>
        </aside>
        <main class="main" id="overview">
            <header class="topbar">
                <div class="breadcrumb">Workspace <span aria-hidden="true">/</span> <strong>Overview</strong></div>
                <div class="topbar-right">
                    <span class="today"><?= date('l, F j, Y') ?></span>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="logout">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <button class="logout" type="submit">Log out&nbsp; ↗</button>
                    </form>
                </div>
            </header>
            <section class="welcome">
                <div>
                    <div class="eyebrow"><?= date('l, F j') ?></div>
                    <h1>Good morning, <?= $username ?></h1>
                    <div class="subtitle">Here’s what’s happening at your hotel today.</div>
                </div>
                <div class="date-chip">◷ &nbsp;Today’s overview</div>
            </section>
            <section class="stats" aria-label="Hotel performance">
                <article class="stat-card">
                    <div class="stat-top"><span>Occupancy rate</span><span class="stat-icon">▦</span></div>
                    <div class="stat-value">78%</div>
                    <div class="stat-note"><span class="positive">↑ 6.4%</span> &nbsp;compared to last week</div>
                </article>
                <article class="stat-card">
                    <div class="stat-top"><span>Today’s revenue</span><span class="stat-icon">$</span></div>
                    <div class="stat-value">$8,420</div>
                    <div class="stat-note"><span class="positive">↑ 12.8%</span> &nbsp;compared to yesterday</div>
                </article>
                <article class="stat-card">
                    <div class="stat-top"><span>Arrivals today</span><span class="stat-icon">↗</span></div>
                    <div class="stat-value">18</div>
                    <div class="stat-note">Across <strong>14 rooms</strong> · 6 checked in</div>
                </article>
                <article class="stat-card">
                    <div class="stat-top"><span>Departures today</span><span class="stat-icon">↙</span></div>
                    <div class="stat-value">12</div>
                    <div class="stat-note">Next departure at <strong>11:30 AM</strong></div>
                </article>
            </section>
            <div class="content-grid">
                <section class="panel" id="reservations">
                    <div class="panel-head"><div class="panel-title">Recent reservations</div><button class="panel-link" type="button">View all&nbsp; →</button></div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Guest</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr></thead>
                            <tbody>
                                <tr><td>Olivia Bennett</td><td>Deluxe · 204</td><td>Nov 04</td><td>Nov 08</td><td><span class="status checkin">Checked in</span></td></tr>
                                <tr><td>James Wilson</td><td>Garden · 118</td><td>Nov 04</td><td>Nov 06</td><td><span class="status confirmed">Confirmed</span></td></tr>
                                <tr><td>Sofia Martinez</td><td>Suite · 305</td><td>Nov 04</td><td>Nov 09</td><td><span class="status checkin">Checked in</span></td></tr>
                                <tr><td>Ethan Chen</td><td>Classic · 102</td><td>Nov 05</td><td>Nov 07</td><td><span class="status confirmed">Confirmed</span></td></tr>
                                <tr><td>Amara Okafor</td><td>Deluxe · 212</td><td>Nov 05</td><td>Nov 10</td><td><span class="status confirmed">Confirmed</span></td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>
                <div>
                    <section class="panel" id="occupancy" style="margin-bottom:19px">
                        <div class="panel-head"><div class="panel-title">Room occupancy</div><button class="panel-link" type="button">This week⌄</button></div>
                        <div class="occupancy">
                            <div class="occupancy-total"><strong>94</strong><span>of 120 rooms occupied</span></div>
                            <div class="bar-row"><div class="bar-label"><span>Occupied</span><span>78%</span></div><div class="track"><div class="fill" style="width:78%"></div></div></div>
                            <div class="bar-row"><div class="bar-label"><span>Available</span><span>18%</span></div><div class="track"><div class="fill" style="background:#d8b77e;width:18%"></div></div></div>
                            <div class="bar-row"><div class="bar-label"><span>Maintenance</span><span>4%</span></div><div class="track"><div class="fill" style="background:#c9d0ce;width:4%"></div></div></div>
                        </div>
                    </section>
                    <section class="panel" id="arrivals">
                        <div class="panel-head"><div class="panel-title">Next arrivals</div><button class="panel-link" type="button">View schedule&nbsp; →</button></div>
                        <div class="arrival-list">
                            <div class="arrival"><div class="arrival-initial">JW</div><div><div class="arrival-name">James Wilson</div><div class="arrival-room">Garden room · 118</div></div><div class="arrival-time">10:45 AM</div></div>
                            <div class="arrival"><div class="arrival-initial">PN</div><div><div class="arrival-name">Priya Nair</div><div class="arrival-room">Classic room · 106</div></div><div class="arrival-time">11:15 AM</div></div>
                            <div class="arrival"><div class="arrival-initial">RA</div><div><div class="arrival-name">Rafael Alves</div><div class="arrival-room">Deluxe room · 221</div></div><div class="arrival-time">12:30 PM</div></div>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
<?php endif; ?>
</body>
</html>