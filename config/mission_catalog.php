<?php
/**
 * BRIGHT SPACE - katalog pilihan editor tutor.
 * Nilai key harus tetap sesuai ENUM pada sql/01_fondasi_tutor.sql (Tahap 1).
 */
defined('BRIGHT_SPACE') or exit('Akses langsung tidak diizinkan.');

function mission_editor_themes(): array
{
    return [
        'indigo'  => ['name' => 'Nebula Biru',  'dot' => '#6366f1'],
        'emerald' => ['name' => 'Galaksi Hijau','dot' => '#10b981'],
        'amber'   => ['name' => 'Bintang Emas', 'dot' => '#f59e0b'],
        'violet'  => ['name' => 'Kosmik Ungu',  'dot' => '#a855f7'],
        'rose'    => ['name' => 'Aurora Merah', 'dot' => '#f43f5e'],
        'sky'     => ['name' => 'Langit Cerah', 'dot' => '#0ea5e9'],
    ];
}

function mission_editor_modes(): array
{
    return [
        'kuis'     => ['name' => 'Kuis Kilat Kosmik',    'emoji' => '⚡', 'description' => 'Jawab pertanyaan pilihan ganda.', 'reward' => 50, 'timer' => 300, 'questions' => 10],
        'story'    => ['name' => 'Misi Cerita Arazka',  'emoji' => '📖','description' => 'Belajar melalui cerita interaktif.', 'reward' => 75, 'timer' => 600, 'questions' => 10],
        'puzzle'   => ['name' => 'Teka-Teki Kata',      'emoji' => '🔤','description' => 'Susun huruf dan temukan jawaban.', 'reward' => 40, 'timer' => 300, 'questions' => 10],
        'matching' => ['name' => 'Cocokkan Pasangan',  'emoji' => '🧩','description' => 'Hubungkan dua hal yang cocok.', 'reward' => 60, 'timer' => 300, 'questions' => 10],
        'latihan'  => ['name' => 'Latihan Bebas',      'emoji' => '🎓','description' => 'Tanpa timer dan tanpa koin.', 'reward' => 0, 'timer' => 0, 'questions' => 10],
    ];
}

/** Hanya SVG dari daftar yang ditetapkan pengembang (bukan HTML dari form). */
function mission_editor_icons(): array
{
    return [
        'matematika' => '<svg viewBox="0 0 64 64" aria-hidden="true"><ellipse cx="32" cy="34" rx="29" ry="10" stroke="#a5b4fc" stroke-width="3" fill="none" transform="rotate(-18 32 32)"/><circle cx="32" cy="32" r="20" fill="#6366f1"/><path d="M19 28h13m-6.5-6.5v13M37 39h11m-9-12 9 9m0-9-9 9" stroke="white" stroke-width="3.5" stroke-linecap="round"/><path d="M3 34a29 10 0 0 0 58 0" stroke="#a5b4fc" stroke-width="3" fill="none" transform="rotate(-18 32 32)"/></svg>',
        'sains' => '<svg viewBox="0 0 64 64" aria-hidden="true"><ellipse cx="32" cy="34" rx="29" ry="10" stroke="#6ee7b7" stroke-width="3" fill="none" transform="rotate(-18 32 32)"/><circle cx="32" cy="32" r="20" fill="#10b981"/><g stroke="white" fill="none" stroke-width="2"><ellipse cx="32" cy="32" rx="13" ry="5"/><ellipse cx="32" cy="32" rx="13" ry="5" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="13" ry="5" transform="rotate(120 32 32)"/></g><circle cx="32" cy="32" r="3" fill="white"/></svg>',
        'bahasa' => '<svg viewBox="0 0 64 64" aria-hidden="true"><ellipse cx="32" cy="34" rx="29" ry="10" stroke="#fcd34d" stroke-width="3" fill="none" transform="rotate(-18 32 32)"/><circle cx="32" cy="32" r="20" fill="#f59e0b"/><path d="M32 25c-4-3-9-3-14-2v18c5-1 10-1 14 2z" fill="white"/><path d="M32 25c4-3 9-3 14-2v18c-5-1-10-1-14 2z" fill="#fef3c7"/><path d="M32 25v18" stroke="#b45309" stroke-width="2"/></svg>',
        'seni-coding' => '<svg viewBox="0 0 64 64" aria-hidden="true"><ellipse cx="32" cy="34" rx="29" ry="10" stroke="#f9a8d4" stroke-width="3" fill="none" transform="rotate(-18 32 32)"/><circle cx="32" cy="32" r="20" fill="#a855f7"/><path d="m26 25-8 7 8 7m12-14 8 7-8 7m-4-17-4 20" fill="none" stroke="white" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'umum' => '<svg viewBox="0 0 64 64" aria-hidden="true"><ellipse cx="32" cy="34" rx="29" ry="10" stroke="#7dd3fc" stroke-width="3" fill="none" transform="rotate(-18 32 32)"/><circle cx="32" cy="32" r="20" fill="#0ea5e9"/><path d="m32 17 4.2 10.8L48 29l-9 7.3 3 11.5L32 41.5l-10 6.3 3-11.5-9-7.3 11.8-1.2z" fill="#fef3c7" stroke="white" stroke-width="1.5"/></svg>',
    ];
}
