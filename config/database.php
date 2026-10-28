<?php
defined('BRIGHT_SPACE') or exit('Akses langsung tidak diizinkan.');

// ============================================================
// KONFIGURASI DATABASE (MODE FALLBACK)
// Kalau DB gak ada, app tetap jalan pakai session
// ============================================================

if (getenv('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST'));
    define('DB_NAME', getenv('DB_NAME') ?: 'bright_space');
    define('DB_USER', getenv('DB_USER') ?: 'bright_user');
    define('DB_PASS', getenv('DB_PASS') ?: '');
} else {
    define('DB_HOST', '127.0.0.1');
    define('DB_NAME', 'bright_space');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

define('DB_CHARSET', 'utf8mb4');

// ============================================================
// KONEKSI DB — Return null kalau gagal (biar app tetap jalan)
// ============================================================
function db()
{
    static $pdo = null;
    static $tried = false;

    if ($tried) { return $pdo; }
    $tried = true;

    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // Gagal connect — return null, jangan crash
        $pdo = null;
    }
    return $pdo;
}

// ============================================================
// DB HELPERS — Safe fallback kalau PDO null
// ============================================================
function db_one($sql, $params = [])
{
    $pdo = db();
    if (!$pdo) { return null; }
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function db_all($sql, $params = [])
{
    $pdo = db();
    if (!$pdo) { return []; }
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function db_exec($sql, $params = [])
{
    $pdo = db();
    if (!$pdo) { return false; }
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (Exception $e) {
        return false;
    }
}

function db_insert($sql, $params = [])
{
    db_exec($sql, $params);
    $pdo = db();
    return $pdo ? (int) $pdo->lastInsertId() : 0;
}

// ============================================================
// USER — Ambil dari SESSION saja (fallback kalau DB kosong)
// ============================================================
function current_user()
{
    // BS_STUDENT_AUTH_STAGE5: akun siswa MySQL, bukan user demo fallback.
    if (!empty($_SESSION['bs_student_id'])) {
        require_once __DIR__ . '/student_progress.php';
        $s = bs_verified_student();
        if (!$s) return null;
        return [
            'id' => (int)$s['id'], 'nama' => $s['display_name'],
            'star_coins' => (int)$s['star_coins'], 'level' => (int)$s['level'],
            'total_xp' => (int)$s['total_xp'],
            'avatar_topi' => null, 'avatar_kacamata' => null,
        ];
    }
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    // Coba ambil dari DB dulu
    $user = db_one('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);

    // Kalau DB gak ada / user gak ketemu → pakai session
    if (!$user) {
        return [
            'id'         => $_SESSION['user_id']    ?? 1,
            'nama'       => $_SESSION['username']   ?? 'Kapten',
            'star_coins' => $_SESSION['star_coins'] ?? 120,
            'level'      => $_SESSION['level']      ?? 1,
            'avatar_topi'     => $_SESSION['avatar_topi']     ?? null,
            'avatar_kacamata' => $_SESSION['avatar_kacamata'] ?? null,
        ];
    }

    return $user;
}

// ============================================================
// require_login — Cek session saja
// ============================================================
function require_login()
{
    // Tahap 5: sesi demo lama tidak diizinkan memasuki halaman siswa.
    if (empty($_SESSION['bs_student_id']) || (int)$_SESSION['bs_student_id'] !== (int)($_SESSION['user_id'] ?? 0)) {
        header('Location: login.php'); exit;
    }
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// ============================================================
// get_soal — Fallback ke array dummy kalau DB kosong
// ============================================================
function get_soal($planetId, $modeType, $limit = 15)
{
    $rows = db_all(
        'SELECT * FROM soal_bank WHERE planet_id = ? AND mode_type = ? ORDER BY RAND() LIMIT ' . (int) $limit,
        [$planetId, $modeType]
    );

    // Kalau DB kosong, pakai fallback dummy
    if (empty($rows)) {
        return get_soal_fallback($planetId, $modeType);
    }

    $hasil = [];
    foreach ($rows as $r) {
        $hasil[] = [
            'pertanyaan'  => $r['pertanyaan'],
            'pilihan'     => [
                'A' => $r['pilihan_a'],
                'B' => $r['pilihan_b'],
                'C' => $r['pilihan_c'],
            ],
            'jawaban'     => $r['jawaban'],
            'penjelasan'  => $r['penjelasan'],
            'umpan_salah' => [
                'A' => $r['umpan_salah_a'],
                'B' => $r['umpan_salah_b'],
                'C' => $r['umpan_salah_c'],
            ],
        ];
    }
    return $hasil;
}

// ============================================================
// FALLBACK SOAL — dipakai kalau DB kosong
// ============================================================
function get_soal_fallback($planetId, $modeType)
{
    $bank = [
        'matematika' => [
            [
                'pertanyaan'  => 'Roket Kapten membawa 8 bintang. Kapten menemukan 5 bintang lagi. Berapa jumlah bintang sekarang?',
                'pilihan'     => ['A' => '12 bintang', 'B' => '13 bintang', 'C' => '14 bintang'],
                'jawaban'     => 'B',
                'penjelasan'  => '8 + 5 = 13 bintang.',
                'umpan_salah' => [
                    'A' => 'Coba hitung maju 5 langkah dari angka 8!',
                    'C' => 'Hampir! Coba hitung pelan-pelan 8, 9, 10, 11, 12, 13.',
                ],
            ],
            [
                'pertanyaan'  => 'Arazka punya 15 kelereng. Ia memberi 7 kepada temannya. Berapa sisanya?',
                'pilihan'     => ['A' => '7 kelereng', 'B' => '8 kelereng', 'C' => '9 kelereng'],
                'jawaban'     => 'B',
                'penjelasan'  => '15 - 7 = 8 kelereng.',
                'umpan_salah' => [
                    'A' => '7 itu jumlah yang diberikan, bukan sisanya.',
                    'C' => '9 itu kelebihan satu. Coba hitung mundur dari 15.',
                ],
            ],
            [
                'pertanyaan'  => 'Ada 4 kelompok bintang, masing-masing berisi 5 bintang. Berapa total bintang?',
                'pilihan'     => ['A' => '9 bintang', 'B' => '20 bintang', 'C' => '25 bintang'],
                'jawaban'     => 'B',
                'penjelasan'  => '4 × 5 = 20 bintang.',
                'umpan_salah' => [
                    'A' => '9 itu hasil 4 + 5, bukan 4 × 5.',
                    'C' => '25 itu 5 × 5. Kita butuh 4 kelompok × 5 bintang.',
                ],
            ],
            [
                'pertanyaan'  => 'Setengah dari 18 koin adalah ...',
                'pilihan'     => ['A' => '8 koin', 'B' => '9 koin', 'C' => '10 koin'],
                'jawaban'     => 'B',
                'penjelasan'  => '18 ÷ 2 = 9 koin.',
                'umpan_salah' => [
                    'A' => 'Coba bagi 18 menjadi dua bagian sama besar.',
                    'C' => '10 itu terlalu banyak. Setengah dari 18 pasnya 9.',
                ],
            ],
            [
                'pertanyaan'  => 'Hasil dari 100 - 45 adalah ...',
                'pilihan'     => ['A' => '45', 'B' => '55', 'C' => '65'],
                'jawaban'     => 'B',
                'penjelasan'  => '100 - 45 = 55.',
                'umpan_salah' => [
                    'A' => 'Coba hitung mundur dari 100 sebanyak 45 langkah.',
                    'C' => '65 itu kelebihan. Ingat 100 - 40 = 60, lalu -5 lagi = 55.',
                ],
            ],
        ],
        'sains' => [
            [
                'pertanyaan'  => 'Planet yang dijuluki "Planet Merah" adalah ...',
                'pilihan'     => ['A' => 'Mars', 'B' => 'Venus', 'C' => 'Saturnus'],
                'jawaban'     => 'A',
                'penjelasan'  => 'Mars tampak merah karena tanahnya mengandung besi berkarat.',
                'umpan_salah' => [
                    'B' => 'Venus kuning keputihan karena awannya tebal.',
                    'C' => 'Saturnus terkenal karena cincinnya, bukan warna merah.',
                ],
            ],
            [
                'pertanyaan'  => 'Bagian tumbuhan penyerap air dari tanah adalah ...',
                'pilihan'     => ['A' => 'Daun', 'B' => 'Batang', 'C' => 'Akar'],
                'jawaban'     => 'C',
                'penjelasan'  => 'Akar menyerap air dan zat hara dari tanah.',
                'umpan_salah' => [
                    'A' => 'Daun tempat fotosintesis, bukan penyerap air.',
                    'B' => 'Batang menyalurkan air, tapi bukan penyerapnya.',
                ],
            ],
            [
                'pertanyaan'  => 'Planet terdekat dengan Matahari adalah ...',
                'pilihan'     => ['A' => 'Merkurius', 'B' => 'Bumi', 'C' => 'Mars'],
                'jawaban'     => 'A',
                'penjelasan'  => 'Merkurius adalah planet terdekat dari Matahari.',
                'umpan_salah' => [
                    'B' => 'Bumi adalah planet ketiga dari Matahari.',
                    'C' => 'Mars adalah planet keempat. Cari yang paling dekat.',
                ],
            ],
            [
                'pertanyaan'  => 'Benda langit yang mengelilingi Bumi adalah ...',
                'pilihan'     => ['A' => 'Matahari', 'B' => 'Bulan', 'C' => 'Bintang'],
                'jawaban'     => 'B',
                'penjelasan'  => 'Bulan adalah satelit alami yang mengelilingi Bumi.',
                'umpan_salah' => [
                    'A' => 'Matahari itu bintang, bukan pengeliling Bumi.',
                    'C' => 'Bintang sangat jauh. Yang dekat dengan Bumi itu Bulan.',
                ],
            ],
            [
                'pertanyaan'  => 'Hewan yang bernapas dengan insang adalah ...',
                'pilihan'     => ['A' => 'Kucing', 'B' => 'Ikan', 'C' => 'Burung'],
                'jawaban'     => 'B',
                'penjelasan'  => 'Ikan bernapas dengan insang di dalam air.',
                'umpan_salah' => [
                    'A' => 'Kucing bernapas dengan paru-paru.',
                    'C' => 'Burung juga bernapas dengan paru-paru.',
                ],
            ],
        ],
        'bahasa' => [
            [
                'pertanyaan'  => 'Lawan kata "besar" adalah ...',
                'pilihan'     => ['A' => 'tinggi', 'B' => 'kecil', 'C' => 'panjang'],
                'jawaban'     => 'B',
                'penjelasan'  => 'Antonim "besar" = kecil.',
                'umpan_salah' => [
                    'A' => 'Lawan "tinggi" adalah "rendah".',
                    'C' => 'Lawan "panjang" adalah "pendek".',
                ],
            ],
            [
                'pertanyaan'  => 'Kalimat yang menggunakan tanda baca dengan benar adalah ...',
                'pilihan'     => ['A' => 'Arazka pergi ke bulan.', 'B' => 'arazka pergi ke bulan', 'C' => 'Arazka pergi ke bulan'],
                'jawaban'     => 'A',
                'penjelasan'  => 'Kalimat benar diawali huruf kapital dan diakhiri titik.',
                'umpan_salah' => [
                    'B' => 'Kalimat harus diawali huruf kapital.',
                    'C' => 'Kalimat harus diakhiri tanda baca.',
                ],
            ],
            [
                'pertanyaan'  => 'Kata "membaca" berasal dari kata dasar ...',
                'pilihan'     => ['A' => 'baca', 'B' => 'buku', 'C' => 'tulis'],
                'jawaban'     => 'A',
                'penjelasan'  => 'Kata dasar "membaca" adalah "baca".',
                'umpan_salah' => [
                    'B' => '"Buku" itu benda, bukan kata dasar.',
                    'C' => '"Tulis" itu kata dasar lain.',
                ],
            ],
            [
                'pertanyaan'  => 'Kalimat "Arazka sedang belajar." Subjeknya adalah ...',
                'pilihan'     => ['A' => 'Arazka', 'B' => 'sedang', 'C' => 'belajar'],
                'jawaban'     => 'A',
                'penjelasan'  => 'Subjek = pelaku = Arazka.',
                'umpan_salah' => [
                    'B' => '"Sedang" itu keterangan waktu.',
                    'C' => '"Belajar" itu predikat.',
                ],
            ],
            [
                'pertanyaan'  => 'Kata "cerah" sama artinya dengan ...',
                'pilihan'     => ['A' => 'gelap', 'B' => 'terang', 'C' => 'redup'],
                'jawaban'     => 'B',
                'penjelasan'  => 'Sinonim "cerah" = terang.',
                'umpan_salah' => [
                    'A' => '"Gelap" itu lawan katanya.',
                    'C' => '"Redup" juga lawan dari cerah.',
                ],
            ],
        ],
    ];

    return $bank[$planetId] ?? [];
}