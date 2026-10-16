<?php
require_once 'config/functions.php';
require_role('staff');
require_once 'config/database.php';

// Placeholder officials are used until real barangay records are entered.
$officials = $pdo->query("SELECT id, full_name, position, status, term_start, term_end FROM barangay_officials ORDER BY CASE position WHEN 'Barangay Captain' THEN 1 WHEN 'Kagawad' THEN 2 WHEN 'SK Chairperson' THEN 3 ELSE 4 END, full_name ASC")->fetchAll();
$residentCount = (int) $pdo->query("SELECT COUNT(*) FROM residents")->fetchColumn();
$householdCount = (int) $pdo->query("SELECT COUNT(*) FROM households")->fetchColumn();
$activeCount = (int) $pdo->query("SELECT COUNT(*) FROM residents WHERE status = 'Active'")->fetchColumn();
start_page('Barangay Officials');
?>
<style>
:root{--navy:#172b4d;--blue:#2864dc;--sky:#edf4ff;--ink:#1c2940;--muted:#6e7b91;--line:#e5eaf2;--surface:#fff}
body{background:#f4f7fb;color:var(--ink)}.dashboard-shell{display:grid;grid-template-columns:225px minmax(0,1fr);min-height:calc(100vh - 70px)}
.side-nav{background:var(--navy);color:#dbe6f7;padding:25px 15px}.side-brand{display:flex;align-items:center;gap:11px;padding:0 10px 26px;border-bottom:1px solid #ffffff20;margin-bottom:22px}.brand-mark{width:39px;height:39px;border-radius:12px;background:#fff;color:var(--blue);display:grid;place-items:center;font-weight:800;font-size:20px}.side-brand strong{display:block;color:#fff}.side-brand small{font-size:11px;color:#aebed8}.side-label{font-size:10px;letter-spacing:1.5px;color:#8fa6c8;padding:15px 11px 8px}.side-link{display:flex;gap:11px;align-items:center;padding:11px;border-radius:10px;color:#dbe6f7;text-decoration:none;font-size:13px;margin:3px 0}.side-link:hover,.side-link.active{background:#ffffff18;color:#fff}.side-link .ico{width:21px;text-align:center;font-size:16px}.dashboard-main{padding:28px;min-width:0}.dash-top{display:flex;justify-content:space-between;align-items:center;gap:18px;margin-bottom:26px}.eyebrow{font-size:11px;font-weight:700;letter-spacing:1.5px;color:var(--blue);text-transform:uppercase}.dash-top h1{font-size:27px;margin:5px 0 7px;letter-spacing:-.7px}.dash-top p{color:var(--muted);margin:0;font-size:13px}.date-pill{background:#fff;border:1px solid var(--line);padding:10px 14px;border-radius:12px;color:#52617a;font-size:12px;white-space:nowrap}.metric-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:27px}.metric{background:#fff;border:1px solid var(--line);border-radius:15px;padding:18px;display:flex;align-items:center;gap:14px;box-shadow:0 5px 18px #1b315008}.metric-icon{height:45px;width:45px;border-radius:13px;background:var(--sky);color:var(--blue);display:grid;place-items:center;font-size:21px}.metric span{display:block;color:var(--muted);font-size:12px}.metric strong{display:block;font-size:25px;line-height:1.4}.section-head{display:flex;justify-content:space-between;align-items:end;gap:15px;margin-bottom:15px}.section-head h2{margin:0;font-size:18px}.section-head p{margin:5px 0 0;color:var(--muted);font-size:12px}.official-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.official-card{background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden;box-shadow:0 7px 22px #1b315008;min-width:0}.official-cover{height:94px;background:linear-gradient(125deg,#214bb4,#4385f2);position:relative;padding:17px 18px;color:#fff}.official-cover.teal{background:linear-gradient(125deg,#087e8b,#32b8b5)}.official-cover.purple{background:linear-gradient(125deg,#5942b5,#9780f5)}.official-cover.orange{background:linear-gradient(125deg,#b75b23,#f3a34d)}.official-cover.slate{background:linear-gradient(125deg,#394960,#71829b)}.official-cover.green{background:linear-gradient(125deg,#167653,#42b68a)}.position-tag{font-size:10px;letter-spacing:1px;text-transform:uppercase;opacity:.85}.official-cover h3{font-size:16px;margin:7px 0 0;font-weight:650}.avatar{width:65px;height:65px;border-radius:20px;background:#eaf0fb;border:4px solid #fff;display:grid;place-items:center;color:#284b91;font-size:20px;font-weight:800;position:absolute;left:17px;bottom:-32px;box-shadow:0 5px 12px #10264a18}.avatar.a2{background:#e3f8f4;color:#087e8b}.avatar.a3{background:#f0eaff;color:#5942b5}.avatar.a4{background:#fff0e2;color:#b75b23}.avatar.a5{background:#e9eef5;color:#394960}.avatar.a6{background:#e3f7ed;color:#167653}.official-body{padding:42px 17px 17px}.official-name{font-size:15px;font-weight:750;margin:0 0 4px}.official-term{font-size:11px;color:var(--muted);margin-bottom:15px}.official-meta{display:flex;justify-content:space-between;align-items:center;padding-top:13px;border-top:1px solid var(--line);font-size:11px;color:var(--muted)}.status-chip{display:inline-flex;align-items:center;gap:6px;padding:5px 8px;border-radius:20px;background:#e8f8ef;color:#16814a;font-weight:700}.status-chip:before{content:'';width:6px;height:6px;border-radius:50%;background:#20a464}.status-chip.inactive{background:#fff0ed;color:#b54735}.status-chip.inactive:before{background:#d75a49}.view-link{font-weight:700;color:var(--blue);text-decoration:none}.placeholder-note{margin-top:20px;padding:14px 16px;background:#edf4ff;border:1px solid #dbe8ff;color:#52698c;border-radius:12px;font-size:12px}.quick-links{display:flex;gap:8px;flex-wrap:wrap}.quick-links a{font-size:12px;text-decoration:none;color:var(--blue);background:#fff;border:1px solid var(--line);padding:9px 12px;border-radius:10px;font-weight:650}
@media(max-width:1000px){.dashboard-shell{grid-template-columns:185px minmax(0,1fr)}.official-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.dashboard-main{padding:22px}.metric-grid{gap:9px}.metric{padding:13px}}
@media(max-width:680px){.dashboard-shell{display:block}.side-nav{padding:10px;display:flex;overflow-x:auto;gap:5px}.side-brand,.side-label{display:none}.side-link{white-space:nowrap;margin:0;padding:10px}.dashboard-main{padding:18px 13px}.dash-top{align-items:flex-start;flex-direction:column}.metric-grid{grid-template-columns:1fr}.official-grid{grid-template-columns:1fr}.section-head{align-items:flex-start;flex-direction:column}}
</style>
<div class="dashboard-shell">
    <aside class="side-nav">
        <div class="side-brand"><div class="brand-mark">B</div><div><strong>Barangay Hub</strong><small>Resident Services</small></div></div>
        <div class="side-label">WORKSPACE</div>
        <a class="side-link" href="index.php"><span class="ico">▦</span>Overview</a>
        <a class="side-link active" href="officials.php"><span class="ico">♙</span>Officials</a>
        <a class="side-link" href="residents.php"><span class="ico">♧</span>Residents</a>
        <a class="side-link" href="resident_form.php"><span class="ico">＋</span>Register resident</a>
        <a class="side-link" href="households.php"><span class="ico">⌂</span>Households</a>
        <div class="side-label">RECORDS</div>
        <a class="side-link" href="reports.php"><span class="ico">▤</span>Reports & print</a>
    </aside>
    <main class="dashboard-main">
        <div class="dash-top">
            <div><div class="eyebrow">Barangay directory</div><h1>Meet your local officials</h1><p>A simple overview of the people serving your community.</p></div>
            <div class="date-pill">● Demo workspace &nbsp; / &nbsp; Placeholder data</div>
        </div>
        <div class="metric-grid">
            <div class="metric"><div class="metric-icon">♙</div><div><span>Directory entries</span><strong><?= count($officials) ?></strong></div></div>
            <div class="metric"><div class="metric-icon">♧</div><div><span>Resident records</span><strong><?= number_format($residentCount) ?></strong></div></div>
            <div class="metric"><div class="metric-icon">⌂</div><div><span>Households</span><strong><?= number_format($householdCount) ?></strong></div></div>
        </div>
        <div class="section-head"><div><h2>Official directory</h2><p>Sample profiles use initials and placeholder names. Replace them with approved records.</p></div><div class="quick-links"><a href="residents.php">Browse residents ↗</a><a href="reports.php">Open reports ↗</a></div></div>
        <div class="official-grid">
            <?php
            $colors = ['', 'teal', 'purple', 'orange', 'slate', 'green'];
            $avatars = ['', 'a2', 'a3', 'a4', 'a5', 'a6'];
            if (!$officials):
                $officials = [
                    ['id'=>0,'full_name'=>'Official Name 01','position'=>'Barangay Captain','status'=>'Active','term_start'=>null,'term_end'=>null],
                    ['id'=>0,'full_name'=>'Official Name 02','position'=>'Kagawad','status'=>'Active','term_start'=>null,'term_end'=>null],
                    ['id'=>0,'full_name'=>'Official Name 03','position'=>'Kagawad','status'=>'Active','term_start'=>null,'term_end'=>null],
                    ['id'=>0,'full_name'=>'Official Name 04','position'=>'SK Chairperson','status'=>'Active','term_start'=>null,'term_end'=>null],
                    ['id'=>0,'full_name'=>'Official Name 05','position'=>'Secretary','status'=>'Active','term_start'=>null,'term_end'=>null],
                    ['id'=>0,'full_name'=>'Official Name 06','position'=>'Treasurer','status'=>'Active','term_start'=>null,'term_end'=>null]
                ];
            endif;
            foreach ($officials as $i => $official):
                $name = trim($official['full_name'] ?? 'Official Placeholder');
                $parts = preg_split('/\s+/', $name);
                $initials = strtoupper(substr($parts[0] ?? 'O', 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                $active = ($official['status'] ?? 'Active') === 'Active';
                $term = 'Term dates not set';
                if (!empty($official['term_start']) || !empty($official['term_end'])) {
                    $term = (!empty($official['term_start']) ? date('M Y', strtotime($official['term_start'])) : '—') . ' – ' . (!empty($official['term_end']) ? date('M Y', strtotime($official['term_end'])) : 'Present');
                }
            ?>
            <article class="official-card">
                <div class="official-cover <?= $colors[$i % count($colors)] ?>"><div class="position-tag">Community leadership</div><h3><?= e($official['position'] ?? 'Official') ?></h3><div class="avatar <?= $avatars[$i % count($avatars)] ?>"><?= e($initials) ?></div></div>
                <div class="official-body"><p class="official-name"><?= e($name) ?></p><div class="official-term"><?= e($term) ?></div><div class="official-meta"><span class="status-chip <?= $active ? '' : 'inactive' ?>"><?= e($official['status'] ?? 'Unknown') ?></span><a class="view-link" href="residents.php" aria-label="Browse resident records">Directory ↗</a></div></div>
            </article>
            <?php endforeach; ?>
        </div>
        <div class="placeholder-note"><strong>Demo note:</strong> The names and initials above are placeholders when the officials table is empty. This page does not use real portraits or represent actual elected officials.</div>
    </main>
</div>
<?php end_page(); ?>
