<?php
require_once __DIR__ . '/_auth.php';

function bs_admin_layout_start(array $admin,string $active,string $pageTitle): void
{
    $links=[
      ['id'=>'dashboard','href'=>'index.php','icon'=>'🏠','label'=>'Dashboard Admin'],
      ['id'=>'tutors','href'=>'tutors.php','icon'=>'👩‍🏫','label'=>'Kelola Akun Tutor'],
      ['id'=>'students','href'=>'students.php','icon'=>'🎓','label'=>'Kelola Akun Siswa'],
    ];
    $name=(string)($admin['display_name']??$admin['username']??'Admin');
    $csrf=tutor_csrf_token();
    ?>
<a href="#tutor-main" class="bs-tutor-skip">Langsung ke konten</a>
<div id="bs-tutor-overlay" class="bs-tutor-overlay" hidden></div>
<aside class="bs-tutor-sidebar" id="bs-tutor-sidebar" aria-label="Navigasi Admin Sekolah">
    <div class="bs-tutor-brand">
       <a class="bs-tutor-brand-link" href="index.php">
          <span class="bs-tutor-brand-icon" aria-hidden="true">🏫</span>
          <span>BRIGHT <strong>SPACE</strong><small>Portal Admin</small></span>
       </a>
       <button type="button" class="bs-tutor-close" id="bs-tutor-close" aria-label="Tutup menu">✕</button>
    </div>
    <div class="bs-tutor-identity">
       <span class="bs-tutor-avatar" aria-hidden="true">🛡️</span>
       <div><span class="bs-tutor-small">Administrator Sekolah</span><strong><?= tutor_e($name) ?></strong></div>
    </div>
    <nav class="bs-tutor-nav" aria-label="Menu admin">
        <p class="bs-tutor-nav-label">MANAJEMEN AKUN</p>
        <?php foreach($links as $link): ?>
            <a class="bs-tutor-link<?= $active===$link['id']?' is-active':'' ?>"
               <?= $active===$link['id']?'aria-current="page"':'' ?>
               href="<?= tutor_e($link['href']) ?>"><span aria-hidden="true"><?= $link['icon'] ?></span><?= tutor_e($link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="bs-tutor-sidebar-footer">
        <p>Administrasi akun guru dan siswa BRIGHT SPACE. Perubahan akun tidak menghapus progres belajar.</p>
        <form method="post" action="../tutor/logout.php">
            <input type="hidden" name="csrf_token" value="<?= tutor_e($csrf) ?>">
            <button class="bs-tutor-logout" type="submit">🚪 Keluar</button>
        </form>
    </div>
</aside>
<div class="bs-tutor-page">
    <header class="bs-tutor-topbar">
        <div class="bs-tutor-topbar-left">
            <button type="button" class="bs-tutor-menu-toggle" id="bs-tutor-open" aria-label="Buka menu Admin" aria-controls="bs-tutor-sidebar" aria-expanded="false">☰</button>
            <div><p class="bs-tutor-eyebrow">BRIGHT SPACE · ADMIN SEKOLAH</p><h2><?= tutor_e($pageTitle) ?></h2></div>
        </div>
        <div class="bs-tutor-topbar-right">
            <span class="bs-tutor-topbar-person"><span aria-hidden="true">🛡️</span>
                <span><?= tutor_e($name) ?><small>Administrator</small></span>
            </span>
            <form method="post" action="../tutor/logout.php" class="bs-tutor-header-logout">
                <input type="hidden" name="csrf_token" value="<?= tutor_e($csrf) ?>">
                <button type="submit">Keluar <span aria-hidden="true">↗</span></button>
            </form>
        </div>
    </header>
<?php
}

function bs_admin_layout_end(): void
{
    ?>
</div>
<script src="../tutor/assets/tutor-layout.js" defer></script>
<?php
}
