<?php
// ============================================================
// profile.php — Profil Kapten (Session-based, no DB)
// Baca koin & koleksi dari $_SESSION
// ============================================================
define('BRIGHT_SPACE', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/config/database.php';
require_login();

$user = current_user();

function e($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }

require __DIR__ . '/config/planets.php';

// ============================================================
// BS_STAGE6B_PROFILE_INVENTORY: baca inventaris MySQL, bukan session/localStorage.
require_once __DIR__.'/config/shop_inventory.php';
$studentId=bs_shop_student_id();
$pesanSukses='';$pesanError='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['aksi'])){
    if(!bs_shop_valid_csrf($_POST['csrf_token']??null)){http_response_code(403);exit('Token keamanan tidak valid.');}
    try {
        $id=is_string($_POST['item_id']??null)?trim($_POST['item_id']):'';
        $action=(string)$_POST['aksi'];
        if($action==='pakai')bs_shop_set_equipped($studentId,$id,true);
        elseif($action==='lepas')bs_shop_set_equipped($studentId,$id,false);
        else throw new DomainException('Aksi tidak dikenal.');
        $_SESSION['bs_profile_flash']=['ok'=>true,'msg'=>'Perlengkapan berhasil disimpan!'];
    }catch(DomainException $e){$_SESSION['bs_profile_flash']=['ok'=>false,'msg'=>$e->getMessage()];}
    catch(Throwable $e){error_log('Profile 6B: '.$e->getMessage());$_SESSION['bs_profile_flash']=['ok'=>false,'msg'=>'Perubahan gagal. Coba lagi.'];}
    header('Location: profile.php',true,303);exit;
}
$flash=$_SESSION['bs_profile_flash']??null;unset($_SESSION['bs_profile_flash']);
if($flash){if($flash['ok'])$pesanSukses=$flash['msg'];else $pesanError=$flash['msg'];}
try{$state=bs_shop_state($studentId);}catch(Throwable $e){error_log('Profile state 6B: '.$e->getMessage());http_response_code(503);exit('Inventaris tidak tersedia.');}
$koinSekarang=$state['coins'];$koleksi=$state['collection'];
$topiAktif=$state['topi'];$kacamataAktif=$state['kacamata'];
// Agar tampilan profil dan hero menggunakan item yang aktif dari DB.
// Helper: cek item sedang dipakai
function sedangDipakai($id, $topi, $kacamata) {
    return ($topi === $id) || ($kacamata === $id);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Kapten - BRIGHT SPACE</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cosmic: { bg: '#E0F2FE', card: '#EDE9FE', accent: '#FEF3C7', text: '#1E1B4B' }
                    },
                    fontFamily: { nunito: ['Nunito', 'ui-rounded', 'system-ui', 'sans-serif'] }
                }
            }
        };
    </script>
    <style>
        @keyframes float-frame { 0%,100%{transform:translateY(0);}50%{transform:translateY(-6px);} }
        @keyframes glow-ring { 0%,100%{box-shadow:0 0 0 0 rgba(139,92,246,0.4);}50%{box-shadow:0 0 0 16px rgba(139,92,246,0);} }
        @keyframes sparkle { 0%,100%{opacity:0.3;transform:scale(0.8);}50%{opacity:1;transform:scale(1.2);} }
        @keyframes bob { 0%,100%{transform:translateY(0);}50%{transform:translateY(-3px);} }
        @keyframes slide-down { 0%{opacity:0;transform:translateY(-10px);}100%{opacity:1;transform:translateY(0);} }
        .profile-float { animation: float-frame 3s ease-in-out infinite; }
        .profile-glow  { animation: glow-ring 2.5s ease-in-out infinite; }
        .sparkle { animation: sparkle 1.5s ease-in-out infinite; }
        .astronot-bob { animation: bob 2.5s ease-in-out infinite; }
        .slide-down { animation: slide-down 0.3s ease-out; }
    </style>
</head>
<body class="font-nunito bg-cosmic-bg text-cosmic-text min-h-screen flex flex-col antialiased">

    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <div class="lg:ml-72 min-h-screen flex flex-col">

        <?php include __DIR__ . '/components/header.php'; ?>

        <main class="max-w-5xl mx-auto px-4 py-6 w-full flex-1">
<aside class="mb-4 bg-white border-2 border-violet-200 rounded-2xl p-4 text-sm font-bold flex flex-wrap justify-between gap-2">
  <span>✨ Total XP: <?= (int)($user['total_xp'] ?? 0) ?> &nbsp; · &nbsp; ⭐ Level <?= (int)($user['level'] ?? 1) ?></span>
  <a href="progres-misi.php" class="text-violet-700 font-black underline">Lihat progres misi →</a>
</aside>

            <!-- NOTIFIKASI -->
            <?php if ($pesanSukses): ?>
                <div class="mb-4 slide-down flex items-center gap-3 bg-emerald-100 border-2 border-emerald-400 text-emerald-800 font-bold px-4 py-3 rounded-2xl shadow-md">
                    <span class="text-2xl">✅</span>
                    <span class="flex-1"><?= e($pesanSukses) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($pesanError): ?>
                <div class="mb-4 slide-down flex items-center gap-3 bg-rose-100 border-2 border-rose-400 text-rose-800 font-bold px-4 py-3 rounded-2xl shadow-md">
                    <span class="text-2xl">⚠️</span>
                    <span class="flex-1"><?= e($pesanError) ?></span>
                </div>
            <?php endif; ?>

            <!-- KARTU PROFIL + KARAKTER -->
            <section class="mb-6">
                <div class="relative overflow-hidden bg-gradient-to-br from-violet-500 via-indigo-500 to-purple-600 rounded-3xl shadow-xl p-6 sm:p-8 text-white">

                    <div class="absolute top-4 right-4 text-5xl opacity-20 select-none">✨</div>
                    <div class="absolute bottom-4 left-4 text-4xl opacity-15 select-none">🌌</div>
                    <div class="absolute top-1/3 left-1/4 text-2xl sparkle select-none">⭐</div>
                    <div class="absolute bottom-1/4 right-1/3 text-xl sparkle select-none" style="animation-delay: 0.5s;">✨</div>

                    <div class="relative flex flex-col sm:flex-row items-center gap-6">

                        <!-- KARAKTER ASTRONOT + LAYER ITEM -->
                        <div class="profile-float shrink-0">
                            <div class="profile-glow relative w-44 h-44 sm:w-52 sm:h-52 rounded-full bg-gradient-to-br from-amber-200 via-yellow-300 to-amber-400 border-4 border-amber-300 flex items-center justify-center shadow-2xl">

                                <div class="astronot-bob relative w-36 h-36 sm:w-44 sm:h-44">
                                    <svg viewBox="0 0 200 200" class="w-full h-full" aria-hidden="true">

                                        <!-- Bayangan -->
                                        <ellipse cx="100" cy="185" rx="50" ry="8" fill="#000" opacity="0.15"/>

                                        <!-- Badan -->
                                        <rect x="70" y="130" width="60" height="45" rx="20" fill="#FFFFFF" stroke="#1E1B4B" stroke-width="3"/>
                                        <rect x="85" y="140" width="30" height="20" rx="4" fill="#C4B5FD" stroke="#1E1B4B" stroke-width="2"/>
                                        <circle cx="93" cy="148" r="2" fill="#FCD34D"/>
                                        <circle cx="100" cy="148" r="2" fill="#34D399"/>
                                        <circle cx="107" cy="148" r="2" fill="#F87171"/>
                                        <rect x="88" y="154" width="24" height="3" rx="1" fill="#7DD3FC"/>

                                        <!-- Lengan -->
                                        <ellipse cx="62" cy="145" rx="14" ry="20" fill="#FFFFFF" stroke="#1E1B4B" stroke-width="3"/>
                                        <ellipse cx="138" cy="145" rx="14" ry="20" fill="#FFFFFF" stroke="#1E1B4B" stroke-width="3"/>

                                        <!-- Kaki -->
                                        <rect x="78" y="172" width="18" height="12" rx="6" fill="#FFFFFF" stroke="#1E1B4B" stroke-width="3"/>
                                        <rect x="104" y="172" width="18" height="12" rx="6" fill="#FFFFFF" stroke="#1E1B4B" stroke-width="3"/>

                                        <!-- Helm -->
                                        <circle cx="100" cy="85" r="52" fill="#FFFFFF" stroke="#1E1B4B" stroke-width="3"/>
                                        <line x1="100" y1="30" x2="100" y2="15" stroke="#1E1B4B" stroke-width="3" stroke-linecap="round"/>
                                        <circle cx="100" cy="10" r="6" fill="#FCD34D" stroke="#B45309" stroke-width="2"/>

                                        <!-- Kaca helm -->
                                        <rect x="58" y="55" width="84" height="60" rx="30" fill="#1E1B4B" stroke="#1E1B4B" stroke-width="3"/>
                                        <path d="M72 68 q10-8 25-8" fill="none" stroke="#FFFFFF" stroke-opacity="0.5" stroke-width="3" stroke-linecap="round"/>

                                        <!-- Mata -->
                                        <circle cx="83" cy="85" r="9" fill="#7DD3FC"/>
                                        <circle cx="117" cy="85" r="9" fill="#7DD3FC"/>
                                        <circle cx="85" cy="87" r="4" fill="#1E1B4B"/>
                                        <circle cx="119" cy="87" r="4" fill="#1E1B4B"/>
                                        <circle cx="82" cy="82" r="2.5" fill="#FFFFFF"/>
                                        <circle cx="116" cy="82" r="2.5" fill="#FFFFFF"/>

                                        <!-- Pipi + senyum -->
                                        <ellipse cx="70" cy="98" rx="6" ry="4" fill="#F9A8D4" opacity="0.7"/>
                                        <ellipse cx="130" cy="98" rx="6" ry="4" fill="#F9A8D4" opacity="0.7"/>
                                        <path d="M92 100 q8 6 16 0" fill="none" stroke="#7DD3FC" stroke-width="3" stroke-linecap="round"/>

                                        <text x="100" y="165" text-anchor="middle" font-family="Nunito, sans-serif" font-size="9" font-weight="900" fill="#7C3AED">KAPTEN</text>

                                        <!-- LAYER KACAMATA -->
                                        <?php if ($kacamataAktif): ?>
                                            <g>
                                                <rect x="66" y="76" width="28" height="20" rx="8" fill="#FDE68A" stroke="#1E1B4B" stroke-width="2.5" opacity="0.95"/>
                                                <rect x="106" y="76" width="28" height="20" rx="8" fill="#FDE68A" stroke="#1E1B4B" stroke-width="2.5" opacity="0.95"/>
                                                <line x1="94" y1="84" x2="106" y2="84" stroke="#1E1B4B" stroke-width="2.5" stroke-linecap="round"/>
                                                <polygon points="80,80 81,83 84,83.2 81.5,85 82.3,88 80,86.5 77.7,88 78.5,85 76,83.2 79,83" fill="#F59E0B"/>
                                                <polygon points="120,80 121,83 124,83.2 121.5,85 122.3,88 120,86.5 117.7,88 118.5,85 116,83.2 119,83" fill="#F59E0B"/>
                                            </g>
                                        <?php endif; ?>

                                        <!-- LAYER TOPI -->
                                        <?php if ($topiAktif === 'topi-astronaut'): ?>
                                            <g>
                                                <path d="M55 42 a45 30 0 0 1 90 0 z" fill="#C4B5FD" stroke="#1E1B4B" stroke-width="3" stroke-linejoin="round"/>
                                                <path d="M68 40 a32 20 0 0 1 64 0 z" fill="#7DD3FC" stroke="#1E1B4B" stroke-width="2" stroke-linejoin="round"/>
                                                <rect x="48" y="40" width="104" height="12" rx="6" fill="#F9A8D4" stroke="#1E1B4B" stroke-width="3"/>
                                            </g>
                                        <?php elseif ($topiAktif === 'mahkota-galaksi'): ?>
                                            <g>
                                                <path d="M55 45 l-3-25 15 12 13-20 13 20 15-12-3 25 z" fill="#FCD34D" stroke="#B45309" stroke-width="3" stroke-linejoin="round"/>
                                                <rect x="52" y="42" width="96" height="10" rx="3" fill="#A78BFA" stroke="#1E1B4B" stroke-width="2.5"/>
                                                <circle cx="52" cy="18" r="4" fill="#F472B6"/>
                                                <circle cx="148" cy="18" r="4" fill="#F472B6"/>
                                                <circle cx="100" cy="12" r="4" fill="#22D3EE"/>
                                            </g>
                                        <?php endif; ?>

                                    </svg>
                                </div>

                                <div class="absolute top-2 right-4 text-2xl sparkle select-none">✨</div>
                                <div class="absolute bottom-6 left-2 text-xl sparkle select-none" style="animation-delay: 0.7s;">⭐</div>
                            </div>
                        </div>

                        <!-- INFO USER -->
                        <div class="text-center sm:text-left flex-1">
                            <p class="inline-block bg-white/20 text-xs font-extrabold uppercase tracking-wider px-3 py-1 rounded-full">
                                👑 Kapten Galaksi
                            </p>
                            <h1 class="mt-2 text-3xl sm:text-4xl font-black leading-tight">
                                <?= e($user['nama']) ?>
                            </h1>
                            <p class="mt-1 text-sm sm:text-base font-bold opacity-90">
                                Penjelajah Galaksi Level <?= (int)($user['level']??1) ?>
                            </p>

                            <div class="mt-4 flex flex-wrap justify-center sm:justify-start gap-3">
                                <div class="inline-flex items-center gap-2 bg-amber-400 border-2 border-amber-300 rounded-2xl px-3 py-1.5 shadow">
                                    <span class="text-xl">⭐</span>
                                    <div>
                                        <p class="text-[10px] font-extrabold uppercase text-amber-900 leading-none">Koin</p>
                                        <p class="text-lg font-black text-amber-900 leading-none"><?= $koinSekarang ?></p>
                                    </div>
                                </div>
                                <div class="inline-flex items-center gap-2 bg-white/20 border-2 border-white/30 rounded-2xl px-3 py-1.5 shadow">
                                    <span class="text-xl">🎒</span>
                                    <div>
                                        <p class="text-[10px] font-extrabold uppercase text-white/80 leading-none">Item</p>
                                        <p class="text-lg font-black leading-none"><?= count($koleksi) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- BADGE -->
            <section class="mb-6">
                <h2 class="text-lg sm:text-xl font-black mb-3">🏅 Lencana Pencapaian</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="flex flex-col items-center text-center p-3 rounded-2xl border-2 bg-gradient-to-br from-amber-100 to-amber-200 border-amber-300">
                        <span class="text-4xl mb-1">🎯</span>
                        <p class="text-xs font-black leading-tight">Kuis Pertama</p>
                        <p class="text-[10px] font-bold opacity-70 mt-0.5">Selesaikan 1 kuis</p>
                    </div>
                    <div class="flex flex-col items-center text-center p-3 rounded-2xl border-2 bg-gradient-to-br from-rose-100 to-rose-200 border-rose-300">
                        <span class="text-4xl mb-1">⚡</span>
                        <p class="text-xs font-black leading-tight">3 Days Streak</p>
                        <p class="text-[10px] font-bold opacity-70 mt-0.5">Belajar 3 hari</p>
                    </div>
                    <div class="flex flex-col items-center text-center p-3 rounded-2xl border-2 bg-gradient-to-br from-violet-100 to-violet-200 border-violet-300">
                        <span class="text-4xl mb-1">🌌</span>
                        <p class="text-xs font-black leading-tight">Master Matematika</p>
                        <p class="text-[10px] font-bold opacity-70 mt-0.5">100% di Matematika</p>
                    </div>
                    <div class="flex flex-col items-center text-center p-3 rounded-2xl border-2 <?= count($koleksi) >= 3 ? 'bg-gradient-to-br from-emerald-100 to-emerald-200 border-emerald-300' : 'bg-slate-100 border-slate-200 opacity-50' ?>">
                        <span class="text-4xl mb-1">🏆</span>
                        <p class="text-xs font-black leading-tight">Kolektor Sejati</p>
                        <p class="text-[10px] font-bold opacity-70 mt-0.5">Beli 3 item</p>
                    </div>
                </div>
            </section>

            <!-- KOLEKSI SAYA -->
            <section>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg sm:text-xl font-black">🎒 Koleksi Saya</h2>
                    <a href="shop.php" class="text-xs sm:text-sm font-extrabold text-violet-600 hover:underline">
                        + Beli Item Baru →
                    </a>
                </div>

                <?php if (empty($koleksi)): ?>
                    <div class="bg-white rounded-2xl p-8 text-center border-2 border-dashed border-violet-200">
                        <p class="text-4xl mb-2">🎒</p>
                        <p class="text-sm font-extrabold text-slate-600">Koleksimu masih kosong</p>
                        <p class="text-xs font-bold text-slate-400 mt-1">Beli item di Toko Bintang untuk mengisi koleksimu!</p>
                        <a href="shop.php" class="inline-block mt-4 bg-violet-500 hover:bg-violet-600 text-white font-extrabold text-sm px-6 py-2.5 rounded-full shadow">
                            Ke Toko Bintang 🛍️
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        <?php foreach ($koleksi as $item):
                            $itemId  = $item['id'] ?? '';
                            $itemNama = $item['nama'] ?? 'Item';
                            $slot    = $item['slot'] ?? null;
                            $dipakai = sedangDipakai($itemId, $topiAktif, $kacamataAktif);
                            $bisaDipakai = ($slot === 'topi' || $slot === 'kacamata');

                            // Cari ikon dari config
                            $ikonHtml = '🎁';
                            foreach ($items as $it) {
                                if ($it['id'] === $itemId) { $ikonHtml = $it['ikon']; break; }
                            }
                        ?>
                            <article class="flex flex-col bg-white border-2 <?= $dipakai ? 'border-emerald-400 bg-emerald-50' : 'border-violet-200' ?> rounded-2xl p-3 shadow-sm">

                                <div class="w-16 h-16 mx-auto mb-2 flex items-center justify-center"><?= $ikonHtml ?></div>
                                <h3 class="text-xs font-black leading-tight text-center flex-1"><?= e($itemNama) ?></h3>

                                <?php if ($dipakai): ?>
                                    <form method="POST" class="mt-2">
                                        <input type="hidden" name="aksi" value="lepas">
<input type="hidden" name="csrf_token" value="<?= e(bs_shop_csrf()) ?>">
                                        <input type="hidden" name="item_id" value="<?= e($itemId) ?>">
                                        <input type="hidden" name="slot" value="<?= e($slot) ?>">
                                        <button type="submit"
                                                class="w-full bg-rose-500 hover:bg-rose-600 text-white font-extrabold text-xs py-1.5 rounded-full transition-transform hover:scale-105">
                                            Lepas 🚫
                                        </button>
                                    </form>
                                <?php elseif ($bisaDipakai): ?>
                                    <form method="POST" class="mt-2">
                                        <input type="hidden" name="aksi" value="pakai">
<input type="hidden" name="csrf_token" value="<?= e(bs_shop_csrf()) ?>">
                                        <input type="hidden" name="item_id" value="<?= e($itemId) ?>">
                                        <button type="submit"
                                                class="w-full bg-violet-500 hover:bg-violet-600 text-white font-extrabold text-xs py-1.5 rounded-full transition-transform hover:scale-105">
                                            Gunakan 👕
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="mt-2 w-full text-center text-xs font-bold bg-slate-100 text-slate-500 py-1.5 rounded-full">
                                        Koleksi
                                    </span>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        </main>

        <footer class="bg-white border-t-2 border-slate-100 mt-8">
            <div class="max-w-6xl mx-auto px-4 py-4 text-center text-xs font-bold text-slate-400">
                BRIGHT SPACE EdTech
            </div>
        </footer>
    </div>
</body>
</html>