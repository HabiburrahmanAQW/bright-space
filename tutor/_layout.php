<?php
/**
 * Shared layout for authenticated tutor pages.
 * The student's session/cookies are intentionally not used here.
 */
require_once __DIR__ . '/_auth.php';

function tutor_layout_start(array $tutor, string $active, string $pageTitle): void
{
    $isAdmin = ($tutor['role'] ?? '') === 'admin';
    $links = $isAdmin
        ? [
            ['id' => 'dashboard', 'href' => 'index.php', 'icon' => '🏫', 'label' => 'Dashboard Admin'],
            ['id' => 'accounts', 'href' => 'accounts.php', 'icon' => '👩‍🏫', 'label' => 'Daftar & Persetujuan Tutor'],
            ['id' => 'directory', 'href' => 'student-directory.php', 'icon' => '🎓', 'label' => 'Daftar Seluruh Siswa'],
        ]
        : [
            ['id' => 'dashboard', 'href' => 'index.php', 'icon' => '🏠', 'label' => 'Dashboard Tutor'],
            ['id' => 'planets', 'href' => 'planet-form.php', 'icon' => '🪐', 'label' => 'Tambah Planet'],
            ['id' => 'questions', 'href' => 'index.php#planet-list-title', 'icon' => '📚', 'label' => 'Kelola Soal Misi'],
            ['id' => 'students', 'href' => 'students.php', 'icon' => '👩‍🎓', 'label' => 'Tambah & Kelola Siswa'],
        ];
    $displayName = (string)($tutor['display_name'] ?? $tutor['username'] ?? 'Tutor');
    $role = (($tutor['role'] ?? '') === 'admin') ? 'Administrator' : 'Tutor';
    $csrf = tutor_csrf_token();
    ?>
<a href="#tutor-main" class="bs-tutor-skip">Langsung ke konten</a>
<div id="bs-tutor-overlay" class="bs-tutor-overlay" hidden></div>
<aside class="bs-tutor-sidebar" id="bs-tutor-sidebar" aria-label="Navigasi tutor">
    <div class="bs-tutor-brand">
        <a href="index.php" class="bs-tutor-brand-link">
            <span class="bs-tutor-brand-icon" aria-hidden="true"><?= $isAdmin ? '🏫' : '🚀' ?></span>
            <span>BRIGHT <strong>SPACE</strong><small><?= $isAdmin ? 'Portal Admin' : 'Portal Tutor' ?></small></span>
        </a>
        <button type="button" id="bs-tutor-close" class="bs-tutor-close" aria-label="Tutup menu">✕</button>
    </div>

    <div class="bs-tutor-identity">
        <span class="bs-tutor-avatar" aria-hidden="true"><?= $isAdmin ? '🛡️' : '📚' ?></span>
        <div><span class="bs-tutor-small"><?= tutor_e($role) ?></span>
             <strong><?= tutor_e($displayName) ?></strong></div>
    </div>

    <nav class="bs-tutor-nav" aria-label="Menu utama">
        <p class="bs-tutor-nav-label"><?= $isAdmin ? 'MENU ADMIN' : 'MENU TUTOR' ?></p>
        <?php foreach ($links as $link): ?>
            <a href="<?= tutor_e($link['href']) ?>"
               class="bs-tutor-link<?= $active === $link['id'] ? ' is-active' : '' ?>"
               <?= $active === $link['id'] ? 'aria-current="page"' : '' ?>>
                <span aria-hidden="true"><?= $link['icon'] ?></span>
                <?= tutor_e($link['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="bs-tutor-sidebar-footer">
        <p><?= $isAdmin ? 'Pantau akun tutor dan siswa serta tinjau pendaftaran pengajar.' : 'Kelola materi pembelajaran dan terbitkan misi untuk siswa.' ?></p>
        <form action="logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= tutor_e($csrf) ?>">
            <button type="submit" class="bs-tutor-logout">🚪 Keluar</button>
        </form>
    </div>
</aside>

<div class="bs-tutor-page">
    <header class="bs-tutor-topbar">
        <div class="bs-tutor-topbar-left">
            <button type="button" id="bs-tutor-open" class="bs-tutor-menu-toggle"
                    aria-label="Buka menu tutor" aria-controls="bs-tutor-sidebar" aria-expanded="false">☰</button>
            <div>
                <p class="bs-tutor-eyebrow">BRIGHT SPACE · <?= $isAdmin ? 'ADMIN SEKOLAH' : 'TUTOR STUDIO' ?></p>
                <h2><?= tutor_e($pageTitle) ?></h2>
            </div>
        </div>
        <div class="bs-tutor-topbar-right">
            <span class="bs-tutor-topbar-person"><span aria-hidden="true">👩‍🏫</span>
                <span><?= tutor_e($displayName) ?><small><?= tutor_e($role) ?></small></span>
            </span>
            <form method="post" action="logout.php" class="bs-tutor-header-logout">
                <input type="hidden" name="csrf_token" value="<?= tutor_e($csrf) ?>">
                <button type="submit" aria-label="Keluar dari BRIGHT SPACE">Keluar <span aria-hidden="true">↗</span></button>
            </form>
        </div>
    </header>
<?php
}

function tutor_layout_end(): void
{
    ?>
</div>
<script src="assets/tutor-layout.js" defer></script>
<?php
}
