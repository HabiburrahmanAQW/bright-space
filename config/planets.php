<?php
defined('BRIGHT_SPACE') or exit('Akses langsung tidak diizinkan.');

$planets = [
    [
        'id' => 'matematika', 'nama' => 'Planet Matematika', 'deskripsi' => 'Petualangan Angka & Logika',
        'status' => 'Terbuka', 'level' => 0,
        'gradien' => 'from-blue-100 to-indigo-200', 'border' => 'border-indigo-300',
        'tombol' => 'bg-indigo-500 hover:bg-indigo-600 focus-visible:ring-indigo-300',
        'ikon' => '<svg viewBox="0 0 64 64" class="w-full h-full" aria-hidden="true"><ellipse cx="32" cy="34" rx="30" ry="9" fill="none" stroke="#A5B4FC" stroke-width="3" transform="rotate(-18 32 32)"/><circle cx="32" cy="32" r="20" fill="#6366F1"/><circle cx="25" cy="24" r="5" fill="#818CF8"/><g stroke="#FFFFFF" stroke-width="3.5" stroke-linecap="round" fill="none"><path d="M21 28h10M26 23v10"/><path d="M37 41h9"/><path d="M38 27l7 7M45 27l-7 7"/></g><path d="M2 34a30 9 0 0 0 60 0" fill="none" stroke="#A5B4FC" stroke-width="3" transform="rotate(-18 32 32)"/></svg>',
    ],
    [
        'id' => 'sains', 'nama' => 'Planet Sains', 'deskripsi' => 'Eksplorasi Alam & Antariksa',
        'status' => 'Terbuka', 'level' => 0,
        'gradien' => 'from-emerald-100 to-green-200', 'border' => 'border-emerald-300',
        'tombol' => 'bg-emerald-500 hover:bg-emerald-600 focus-visible:ring-emerald-300',
        'ikon' => '<svg viewBox="0 0 64 64" class="w-full h-full" aria-hidden="true"><ellipse cx="32" cy="34" rx="30" ry="9" fill="none" stroke="#6EE7B7" stroke-width="3" transform="rotate(-18 32 32)"/><circle cx="32" cy="32" r="20" fill="#10B981"/><circle cx="25" cy="24" r="5" fill="#34D399"/><g fill="none" stroke="#FFFFFF" stroke-width="2"><ellipse cx="32" cy="32" rx="14" ry="5.5"/><ellipse cx="32" cy="32" rx="14" ry="5.5" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="14" ry="5.5" transform="rotate(120 32 32)"/></g><circle cx="32" cy="32" r="3" fill="#FFFFFF"/><path d="M2 34a30 9 0 0 0 60 0" fill="none" stroke="#6EE7B7" stroke-width="3" transform="rotate(-18 32 32)"/></svg>',
    ],
    [
        'id' => 'bahasa', 'nama' => 'Planet Bahasa', 'deskripsi' => 'Dunia Kata & Cerita Seru',
        'status' => 'Terbuka', 'level' => 0,
        'gradien' => 'from-amber-100 to-yellow-200', 'border' => 'border-amber-300',
        'tombol' => 'bg-amber-500 hover:bg-amber-600 focus-visible:ring-amber-300',
        'ikon' => '<svg viewBox="0 0 64 64" class="w-full h-full" aria-hidden="true"><ellipse cx="32" cy="34" rx="30" ry="9" fill="none" stroke="#FCD34D" stroke-width="3" transform="rotate(-18 32 32)"/><circle cx="32" cy="32" r="20" fill="#F59E0B"/><circle cx="25" cy="24" r="5" fill="#FBBF24"/><path d="M32 25c-4-3-9-3-14-2v18c5-1 10-1 14 2z" fill="#FFFFFF"/><path d="M32 25c4-3 9-3 14-2v18c-5-1-10-1-14 2z" fill="#FEF3C7"/><path d="M32 25v18" stroke="#B45309" stroke-width="2" stroke-linecap="round"/><path d="M2 34a30 9 0 0 0 60 0" fill="none" stroke="#FCD34D" stroke-width="3" transform="rotate(-18 32 32)"/></svg>',
    ],
    [
        'id' => 'seni-coding', 'nama' => 'Planet Seni & Coding', 'deskripsi' => 'Kreasi Gambar & Algoritma',
        'status' => 'Terkunci', 'level' => 5,
        'gradien' => 'from-purple-100 to-pink-200', 'border' => 'border-pink-300',
        'tombol' => 'bg-purple-500 hover:bg-purple-600 focus-visible:ring-purple-300',
        'ikon' => '<svg viewBox="0 0 64 64" class="w-full h-full" aria-hidden="true"><ellipse cx="32" cy="34" rx="30" ry="9" fill="none" stroke="#F9A8D4" stroke-width="3" transform="rotate(-18 32 32)"/><circle cx="32" cy="32" r="20" fill="#A855F7"/><circle cx="25" cy="24" r="5" fill="#C084FC"/><g fill="none" stroke="#FFFFFF" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="26,25 18,32 26,39"/><polyline points="38,25 46,32 38,39"/><line x1="34" y1="22" x2="30" y2="42"/></g><circle cx="46" cy="19" r="3" fill="#F9A8D4"/><path d="M2 34a30 9 0 0 0 60 0" fill="none" stroke="#F9A8D4" stroke-width="3" transform="rotate(-18 32 32)"/></svg>',
    ],
];

$gameModes = [
    'kuis' => [
        'id' => 'kuis', 'type' => 'kuis', 'kategori' => 'wajib',
        'judul' => 'Kuis Kilat Kosmik', 'deskripsi' => 'Jawab 10 soal dengan cepat!',
        'ikon' => '⚡', 'reward' => 50, 'timer_detik' => 300, 'jumlah_soal' => 10,
        'warna' => ['bg' => 'from-indigo-100 to-blue-200', 'border' => 'border-indigo-300', 'tombol' => 'bg-indigo-500 hover:bg-indigo-600'],
    ],
    'story' => [
        'id' => 'story', 'type' => 'story', 'kategori' => 'wajib',
        'judul' => 'Misi Cerita Arazka', 'deskripsi' => 'Bantu Arazka memutuskan!',
        'ikon' => '📖', 'reward' => 75, 'timer_detik' => 600, 'jumlah_soal' => 10,
        'warna' => ['bg' => 'from-purple-100 to-pink-200', 'border' => 'border-purple-300', 'tombol' => 'bg-purple-500 hover:bg-purple-600'],
    ],
    'puzzle' => [
        'id' => 'puzzle', 'type' => 'puzzle', 'kategori' => 'side',
        'judul' => 'Teka-Teki Kata Angkasa', 'deskripsi' => 'Susun huruf jadi kosakata.',
        'ikon' => '🔤', 'reward' => 40, 'timer_detik' => 300, 'jumlah_soal' => 10,
        'warna' => ['bg' => 'from-emerald-100 to-teal-200', 'border' => 'border-emerald-300', 'tombol' => 'bg-emerald-500 hover:bg-emerald-600'],
    ],
    'matching' => [
        'id' => 'matching', 'type' => 'matching', 'kategori' => 'side',
        'judul' => 'Cocokkan Rumus & Simbol', 'deskripsi' => 'Hubungkan simbol dengan jawaban.',
        'ikon' => '🧩', 'reward' => 60, 'timer_detik' => 300, 'jumlah_soal' => 10,
        'warna' => ['bg' => 'from-amber-100 to-orange-200', 'border' => 'border-amber-300', 'tombol' => 'bg-amber-500 hover:bg-amber-600'],
    ],
    'latihan' => [
        'id' => 'latihan', 'type' => 'latihan', 'kategori' => 'side',
        'judul' => 'Latihan Bebas', 'deskripsi' => 'Mode santai tanpa timer, tanpa koin.',
        'ikon' => '🎓', 'reward' => 0, 'timer_detik' => 0, 'jumlah_soal' => 10,
        'warna' => ['bg' => 'from-slate-100 to-violet-200', 'border' => 'border-slate-300', 'tombol' => 'bg-violet-500 hover:bg-violet-600'],
    ],
];

$items = [
    ['id' => 'topi-astronaut', 'nama' => 'Topi Astronaut', 'deskripsi' => 'Topi lucu Kapten cilik!', 'harga' => 50, 'slot' => 'topi', 'ikon' => '<svg viewBox="0 0 64 64" class="w-full h-full"><circle cx="32" cy="32" r="30" fill="#EDE9FE"/><line x1="32" y1="20" x2="32" y2="14" stroke="#1E1B4B" stroke-width="2.5" stroke-linecap="round"/><circle cx="32" cy="12" r="3.5" fill="#FCD34D" stroke="#B45309" stroke-width="1.5"/><path d="M12 40a20 20 0 0 1 40 0z" fill="#C4B5FD" stroke="#1E1B4B" stroke-width="2.5" stroke-linejoin="round"/><path d="M18 38a14 14 0 0 1 28 0z" fill="#7DD3FC" stroke="#1E1B4B" stroke-width="2" stroke-linejoin="round"/><rect x="9" y="38" width="46" height="8" rx="4" fill="#F9A8D4" stroke="#1E1B4B" stroke-width="2.5"/></svg>'],
    ['id' => 'kacamata-bintang', 'nama' => 'Kacamata Bintang', 'deskripsi' => 'Bintang berkilau di mata!', 'harga' => 40, 'slot' => 'kacamata', 'ikon' => '<svg viewBox="0 0 64 64" class="w-full h-full"><circle cx="32" cy="32" r="30" fill="#E0F2FE"/><rect x="10" y="22" width="19" height="16" rx="6" fill="#FDE68A" stroke="#1E1B4B" stroke-width="2.5"/><rect x="35" y="22" width="19" height="16" rx="6" fill="#FDE68A" stroke="#1E1B4B" stroke-width="2.5"/><path d="M29 30h6" stroke="#1E1B4B" stroke-width="2.5" stroke-linecap="round"/><polygon points="19.5,26 20.8,29 24,29.2 21.5,31.2 22.4,34.4 19.5,32.6 16.6,34.4 17.5,31.2 15,29.2 18.2,29" fill="#F59E0B"/><polygon points="44.5,26 45.8,29 49,29.2 46.5,31.2 47.4,34.4 44.5,32.6 41.6,34.4 42.5,31.2 40,29.2 43.2,29" fill="#F59E0B"/></svg>'],
    ['id' => 'mahkota-galaksi', 'nama' => 'Mahkota Galaksi', 'deskripsi' => 'Untuk Kapten hebat!', 'harga' => 150, 'slot' => 'topi', 'ikon' => '<svg viewBox="0 0 64 64" class="w-full h-full"><circle cx="32" cy="32" r="30" fill="#FEF3C7"/><path d="M12 44l-3-22 12 10 11-16 11 16 12-10-3 22z" fill="#FCD34D" stroke="#B45309" stroke-width="2.5" stroke-linejoin="round"/><rect x="12" y="44" width="40" height="7" rx="2" fill="#A78BFA" stroke="#1E1B4B" stroke-width="2"/><circle cx="9" cy="21" r="3" fill="#F472B6"/><circle cx="55" cy="21" r="3" fill="#F472B6"/><circle cx="32" cy="15" r="3" fill="#22D3EE"/></svg>'],
    ['id' => 'roket-neon', 'nama' => 'Roket Neon', 'deskripsi' => 'Roket bercahaya cepat!', 'harga' => 80, 'slot' => null, 'ikon' => '<svg viewBox="0 0 64 64" class="w-full h-full"><circle cx="32" cy="32" r="30" fill="#312E81"/><path d="M32 10c7 5 10 13 10 21 0 5-1 9-2.5 13h-15C23 40 22 36 22 31c0-8 3-16 10-21z" fill="#22D3EE" stroke="#A5F3FC" stroke-width="2"/><circle cx="32" cy="27" r="5" fill="#1E1B4B"/></svg>'],
    ['id' => 'badge-emas', 'nama' => 'Badge Emas', 'deskripsi' => 'Badge koin jadi emas!', 'harga' => 100, 'slot' => null, 'ikon' => '<svg viewBox="0 0 64 64" class="w-full h-full"><circle cx="32" cy="32" r="30" fill="#FEF3C7"/><circle cx="32" cy="39" r="16" fill="#FBBF24" stroke="#B45309" stroke-width="2.5"/><polygon points="32,31 34.4,36.4 40.2,37 35.8,40.8 37.1,46.5 32,43.5 26.9,46.5 28.2,40.8 23.8,37 29.6,36.4" fill="#FFFFFF" stroke="#B45309" stroke-width="1"/></svg>'],
];

$kategoriBadge = [
    'wajib' => ['teks' => '⭐ Misi Wajib', 'kelas' => 'bg-amber-300 text-amber-900'],
    'side'  => ['teks' => '💎 Side Quest', 'kelas' => 'bg-sky-300 text-sky-900'],
];

function cariPlanet($planets, $id)
{
    foreach ($planets as $p) {
        if ($p['id'] === $id) { return $p; }
    }
    return null;
}