<?php
/**
 * Shared page header — outputs the <head> block, nav bar, and opens <main>.
 * Include at the top of every page after requireLogin().
 *
 * @param string $pageTitle  Browser tab / <title> text
 */
function renderHeader($pageTitle = "Student Grades System") {
    $user = currentUser();
    $nav = [
        "index.php"    => ["🏠", "Home"],
        "students.php" => ["🎓", "Students"],
        "grades.php"   => ["📝", "Grades"],
        "search.php"   => ["🔍", "Search"],
        "logs.php"     => ["📋", "Activity Logs"],
    ];
    $current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | GradeTrack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:         #0d0f14;
            --surface:    #151821;
            --surface2:   #1e2230;
            --border:     #2a2f3f;
            --accent:     #5b8af5;
            --accent2:    #f5a623;
            --danger:     #e05a5a;
            --success:    #4caf82;
            --text:       #e8eaf0;
            --text-muted: #7a8099;
            --radius:     10px;
            --font-head:  'Syne', sans-serif;
            --font-body:  'DM Sans', sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: var(--font-body);
            font-size: 15px;
            line-height: 1.6;
            min-height: 100vh;
        }

        /* ── NAV ── */
        nav {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 2rem;
            height: 64px;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .nav-brand {
            font-family: var(--font-head);
            font-weight: 800;
            font-size: 1.25rem;
            color: var(--accent);
            text-decoration: none;
            letter-spacing: -0.02em;
            margin-right: 1rem;
        }
        .nav-brand span { color: var(--accent2); }
        .nav-links { display: flex; gap: 0.25rem; flex: 1; }
        .nav-links a {
            color: var(--text-muted);
            text-decoration: none;
            padding: 0.4rem 0.85rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: all .15s ease;
        }
        .nav-links a:hover  { background: var(--surface2); color: var(--text); }
        .nav-links a.active { background: var(--accent); color: #fff; }
        .nav-user {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .nav-user strong { color: var(--accent2); }
        .btn-logout {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 0.35rem 0.8rem;
            border-radius: 6px;
            cursor: pointer;
            font-family: var(--font-body);
            font-size: 0.8rem;
            text-decoration: none;
            transition: all .15s;
        }
        .btn-logout:hover { border-color: var(--danger); color: var(--danger); }

        /* ── MAIN CONTENT ── */
        main { padding: 2rem; max-width: 1200px; margin: 0 auto; }

        /* ── PAGE HEADER ── */
        .page-header { margin-bottom: 1.75rem; }
        .page-header h1 {
            font-family: var(--font-head);
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }
        .page-header p { color: var(--text-muted); margin-top: 0.25rem; }

        /* ── CARDS ── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .card-title {
            font-family: var(--font-head);
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* ── TABLE ── */
        .table-wrap { overflow-x: auto; border-radius: var(--radius); border: 1px solid var(--border); }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: var(--surface2);
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        tbody tr { border-bottom: 1px solid var(--border); transition: background .1s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: var(--surface2); }
        td { padding: 0.75rem 1rem; font-size: 0.9rem; }

        /* ── FORMS ── */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
        .form-group { display: flex; flex-direction: column; gap: 0.4rem; }
        label { font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; }
        input, select, textarea {
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 0.6rem 0.9rem;
            border-radius: 7px;
            font-family: var(--font-body);
            font-size: 0.9rem;
            transition: border-color .15s;
            width: 100%;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent);
        }
        select option { background: var(--surface2); }

        /* ── BUTTONS ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.55rem 1.1rem;
            border-radius: 7px;
            font-family: var(--font-body);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all .15s;
        }
        .btn-primary   { background: var(--accent);   color: #fff; }
        .btn-primary:hover { opacity: .85; }
        .btn-success   { background: var(--success);  color: #fff; }
        .btn-success:hover { opacity: .85; }
        .btn-danger    { background: var(--danger);   color: #fff; }
        .btn-danger:hover { opacity: .85; }
        .btn-secondary {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-muted);
        }
        .btn-secondary:hover { border-color: var(--accent); color: var(--accent); }
        .btn-sm { padding: 0.3rem 0.7rem; font-size: 0.8rem; }

        /* ── ALERTS ── */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 7px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-success { background: rgba(76,175,130,.12); border: 1px solid var(--success); color: var(--success); }
        .alert-danger  { background: rgba(224,90,90,.12);  border: 1px solid var(--danger);  color: var(--danger); }

        /* ── BADGE ── */
        .badge {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .badge-create { background: rgba(76,175,130,.15); color: var(--success); }
        .badge-read   { background: rgba(91,138,245,.15); color: var(--accent); }
        .badge-update { background: rgba(245,166,35,.15);  color: var(--accent2); }
        .badge-delete { background: rgba(224,90,90,.15);  color: var(--danger); }

        /* ── GRADE PILL ── */
        .grade-pill {
            display: inline-block;
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .grade-pass { background: rgba(76,175,130,.15); color: var(--success); }
        .grade-fail { background: rgba(224,90,90,.15);  color: var(--danger); }

        /* ── STATS GRID ── */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.25rem 1.5rem;
        }
        .stat-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.07em; color: var(--text-muted); }
        .stat-value { font-family: var(--font-head); font-size: 2rem; font-weight: 800; margin-top: 0.25rem; }
        .stat-card:nth-child(1) .stat-value { color: var(--accent); }
        .stat-card:nth-child(2) .stat-value { color: var(--accent2); }
        .stat-card:nth-child(3) .stat-value { color: var(--success); }
        .stat-card:nth-child(4) .stat-value { color: var(--danger); }

        .flex-row   { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
        .text-muted { color: var(--text-muted); font-size: 0.85rem; }
        .mt-1 { margin-top: .5rem; }
        .mt-2 { margin-top: 1rem; }
        .empty-state { text-align: center; padding: 3rem 1rem; color: var(--text-muted); font-size: 0.9rem; }
    </style>
</head>
<body>
<nav>
    <a class="nav-brand" href="/student_grades/index.php">Grade<span>Track</span></a>
    <div class="nav-links">
        <?php foreach ($nav as $href => [$icon, $label]): ?>
            <a href="/student_grades/<?= $href ?>" class="<?= $current === $href ? 'active' : '' ?>">
                <?= $icon ?> <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="nav-user">
        Logged in as <strong><?= htmlspecialchars($user) ?></strong>
        <a href="/student_grades/logout.php" class="btn-logout">Logout</a>
    </div>
</nav>
<main>
<?php
}

/**
 * Closes the <main> and <body> tags.
 */
function renderFooter() {
    echo "</main></body></html>";
}
?>
