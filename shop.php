<?php
// ============================================================
// shop.php — Toko Bintang (Session-based, no DB)
// Koin & koleksi disimpan di $_SESSION
// ============================================================
define('BRIGHT_SPACE', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/config/database.php';
require_login();

// Ambil user dari session
$user = current_user();

function e($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }

// Data item statis dari config
require __DIR__ . '/config/planets.php';

// ============================================================
// BS_STAGE6B_SHOP_MYSQL: saldo dan inventaris berasal dari bs_students.
require_once __DIR__.'/config/shop_inventory.php';
$studentId=bs_shop_student_id();
$pesanSukses='';$pesanError='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!bs_shop_valid_csrf($_POST['csrf_token']??null)){
        http_response_code(403);exit('Sesi keamanan tidak valid. Muat ulang toko.');
    }
    try{
        $id=is_string($_POST['item_id']??null)?trim($_POST['item_id']):'';
        bs_shop_buy($studentId,$id);
        $_SESSION['bs_shop_flash']=['ok'=>true,'msg'=>'🎉 Item berhasil dibeli dan disimpan di MySQL!'];
    }catch(DomainException $e){$_SESSION['bs_shop_flash']=['ok'=>false,'msg'=>$e->getMessage()];}
    catch(Throwable $e){error_log('Shop 6B: '.$e->getMessage());$_SESSION['bs_shop_flash']=['ok'=>false,'msg'=>'Pembelian gagal. Cek database dan log Laragon.'];}
    header('Location: shop.php',true,303);exit;
}
$flash=$_SESSION['bs_shop_flash']??null;unset($_SESSION['bs_shop_flash']);
if($flash){if($flash['ok'])$pesanSukses=$flash['msg'];else $pesanError=$flash['msg'];}
try{$state=bs_shop_state($studentId);}catch(Throwable $e){error_log('Shop state 6B: '.$e->getMessage());http_response_code(503);exit('Data toko tidak dapat dibaca. Impor SQL Tahap 6B.');}
$koinSekarang=$state['coins'];$koleksi=$state['collection'];$jumlahKoleksi=count($koleksi);
// Koleksi server tidak boleh diganti oleh localStorage/session demo.
// Helper: cek item sudah dimiliki
function sudahDimiliki($koleksi, $id) {
    foreach ($koleksi as $k) {
        if (($k['id'] ?? '') === $id) return true;
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Bintang - BRIGHT SPACE</title>
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
        @keyframes bounce-in { 0%{transform:scale(0.6);opacity:0;}100%{transform:scale(1);opacity:1;} }
        @keyframes coin-pop { 0%{transform:scale(1);}50%{transform:scale(1.18);}100%{transform:scale(1);} }
        @keyframes slide-down { 0%{opacity:0;transform:translateY(-10px);}100%{opacity:1;transform:translateY(0);} }
        .bounce-in { animation: bounce-in 0.4s ease-out; }
        .coin-pop { animation: coin-pop 0.4s ease-out; }
        .slide-down { animation: slide-down 0.3s ease-out; }
    </style>
</head>
<body class="font-nunito bg-cosmic-bg text-cosmic-text min-h-screen flex flex-col antialiased">

    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <div class="lg:ml-72 min-h-screen flex flex-col">

        <?php include __DIR__ . '/components/header.php'; ?>

        <main class="max-w-6xl mx-auto px-4 py-6 w-full flex-1">

            <!-- TOAST / NOTIFIKASI -->
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

            <!-- HERO TOKO -->
            <section class="mb-6">
                <div class="relative overflow-hidden bg-gradient-to-r from-violet-500 to-indigo-500 rounded-3xl shadow-xl p-6 sm:p-8 text-white">
                    <div class="absolute top-4 right-4 text-6xl opacity-20 select-none">🛍️</div>
                    <div class="absolute bottom-2 right-24 text-3xl opacity-15 select-none">✨</div>

                    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <span class="inline-block bg-white/20 text-xs font-extrabold uppercase tracking-wider px-3 py-1.5 rounded-full">
                                🛍️ Space Shop
                            </span>
                            <h1 class="mt-3 text-2xl sm:text-3xl font-black leading-tight">Toko Bintang</h1>
                            <p class="mt-1 text-sm font-bold opacity-90">
                                Tukar Koin Bintangmu dengan item keren, <?= e($user['nama']) ?>!
                            </p>
                        </div>

                        <div class="shrink-0 inline-flex items-center gap-2 bg-amber-400 border-2 border-amber-300 rounded-2xl pl-2 pr-4 py-2 shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-7 h-7">
                                <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" fill="#FCD34D" stroke="#B45309" stroke-width="1.2"/>
                            </svg>
                            <div>
                                <p class="text-[10px] font-extrabold uppercase text-amber-900 leading-none">Koin Kamu</p>
                                <p class="text-xl font-black text-amber-900 leading-none"><?= $koinSekarang ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- GRID ITEM -->
            <section>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg sm:text-xl font-black">🛒 Item Tersedia</h2>
                    <span class="text-xs font-extrabold bg-violet-100 border-2 border-violet-200 text-violet-700 px-3 py-1 rounded-full">
                        🎒 <?= $jumlahKoleksi ?> dimiliki
                    </span>
                </div>

                <div id="shop-full-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($items as $item): $dimiliki = sudahDimiliki($koleksi, $item['id']); ?>
                        <article class="item-toko flex flex-col bg-white border-2 border-violet-200 rounded-3xl p-5 shadow-sm hover:shadow-lg transition-all">

                            <div class="w-24 h-24 mx-auto mb-3 flex items-center justify-center"><?= $item['ikon'] ?></div>

                            <h3 class="text-base font-black leading-tight text-center"><?= e($item['nama']) ?></h3>
                            <p class="mt-1 text-xs font-bold opacity-70 text-center flex-1"><?= e($item['deskripsi']) ?></p>

                            <div class="mt-3 inline-flex items-center justify-center gap-1 bg-cosmic-accent border-2 border-amber-300 rounded-full px-3 py-1 mx-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4">
                                    <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" fill="#FCD34D" stroke="#B45309" stroke-width="1.2"/>
                                </svg>
                                <span class="text-sm font-extrabold"><?= (int) $item['harga'] ?> Koin</span>
                            </div>

                            <div class="mt-3">
                                <?php if ($dimiliki): ?>
                                    <div class="w-full text-center bg-emerald-100 text-emerald-700 font-extrabold text-sm py-2.5 rounded-full">
                                        Dimiliki ✅
                                    </div>
                                    <a href="profile.php" class="block w-full mt-2 text-center bg-violet-100 hover:bg-violet-200 text-violet-700 font-extrabold text-xs py-2 rounded-full">
                                        Lihat di Profil →
                                    </a>
                                <?php else: ?>
                                    <form method="POST" class="w-full">
                                        <input type="hidden" name="beli_item" value="1">
                                        <input type="hidden" name="csrf_token" value="<?= e(bs_shop_csrf()) ?>">
                                        <input type="hidden" name="item_id" value="<?= e($item['id']) ?>">
                                        <button type="submit"
                                                class="w-full bg-violet-500 hover:bg-violet-600 text-white font-extrabold text-sm py-2.5 rounded-full shadow transition-transform hover:scale-105 active:scale-95">
                                            Beli 🛒
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
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