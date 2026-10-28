/* ============================================================
 * BRIGHT SPACE - assets/js/app.js
 * ============================================================ */
(function () {
    'use strict';

    var BS = window.BRIGHT_SPACE || {};
    var USER = BS.user || { nama: 'Kapten', star_coins: 120, topi: null, kacamata: null };
    var PLANET = BS.planet || null;
    var GAME_MODES = BS.gameModes || {};
    var SOAL_PER_MODE = BS.soalPerMode || {};
    var ITEM_TOKO = window.BRIGHT_SPACE_ITEMS || [];

    var IKON_BINTANG_KECIL = '<svg viewBox="0 0 24 24" class="w-full h-full"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" fill="#FCD34D" stroke="#B45309" stroke-width="1.5"/></svg>';
    var PEMBUKA_BENAR = ['Benar! ✅', 'Tepat sekali! 🌟', 'Keren! ⭐', 'Hebat! 🎉'];
    var PEMBUKA_SALAH = ['Hampir tepat! 💪', 'Ayo coba lagi! 🌈', 'Semangat! 🚀'];
    var TIPS = [
        'Klik kartu planet untuk mulai petualangan! 🚀',
        'Misi wajib memberi koin besar, Side Quest untuk seru-seruan! 🎯',
        'Jawab semua benar = Bonus Sempurna +20%! 🏆',
        'Timer habis = sesi selesai! ⏱️',
        'Tukar koinmu di Toko Bintang! 🛍️'
    ];

    // ============ STATE ============
    var koinTotal = parseInt(USER.star_coins, 10) || 0;
    var koinTampil = koinTotal;
    var timerKoin = null;
    var timerToast = null;
    var timerWidget = null;
    var timerSesi = null;
    var sisaWaktu = 0;
    var indeksTips = 0;
    var dimiliki = {};
    var modalAktif = null;
    var fokusSebelum = null;
    var sudahBangunToko = false;
    var sesi = null;

    var coinEl, coinBadge, toastEl;
    var modal, tahapSoal, tahapHasil, ikonEl, judulEl, timerBox, timerEl,
        progTeksEl, progBenarEl, progBarEl, tanyaEl, pilihanEl, lanjutBtn, tutupBtn;
    var hasilBenarEl, hasilTotalEl, hasilAkurasiEl, hasilBarEl, hasilRewardEl,
        hasilBonusEl, hasilKomentar, hasilSelesai, hasilUlang, hasilPercik, araHasil;
    var tokoModal, tokoGrid, tokoSaldo, tokoTutup, tokoBtn;
    var wBtn, wBubble, wTeks;
    var araKuis, araToko;

    function setMood(el, mood) {
        if (!el) return;
        el.removeAttribute('data-mood');
        void el.offsetWidth;
        if (mood) el.setAttribute('data-mood', mood);
    }

    // ============ KOIN ============
    function tampilSaldoToko() { if (tokoSaldo) tokoSaldo.textContent = koinTotal; }
    function denyutKoin() { if (!coinBadge) return; coinBadge.classList.remove('coin-pop'); void coinBadge.offsetWidth; coinBadge.classList.add('coin-pop'); }
    function animasiKoin() {
        if (!coinEl) return;
        clearInterval(timerKoin);
        if (koinTampil === koinTotal) return;
        var langkah = Math.max(1, Math.ceil(Math.abs(koinTotal - koinTampil) / 15));
        timerKoin = setInterval(function () {
            var sisa = koinTotal - koinTampil;
            koinTampil += Math.sign(sisa) * Math.min(Math.abs(sisa), langkah);
            coinEl.textContent = koinTampil;
            if (koinTampil === koinTotal) { clearInterval(timerKoin); denyutKoin(); }
        }, 40);
    }
    function ubahKoin(selisih) {
        selisih = parseInt(selisih, 10) || 0;
        if (!selisih) return;
        koinTotal = Math.max(0, koinTotal + selisih);
        tampilSaldoToko();
        var sk = document.getElementById('sidebar-koin');
        if (sk) sk.textContent = koinTotal;
        animasiKoin();
    }
    window.showToast = function (pesan, durasi) {
        if (!toastEl) return;
        toastEl.textContent = pesan;
        toastEl.classList.remove('hidden');
        toastEl.style.display = '';
        clearTimeout(timerToast);
        timerToast = setTimeout(function () {
            toastEl.classList.add('hidden');
            toastEl.style.display = 'none';
        }, durasi || 2400);
    };

    // ============ MODAL ============
    function tampilkanModal(root) {
        if (!root) return;
        modalAktif = root;
        root.classList.remove('hidden');
        root.style.display = '';
        document.body.classList.add('overflow-hidden');
    }
    function sembunyikanModal(root) {
        if (!root) return;
        root.classList.add('hidden');
        root.style.display = 'none';
        if (modalAktif === root) modalAktif = null;
        document.body.classList.remove('overflow-hidden');
    }

    // ============ ARAZKA WIDGET ============
    function arazkaBicara(teks, mood, durasi) {
        if (!wBubble) return;
        wTeks.textContent = teks;
        wBubble.classList.remove('hidden');
        wBubble.style.display = '';
        setMood(wBtn, mood || 'halo');
        clearTimeout(timerWidget);
        timerWidget = setTimeout(function () {
            wBubble.classList.add('hidden');
            wBubble.style.display = 'none';
        }, durasi || 7000);
    }
    function percikBintang(wadah) {
        if (!wadah) return;
        var arah = [[-40,-30],[40,-30],[-46,2],[46,2],[-30,34],[30,34],[0,-44],[0,42]];
        wadah.innerHTML = '';
        arah.forEach(function (a) {
            var s = document.createElement('span');
            s.className = 'percik';
            s.style.setProperty('--dx', a[0] + 'px');
            s.style.setProperty('--dy', a[1] + 'px');
            s.innerHTML = IKON_BINTANG_KECIL;
            wadah.appendChild(s);
        });
    }
    function bicara(ara, mood, tema, judul, teks, meriah) {
        if (!ara || !ara.bubble) return;
        var warna = {
            netral: 'bg-violet-50 border-violet-200 text-cosmic-text',
            sukses: 'bg-emerald-50 border-emerald-300 text-emerald-900',
            semangat: 'bg-amber-50 border-amber-300 text-amber-900'
        };
        ara.bubble.className = 'relative flex-1 rounded-2xl border-2 p-3 ' + warna[tema];
        if (ara.judul) ara.judul.textContent = judul;
        if (ara.teks) ara.teks.textContent = teks;
        setMood(ara.avatar, mood);
        if (ara.percik) {
            ara.percik.innerHTML = '';
            if (meriah) percikBintang(ara.percik);
        }
    }

    // ============ CHAT ARAZKA ============
    function initArazkaChat() {
        var chatBox = document.getElementById('arazka-chat');
        if (!chatBox) return;

        var teksEl    = document.getElementById('chat-teks');
        var subEl     = document.getElementById('chat-subteks');
        var pilihanEl = document.getElementById('chat-pilihan');
        var lanjutBtn = document.getElementById('chat-lanjut');
        var percikEl  = document.getElementById('chat-percik');

        if (!teksEl || !pilihanEl || !lanjutBtn) return;

        var langkah = 0;
        var data = { umur: null, pelajaran: null };

        var skenario = [
            {
                teks: 'Halo, Kapten ' + USER.nama + '! 👋',
                sub: 'Aku Arazka, mentor AI-mu di BRIGHT SPACE. Senang kenalan!',
                pilihan: null,
                lanjut: 'Lanjut 👉'
            },
            {
                teks: 'Berapa umurmu sekarang?',
                sub: 'Pilih salah satu ya!',
                pilihan: [
                    { label: '🐣 6-7 tahun', value: 6 },
                    { label: '🌟 8-9 tahun', value: 8 },
                    { label: '🚀 10-11 tahun', value: 10 }
                ],
                field: 'umur'
            },
            {
                teks: 'Pelajaran apa favoritmu?',
                sub: 'Pilih yang paling kamu suka!',
                pilihan: [
                    { label: '🔢 Matematika', value: 'matematika' },
                    { label: '🔬 Sains', value: 'sains' },
                    { label: '📚 Bahasa', value: 'bahasa' }
                ],
                field: 'pelajaran'
            },
            {
                teks: 'Siap petualangan! 🚀',
                sub: 'Aku sudah siapkan petualanganmu. Yuk pilih planetmu!',
                pilihan: null,
                lanjut: '🚀 Mulai Petualangan!',
                selesai: true
            }
        ];

        function renderLangkah() {
            var s = skenario[langkah];
            if (!s) return;

            teksEl.textContent = s.teks;
            subEl.textContent = s.sub || '';

            if (percikEl && langkah > 0) percikBintang(percikEl);

            pilihanEl.innerHTML = '';
            lanjutBtn.classList.add('hidden');
            lanjutBtn.style.display = 'none';

            if (s.pilihan) {
                s.pilihan.forEach(function (opt) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'chat-opsi w-full sm:w-auto bg-white hover:bg-cosmic-accent border-2 border-violet-300 hover:border-amber-400 rounded-2xl px-4 py-3 font-extrabold text-sm sm:text-base transition-all hover:scale-105 active:scale-95 shadow-sm';
                    btn.textContent = opt.label;
                    btn.addEventListener('click', function () {
                        data[s.field] = opt.value;
                        var payload = {};
                        payload[s.field === 'umur' ? 'umur' : 'pelajaran'] = opt.value;
                        fetch('api/simpan_profil.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        }).catch(function () {});
                        langkah++;
                        renderLangkah();
                    });
                    pilihanEl.appendChild(btn);
                });
            } else if (s.lanjut) {
                lanjutBtn.textContent = s.lanjut;
                lanjutBtn.classList.remove('hidden');
                lanjutBtn.removeAttribute('hidden');
                lanjutBtn.style.display = 'block';
                lanjutBtn.onclick = function () {
                    if (s.selesai) {
                        chatBox.style.transition = 'opacity 0.5s, transform 0.5s';
                        chatBox.style.opacity = '0';
                        chatBox.style.transform = 'translateY(-20px)';
                        setTimeout(function () {
                            chatBox.classList.add('hidden');
                            chatBox.style.display = 'none';
                            var grid = document.getElementById('judul-planet-section');
                            if (grid) grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            arazkaBicara('Ayo pilih planetmu, Kapten! 🚀', 'selebrasi', 6000);
                        }, 500);
                    } else {
                        langkah++;
                        renderLangkah();
                    }
                };
            }
        }

        renderLangkah();
    }

    // ============ TIMER ============
    function formatWaktu(detik) {
        var m = Math.floor(detik / 60);
        var s = detik % 60;
        return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    }
    function mulaiTimer(detik) {
        clearInterval(timerSesi);
        sisaWaktu = detik;
        if (detik <= 0) { timerBox.classList.add('hidden'); return; }
        timerBox.classList.remove('hidden');
        timerEl.textContent = formatWaktu(sisaWaktu);
        timerEl.classList.remove('timer-warning');
        timerSesi = setInterval(function () {
            sisaWaktu--;
            timerEl.textContent = formatWaktu(Math.max(0, sisaWaktu));
            if (sisaWaktu <= 30) timerEl.classList.add('timer-warning');
            if (sisaWaktu <= 0) {
                clearInterval(timerSesi);
                window.showToast('⏰ Waktu habis!', 3000);
                tampilkanHasil(true);
            }
        }, 1000);
    }
    function stopTimer() { clearInterval(timerSesi); }

    // ============ SESI KUIS ============
    function mulaiSesi(modeData) {
        var soalList = SOAL_PER_MODE[modeData.id] || [];
        if (soalList.length === 0) {
            window.showToast('Soal sedang disiapkan ya! 🚀', 3000);
            return;
        }
        var pool = soalList.slice().sort(function () { return Math.random() - 0.5; });
        var jumlah = Math.min(modeData.jumlah_soal || 10, pool.length);

        sesi = {
            mode: modeData,
            soalList: pool.slice(0, jumlah),
            indeks: 0,
            benar: 0,
            salah: 0,
            sudahDijawab: false,
            totalSoal: jumlah,
            selesai: false
        };

        ikonEl.innerHTML = PLANET.ikon;
        judulEl.textContent = modeData.judul;
        tahapSoal.classList.remove('hidden');
        tahapHasil.classList.add('hidden');
        tutupBtn.style.display = '';
        renderSoal();
        tampilkanModal(modal);
        if (modeData.timer_detik > 0) mulaiTimer(modeData.timer_detik);
        else timerBox.classList.add('hidden');
    }

    function renderSoal() {
        if (!sesi) return;
        var soal = sesi.soalList[sesi.indeks];
        if (!soal) { tampilkanHasil(); return; }
        sesi.sudahDijawab = false;

        progTeksEl.textContent = 'Soal ' + (sesi.indeks + 1) + ' dari ' + sesi.totalSoal;
        progBenarEl.textContent = '✅ ' + sesi.benar;
        progBarEl.style.width = ((sesi.indeks) / sesi.totalSoal * 100) + '%';
        tanyaEl.textContent = soal.pertanyaan;
        pilihanEl.innerHTML = '';

        Object.keys(soal.pilihan).forEach(function (huruf) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('data-huruf', huruf);
            btn.className = 'opsi-jawaban w-full flex items-center gap-3 text-left bg-white border-2 border-violet-200 rounded-2xl px-3 py-3 font-bold text-base hover:bg-cosmic-accent hover:border-amber-300';
            var bulat = document.createElement('span');
            bulat.className = 'shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-violet-500 text-white font-black';
            bulat.textContent = huruf;
            var t = document.createElement('span');
            t.textContent = soal.pilihan[huruf];
            btn.appendChild(bulat);
            btn.appendChild(t);
            pilihanEl.appendChild(btn);
        });

        lanjutBtn.classList.add('hidden');
        lanjutBtn.textContent = (sesi.indeks === sesi.totalSoal - 1) ? 'Lihat Hasil 🏁' : 'Soal Berikutnya →';

        var sapaan = sesi.indeks === 0 ? 'Sesi dimulai! Jawab dengan teliti ya! 🚀' : 'Soal ' + (sesi.indeks + 1) + ' 📝';
        bicara(araKuis, 'halo', 'netral', sapaan, 'Baca pelan-pelan dan pilih yang paling tepat!', false);
    }

    function pilihJawaban(btn) {
        if (!sesi || sesi.sudahDijawab) return;
        var soal = sesi.soalList[sesi.indeks];
        var huruf = btn.getAttribute('data-huruf');
        var semua = pilihanEl.querySelectorAll('.opsi-jawaban');
        sesi.sudahDijawab = true;

        if (huruf === soal.jawaban) {
            sesi.benar++;
            Array.prototype.forEach.call(semua, function (o) {
                o.disabled = true;
                if (o === btn) {
                    o.className = 'opsi-jawaban w-full flex items-center gap-3 text-left bg-emerald-100 border-2 border-emerald-400 rounded-2xl px-3 py-3 font-bold text-base';
                } else {
                    o.className = 'opsi-jawaban w-full flex items-center gap-3 text-left bg-white/60 border-2 border-violet-100 rounded-2xl px-3 py-3 font-bold text-base opacity-60';
                }
            });
            bicara(araKuis, 'selebrasi', 'sukses', PEMBUKA_BENAR[Math.floor(Math.random() * PEMBUKA_BENAR.length)], soal.penjelasan, true);
            progBenarEl.textContent = '✅ ' + sesi.benar;
        } else {
            sesi.salah++;
            Array.prototype.forEach.call(semua, function (o) {
                o.disabled = true;
                if (o.getAttribute('data-huruf') === soal.jawaban) {
                    o.className = 'opsi-jawaban w-full flex items-center gap-3 text-left bg-emerald-50 border-2 border-emerald-400 rounded-2xl px-3 py-3 font-bold text-base';
                } else if (o === btn) {
                    o.className = 'opsi-jawaban w-full flex items-center gap-3 text-left bg-rose-50 border-2 border-rose-300 rounded-2xl px-3 py-3 font-bold text-base opacity-70';
                } else {
                    o.className = 'opsi-jawaban w-full flex items-center gap-3 text-left bg-white/60 border-2 border-violet-100 rounded-2xl px-3 py-3 font-bold text-base opacity-60';
                }
            });
            var petunjuk = (soal.umpan_salah && soal.umpan_salah[huruf]) || 'Coba baca lagi ya.';
            bicara(araKuis, 'semangat', 'semangat', PEMBUKA_SALAH[Math.floor(Math.random() * PEMBUKA_SALAH.length)], petunjuk, false);
        }
        lanjutBtn.classList.remove('hidden');
    }

    function lanjutSoal() {
        if (!sesi || !sesi.sudahDijawab) return;
        sesi.indeks++;
        if (sesi.indeks >= sesi.totalSoal) tampilkanHasil();
        else renderSoal();
    }

    function tampilkanHasil(karenaHabisWaktu) {
        if (!sesi) return;
        sesi.selesai = true;
        stopTimer();

        var benar = sesi.benar;
        var total = sesi.totalSoal;
        var akurasi = Math.round(benar / total * 100);
        var reward = 0;
        var bonus = false;

        if (sesi.mode.reward > 0) {
            reward = Math.round(sesi.mode.reward * akurasi / 100);
            if (benar === total) { bonus = true; reward = Math.round(reward * 1.2); }
            ubahKoin(reward);
        }

        hasilBenarEl.textContent = benar;
        hasilTotalEl.textContent = total;
        hasilAkurasiEl.textContent = 'Akurasi: ' + akurasi + '%';
        hasilBarEl.style.width = '0%';
        setTimeout(function () { hasilBarEl.style.width = akurasi + '%'; }, 100);

        if (sesi.mode.reward > 0) {
            document.getElementById('hasil-reward-box').classList.remove('hidden');
            hasilRewardEl.textContent = reward;
            hasilBonusEl.classList.toggle('hidden', !bonus);
        } else {
            document.getElementById('hasil-reward-box').classList.add('hidden');
            hasilBonusEl.classList.add('hidden');
        }

        var komentar = '';
        if (karenaHabisWaktu) komentar = 'Waktu habis! Tapi kamu sudah berusaha, Kapten! ⏰';
        else if (akurasi === 100) komentar = 'SEMPURNA, Kapten! 🏆 Semua jawaban benar!';
        else if (akurasi >= 80) komentar = 'Hebat! ' + benar + '/' + total + ' benar. Dikit lagi sempurna! 🌟';
        else if (akurasi >= 60) komentar = 'Bagus! ' + benar + '/' + total + ' benar. Terus semangat! 💪';
        else if (akurasi >= 40) komentar = 'Kerja bagus! ' + benar + '/' + total + ' benar. Ayo latihan lagi! 🌈';
        else komentar = 'Jangan sedih, Kapten! Setiap usaha itu hebat! 😊';
        hasilKomentar.textContent = komentar;

        setMood(araHasil, akurasi >= 60 ? 'selebrasi' : 'semangat');
        if (akurasi >= 80) percikBintang(hasilPercik);
        else hasilPercik.innerHTML = '';

        tahapSoal.classList.add('hidden');
        tahapHasil.classList.remove('hidden');
        tutupBtn.style.display = 'none';
    }

    function tutupKuis() {
        if (!modal) return;
        if (sesi && !sesi.selesai && sesi.indeks < sesi.totalSoal) {
            if (!confirm('Yakin keluar? Progres akan hangus.')) return;
        }
        stopTimer();
        sesi = null;
        tahapSoal.classList.remove('hidden');
        tahapHasil.classList.add('hidden');
        tutupBtn.style.display = '';
        sembunyikanModal(modal);
        arazkaBicara('Semangat terus, Kapten! 🌟', 'halo', 5000);
    }

    function ulangSesi() {
        if (!sesi) return;
        var mode = sesi.mode;
        stopTimer();
        sesi = null;
        tahapSoal.classList.remove('hidden');
        tahapHasil.classList.add('hidden');
        tutupBtn.style.display = '';
        sembunyikanModal(modal);
        setTimeout(function () { mulaiSesi(mode); }, 200);
    }

    function selesaiSesi() {
        stopTimer();
        sesi = null;
        tahapSoal.classList.remove('hidden');
        tahapHasil.classList.add('hidden');
        tutupBtn.style.display = '';
        sembunyikanModal(modal);
        arazkaBicara('Koinmu sekarang ' + koinTotal + ' ⭐', 'selebrasi', 6000);
    }

    // ============ TOKO ============
    function bangunToko() {
        if (sudahBangunToko || !tokoGrid || !ITEM_TOKO.length) return;
        tokoGrid.innerHTML = '';
        ITEM_TOKO.forEach(function (item) {
            var card = document.createElement('article');
            card.className = 'item-toko flex flex-col text-center bg-white border-2 border-violet-200 rounded-2xl p-3';
            card.setAttribute('data-item-id', item.id);
            card.setAttribute('data-harga', item.harga);
            card.setAttribute('data-nama', item.nama);
            card.innerHTML =
                '<div class="w-16 h-16 mx-auto mb-2">' + item.ikon + '</div>' +
                '<h4 class="text-sm font-black leading-tight">' + item.nama + '</h4>' +
                '<p class="text-xs font-bold opacity-70 mt-1">' + item.deskripsi + '</p>' +
                '<div class="mt-2 inline-flex items-center justify-center gap-1 bg-cosmic-accent border-2 border-amber-300 rounded-full px-2 py-0.5 mx-auto">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" fill="#FCD34D" stroke="#B45309" stroke-width="1.2"/></svg>' +
                    '<span class="text-xs font-extrabold">' + item.harga + '</span>' +
                '</div>' +
                '<p class="item-status mt-1 text-xs font-extrabold text-violet-700">Tersedia</p>' +
                '<button type="button" class="btn-beli w-full mt-2 bg-violet-500 hover:bg-violet-600 text-white font-extrabold text-sm py-2 rounded-full shadow">Beli</button>';
            tokoGrid.appendChild(card);
        });
        sudahBangunToko = true;
    }

    function bukaToko() {
        if (!tokoModal) return;
        bangunToko();
        fokusSebelum = tokoBtn;
        tampilSaldoToko();
        bicara(araToko, 'halo', 'netral', 'Selamat datang! 🛍️', 'Pilih item favoritmu. Koinmu ' + koinTotal + ' ⭐', false);
        tampilkanModal(tokoModal);
    }
    function tutupToko() { if (!tokoModal) return; sembunyikanModal(tokoModal); }

    function beliItem(card) {
        var id = card.getAttribute('data-item-id');
        var harga = parseInt(card.getAttribute('data-harga'), 10) || 0;
        var nama = card.getAttribute('data-nama');
        if (dimiliki[id]) return;
        if (koinTotal < harga) {
            bicara(araToko, 'semangat', 'semangat', 'Sabar! 💪', 'Kurang ' + (harga - koinTotal) + ' koin lagi untuk ' + nama + '.', false);
            return;
        }
        ubahKoin(-harga);
        dimiliki[id] = true;
        card.className = 'item-toko flex flex-col text-center bg-emerald-50 border-2 border-emerald-300 rounded-2xl p-3';
        card.querySelector('.item-status').textContent = 'Terbeli ✓';
        card.querySelector('.item-status').className = 'item-status mt-1 text-xs font-extrabold text-emerald-700';
        var btn = card.querySelector('.btn-beli');
        btn.disabled = true;
        btn.textContent = 'Dimiliki ✓';
        btn.className = 'btn-beli w-full mt-2 bg-emerald-200 text-emerald-800 font-extrabold text-sm py-2 rounded-full cursor-default';

        fetch('api/beli_item.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_id: id })
        }).catch(function () {});

        pakaiOutfitItem(id);
        bicara(araToko, 'selebrasi', 'sukses', 'Yeay! 🎉', nama + ' jadi milikmu! Sisa koin: ' + koinTotal, true);
    }

    function pakaiOutfitItem(itemId) {
        var svgWrappers = document.querySelectorAll('.arazka-wrap');
        if (itemId === 'topi-astronaut' || itemId === 'mahkota-galaksi') {
            svgWrappers.forEach(function (w) {
                var topiGroup = w.querySelector('.arazka-topi');
                if (topiGroup) topiGroup.style.display = '';
                var sub = w.querySelector('.arazka-topi-' + (itemId === 'topi-astronaut' ? 'astronaut' : 'mahkota'));
                if (sub) sub.style.display = '';
                var other = w.querySelector('.arazka-topi-' + (itemId === 'topi-astronaut' ? 'mahkota' : 'astronaut'));
                if (other) other.style.display = 'none';
            });
        }
        if (itemId === 'kacamata-bintang') {
            svgWrappers.forEach(function (w) {
                var k = w.querySelector('.arazka-kacamata');
                if (k) k.style.display = '';
            });
        }
    }

    function terapkanOutfitAwal() {
        if (USER.topi) pakaiOutfitItem(USER.topi);
        if (USER.kacamata) pakaiOutfitItem(USER.kacamata);
    }
        // ============ SHOP FULL (shop.php) ============
        // ============ SHOP FULL (shop.php) — Pakai DB ============
       // ============ SHOP FULL (shop.php) — Pakai localStorage ============
    function renderShopFull() {
        var grid = document.getElementById('shop-full-grid');
        if (!grid) return;

        var userId = USER.id || 0;
        var KEY_KOIN = 'bs_koin_' + userId;
        var KEY_KOLEKSI = 'bs_koleksi_' + userId;

        // Ambil koin dari localStorage (fallback ke DB value)
        var koinSekarang = (function () {
            var v = localStorage.getItem(KEY_KOIN);
            return v !== null ? parseInt(v, 10) : koinTotal;
        })();

        // Ambil koleksi
        var koleksi = (function () {
            try {
                var v = localStorage.getItem(KEY_KOLEKSI);
                return v ? JSON.parse(v) : [];
            } catch (e) { return []; }
        })();

        function simpanKoin() { localStorage.setItem(KEY_KOIN, String(koinSekarang)); }
        function simpanKoleksi() { localStorage.setItem(KEY_KOLEKSI, JSON.stringify(koleksi)); }
        function sudahDimiliki(id) {
            for (var i = 0; i < koleksi.length; i++) if (koleksi[i].id === id) return true;
            return false;
        }

        // Render tombol di setiap item
        function renderTombol(card) {
            var id = card.getAttribute('data-item-id');
            var area = card.querySelector('.btn-area');
            if (!area) return;

            if (sudahDimiliki(id)) {
                // Sudah dimiliki → status + tombol pakai (kalau bisa dipakai)
                var slot = card.getAttribute('data-item-slot');
                var bisaDipakai = (slot === 'topi' || slot === 'kacamata');
                area.innerHTML =
                    '<div class="w-full text-center bg-emerald-100 text-emerald-700 font-extrabold text-sm py-2.5 rounded-full">Dimiliki ✅</div>' +
                    (bisaDipakai
                        ? '<button type="button" class="btn-pakai w-full mt-2 bg-violet-500 hover:bg-violet-600 text-white font-extrabold text-sm py-2 rounded-full transition-transform hover:scale-105" data-item-id="' + id + '">Gunakan 👕</button>'
                        : '');
            } else {
                // Belum dimiliki → tombol beli
                area.innerHTML =
                    '<button type="button" class="btn-beli w-full bg-violet-500 hover:bg-violet-600 text-white font-extrabold text-sm py-2.5 rounded-full shadow transition-transform hover:scale-105 active:scale-95">Beli 🛒</button>';
            }
        }

        // Render semua item
        grid.querySelectorAll('.item-toko').forEach(renderTombol);

        // Update saldo di header
        var saldoEl = document.getElementById('shop-saldo');
        if (saldoEl) saldoEl.textContent = koinSekarang;

        // Event: klik beli / pakai
        grid.addEventListener('click', function (e) {
            var btnBeli = e.target.closest('.btn-beli');
            var btnPakai = e.target.closest('.btn-pakai');
            var card = e.target.closest('.item-toko');
            if (!card) return;

            // ============ BELI ============
            if (btnBeli && !btnBeli.disabled) {
                var id = card.getAttribute('data-item-id');
                var nama = card.getAttribute('data-item-nama');
                var harga = parseInt(card.getAttribute('data-item-harga'), 10) || 0;

                if (sudahDimiliki(id)) {
                    window.showToast('Kamu sudah punya item ini! ✅', 2000);
                    return;
                }

                if (koinSekarang < harga) {
                    window.showToast('😢 Koin belum cukup! Butuh ' + (harga - koinSekarang) + ' koin lagi.', 3000);
                    return;
                }

                // Proses beli
                koinSekarang -= harga;
                simpanKoin();

                koleksi.push({
                    id: id,
                    nama: nama,
                    emoji: card.getAttribute('data-item-emoji'),
                    slot: card.getAttribute('data-item-slot')
                });
                simpanKoleksi();

                // Update saldo
                if (saldoEl) saldoEl.textContent = koinSekarang;

                // Update tombol
                renderTombol(card);

                // Sinkronkan koinTotal global
                window.setStarCoins ? window.setStarCoins(koinSekarang) : null;

                // Sinkron ke DB (opsional)
                fetch('api/beli_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ item_id: id })
                }).catch(function () {});

                window.showToast('🎉 ' + nama + ' jadi milikmu!', 2500);
                return;
            }

            // ============ PAKAI ============
            if (btnPakai && !btnPakai.disabled) {
                var idP = btnPakai.getAttribute('data-item-id');
                var slotP = card.getAttribute('data-item-slot');

                // Simpan slot yang aktif ke localStorage
                var keyTopi = 'bs_topi_' + userId;
                var keyKacamata = 'bs_kacamata_' + userId;

                if (slotP === 'topi') {
                    localStorage.setItem(keyTopi, idP);
                } else if (slotP === 'kacamata') {
                    localStorage.setItem(keyKacamata, idP);
                }

                window.showToast('👕 Berhasil dipakai! Cek di halaman Profil.', 2500);

                // Update UI: tombol pakai jadi "Dipakai ✓"
                btnPakai.outerHTML = '<div class="w-full mt-2 text-center bg-emerald-100 text-emerald-700 font-extrabold text-sm py-2 rounded-full">Dipakai ✓</div>';

                // Sinkron DB
                fetch('api/pakai_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ item_id: idP })
                }).catch(function () {});
            }
        });
    }
    // ============ CHAT CS ARAZKA (arazka.php) ============
    function initChatCS() {
        var form   = document.getElementById('chat-form');
        var input  = document.getElementById('chat-input');
        var box    = document.getElementById('chat-messages');
        if (!form || !input || !box) return;

        var faqPills = document.querySelectorAll('.faq-pill');

        var knowledge = [
            { keys: ['koin', 'bintang', 'dapat koin'], jawaban: 'Koin Bintang ⭐ bisa kamu dapat dengan menyelesaikan Misi Wajib di setiap planet, Kapten! Setiap jawaban benar memberi koin. Semakin tinggi akurasimu, semakin banyak koinmu! 🚀' },
            { keys: ['buka planet', 'terkunci', 'unlock', 'kunci'], jawaban: 'Planet yang terkunci 🔒 akan terbuka kalau kamu sudah naik level, Kapten! Terus selesaikan misi di planet yang terbuka dulu, ya! 💪' },
            { keys: ['pakai barang', 'item', 'toko', 'gunakan'], jawaban: 'Barang yang sudah kamu beli 🛍️ bisa dipakai lewat halaman Profil Kapten → Koleksi Saya → tombol "Gunakan 👕". Nanti langsung terpasang di avatar Arazka-mu! ✨' },
            { keys: ['star coin', 'coin', 'koin bintang'], jawaban: 'Star Coins (Koin Bintang) 🪙 adalah mata uang di BRIGHT SPACE! Kamu pakai untuk beli item keren di Toko Bintang. Dapat dari jawab kuis dengan benar! ⭐' },
            { keys: ['misi', 'wajib', 'side quest', 'quest'], jawaban: 'Ada 2 jenis misi, Kapten! ⭐ Misi Wajib kasih koin besar, 💎 Side Quest untuk seru-seruan. Pilih yang kamu suka! 🎯' },
            { keys: ['capek', 'lelah', 'susah', 'gagal'], jawaban: 'Jangan sedih, Kapten! Setiap usaha itu hebat. Istirahat dulu, nanti coba lagi. Aku yakin kamu bisa! 🌈' },
            { keys: ['rahasia', 'tips', 'trik'], jawaban: 'Tips rahasia dari Arazka: 🤫 jawab semua soal dengan benar = Bonus Sempurna +20%! Terus latihan dan fokus ya! 🚀' },
            { keys: ['siapa', 'kamu siapa', 'arazka'], jawaban: 'Aku Arazka! 🤖 Mentor AI BRIGHT SPACE yang selalu siap bantu Kapten cilik seperti kamu! Tanya apa saja, aku siap! ✨' }
        ];

        function cariJawaban(teks) {
            var t = teks.toLowerCase();
            for (var i = 0; i < knowledge.length; i++) {
                for (var j = 0; j < knowledge[i].keys.length; j++) {
                    if (t.indexOf(knowledge[i].keys[j]) !== -1) return knowledge[i].jawaban;
                }
            }
            return 'Hmm, aku belum paham soal itu, Kapten. 🤔 Coba tanya pakai kata lain, atau klik tombol pertanyaan cepat di atas ya! 😊';
        }

        function tambahPesan(siapa, teks) {
            var wrap = document.createElement('div');
            wrap.className = 'flex items-start gap-2 ' + (siapa === 'user' ? 'flex-row-reverse' : '');
            var av = document.createElement('div');
            av.className = 'w-8 h-8 shrink-0 rounded-full flex items-center justify-center text-lg ' + (siapa === 'user' ? 'bg-violet-300 text-white border-2 border-violet-400' : 'bg-violet-100 border-2 border-violet-200');
            av.textContent = siapa === 'user' ? (USER.nama ? USER.nama.charAt(0).toUpperCase() : 'K') : '🤖';
            var bubble = document.createElement('div');
            bubble.className = 'px-4 py-2.5 max-w-[80%] text-sm font-bold leading-snug ' + (siapa === 'user' ? 'bg-violet-500 text-white rounded-2xl rounded-tr-sm' : 'bg-indigo-50 border border-indigo-100 rounded-2xl rounded-tl-sm');
            bubble.textContent = teks;
            wrap.appendChild(av);
            wrap.appendChild(bubble);
            box.appendChild(wrap);
            box.scrollTop = box.scrollHeight;
        }

        function kirimPesan(teks) {
            if (!teks.trim()) return;
            tambahPesan('user', teks);
            input.value = '';
            setTimeout(function () {
                tambahPesan('arazka', cariJawaban(teks));
            }, 500);
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            kirimPesan(input.value);
        });

        faqPills.forEach(function (pill) {
            pill.addEventListener('click', function () {
                kirimPesan(pill.getAttribute('data-q'));
            });
        });
    }

    // ============ PROFILE: PAKAI ITEM (profile.php) ============
    function initProfile() {
        var btns = document.querySelectorAll('.btn-pakai-profile');
        if (!btns.length) return;

        btns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-item-id');
                if (typeof pakaiOutfitItem === 'function') pakaiOutfitItem(id);
                window.showToast('👕 Berhasil dipakai!', 2500);

                btn.textContent = '✓ Dipakai';
                btn.classList.remove('bg-violet-500', 'hover:bg-violet-600', 'hover:scale-105');
                btn.classList.add('bg-emerald-400');

                var card = btn.closest('article');
                if (card) {
                    card.classList.remove('border-violet-200');
                    card.classList.add('border-emerald-400', 'bg-emerald-50');
                }

                fetch('api/pakai_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ item_id: id })
                }).catch(function () {});
            });
        });    // ============ PROFILE: KARAKTER DINAMIS + INVENTORY ============
    function initProfile() {
        var karakterEl = document.getElementById('karakter-utama');
        if (!karakterEl) return;  // bukan halaman profile

        var userId = USER.id || 0;
        var KEY_KOLEKSI = 'bs_koleksi_' + userId;
        var KEY_TOPI = 'bs_topi_' + userId;
        var KEY_KACAMATA = 'bs_kacamata_' + userId;

        // Ambil koleksi dari localStorage
        var koleksi = (function () {
            try {
                var v = localStorage.getItem(KEY_KOLEKSI);
                return v ? JSON.parse(v) : [];
            } catch (e) { return []; }
        })();

        // Item yang aktif
        var topiAktif = localStorage.getItem(KEY_TOPI) || null;
        var kacamataAktif = localStorage.getItem(KEY_KACAMATA) || null;

        // ============ RENDER KARAKTER ============
        function renderKarakter() {
            var topiEl = document.getElementById('avatar-topi');
            var kacamataEl = document.getElementById('avatar-kacamata');

            // Reset
            topiEl.style.display = 'none';
            topiEl.textContent = '';
            kacamataEl.style.display = 'none';
            kacamataEl.textContent = '';

            // Cari emoji dari koleksi
            if (topiAktif) {
                var itemTopi = koleksi.find(function (it) { return it.id === topiAktif; });
                if (itemTopi) {
                    topiEl.textContent = itemTopi.emoji || '🎩';
                    topiEl.style.display = '';
                    topiEl.classList.remove('bounce-in');
                    void topiEl.offsetWidth;
                    topiEl.classList.add('bounce-in');
                }
            }

            if (kacamataAktif) {
                var itemKaca = koleksi.find(function (it) { return it.id === kacamataAktif; });
                if (itemKaca) {
                    kacamataEl.textContent = itemKaca.emoji || '🕶️';
                    kacamataEl.style.display = '';
                    kacamataEl.classList.remove('bounce-in');
                    void kacamataEl.offsetWidth;
                    kacamataEl.classList.add('bounce-in');
                }
            }

            // Update stat item count
            var statItem = document.getElementById('stat-item');
            if (statItem) statItem.textContent = koleksi.length;
        }

        // ============ RENDER KOLEKSI ============
        function renderKoleksi() {
            var emptyEl = document.getElementById('koleksi-empty');
            var gridEl = document.getElementById('koleksi-grid');
            if (!gridEl) return;

            if (koleksi.length === 0) {
                emptyEl.classList.remove('hidden');
                gridEl.classList.add('hidden');
                gridEl.innerHTML = '';
                return;
            }

            emptyEl.classList.add('hidden');
            gridEl.classList.remove('hidden');
            gridEl.innerHTML = '';

            koleksi.forEach(function (item) {
                var card = document.createElement('article');
                card.className = 'flex flex-col bg-white border-2 rounded-2xl p-3 shadow-sm transition-all';
                card.setAttribute('data-item-id', item.id);
                card.setAttribute('data-item-slot', item.slot || '');

                var sedangDipakai = (topiAktif === item.id) || (kacamataAktif === item.id);
                var bisaDipakai = (item.slot === 'topi' || item.slot === 'kacamata');

                if (sedangDipakai) {
                    card.classList.add('border-emerald-400', 'bg-emerald-50');
                } else {
                    card.classList.add('border-violet-200');
                }

                var html =
                    '<div class="text-4xl text-center mb-2 select-none">' + (item.emoji || '🎁') + '</div>' +
                    '<h3 class="text-xs font-black leading-tight text-center flex-1">' + item.nama + '</h3>';

                if (sedangDipakai) {
                    html += '<span class="mt-2 w-full text-center text-xs font-extrabold bg-emerald-400 text-white py-1.5 rounded-full">✓ Dipakai</span>';
                } else if (bisaDipakai) {
                    html += '<button type="button" class="btn-pakai-profile w-full mt-2 bg-violet-500 hover:bg-violet-600 text-white font-extrabold text-xs py-1.5 rounded-full transition-transform hover:scale-105" data-item-id="' + item.id + '">Gunakan 👕</button>';
                } else {
                    html += '<span class="mt-2 w-full text-center text-xs font-bold bg-slate-100 text-slate-500 py-1.5 rounded-full">Koleksi</span>';
                }

                card.innerHTML = html;
                gridEl.appendChild(card);
            });

            // Attach event ke tombol pakai
            gridEl.querySelectorAll('.btn-pakai-profile').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var id = btn.getAttribute('data-item-id');
                    var card = btn.closest('article');
                    var slot = card.getAttribute('data-item-slot');

                    // Simpan ke localStorage
                    if (slot === 'topi') localStorage.setItem(KEY_TOPI, id);
                    else if (slot === 'kacamata') localStorage.setItem(KEY_KACAMATA, id);

                    // Update variable aktif
                    topiAktif = localStorage.getItem(KEY_TOPI);
                    kacamataAktif = localStorage.getItem(KEY_KACAMATA);

                    // Re-render
                    renderKarakter();
                    renderKoleksi();

                    window.showToast('👕 Berhasil dipakai!', 2000);

                    // Sinkron ke DB (opsional)
                    fetch('api/pakai_item.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ item_id: id })
                    }).catch(function () {});
                });
            });
        }

        // ============ INIT ============
        renderKarakter();
        renderKoleksi();
    }
    }

    // ============ INIT ============
    function init() {
        coinEl = document.getElementById('star-coin-count');
        coinBadge = document.getElementById('star-coin-badge');
        toastEl = document.getElementById('toast');

        modal = document.getElementById('kuis-modal');
        tahapSoal = document.getElementById('kuis-tahap-soal');
        tahapHasil = document.getElementById('kuis-tahap-hasil');
        ikonEl = document.getElementById('kuis-ikon');
        judulEl = document.getElementById('kuis-judul');
        timerBox = document.getElementById('kuis-timer-box');
        timerEl = document.getElementById('kuis-timer');
        progTeksEl = document.getElementById('kuis-progress-teks');
        progBenarEl = document.getElementById('kuis-progress-benar');
        progBarEl = document.getElementById('kuis-progress-bar');
        tanyaEl = document.getElementById('kuis-pertanyaan');
        pilihanEl = document.getElementById('kuis-pilihan');
        lanjutBtn = document.getElementById('kuis-lanjut');
        tutupBtn = document.getElementById('kuis-tutup');

        hasilBenarEl = document.getElementById('hasil-benar');
        hasilTotalEl = document.getElementById('hasil-total');
        hasilAkurasiEl = document.getElementById('hasil-akurasi');
        hasilBarEl = document.getElementById('hasil-bar');
        hasilRewardEl = document.getElementById('hasil-reward');
        hasilBonusEl = document.getElementById('hasil-bonus-teks');
        hasilKomentar = document.getElementById('hasil-komentar');
        hasilSelesai = document.getElementById('hasil-selesai');
        hasilUlang = document.getElementById('hasil-ulang');
        hasilPercik = document.getElementById('hasil-percik');
        araHasil = document.getElementById('arazka-hasil');

        tokoModal = document.getElementById('toko-modal');
        tokoGrid = document.getElementById('toko-grid');
        tokoSaldo = document.getElementById('toko-saldo');
        tokoTutup = document.getElementById('toko-tutup');
        tokoBtn = document.getElementById('btn-toko-bintang');

        wBtn = document.getElementById('arazka-btn');
        wBubble = document.getElementById('arazka-bubble');
        wTeks = document.getElementById('arazka-teks');

        araKuis = {
            avatar: document.getElementById('arazka-modal'),
            percik: document.getElementById('kuis-percik'),
            bubble: document.getElementById('kuis-bubble'),
            judul: document.getElementById('kuis-fb-judul'),
            teks: document.getElementById('kuis-fb-teks')
        };
        araToko = {
            avatar: document.getElementById('arazka-toko'),
            percik: document.getElementById('toko-percik'),
            bubble: document.getElementById('toko-bubble'),
            judul: document.getElementById('toko-fb-judul'),
            teks: document.getElementById('toko-fb-teks')
        };

        // ============ EVENT ============
        if (wBtn) {
            wBtn.addEventListener('click', function () {
                if (wBubble.classList.contains('hidden')) {
                    arazkaBicara(TIPS[indeksTips++ % TIPS.length], 'halo', 8000);
                } else {
                    wBubble.classList.add('hidden');
                    wBubble.style.display = 'none';
                }
            });
        }

        document.querySelectorAll('.kategori-card').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var kat = btn.getAttribute('data-kategori');
                document.querySelectorAll('.mode-card').forEach(function (m) {
                    m.style.display = (m.getAttribute('data-kategori') === kat) ? '' : 'none';
                });
                var secKat = document.getElementById('section-kategori');
                var secGame = document.getElementById('section-game');
                if (secKat) secKat.classList.add('hidden');
                if (secGame) secGame.classList.remove('hidden');
                var jd = document.getElementById('judul-game');
                if (jd) jd.textContent = (kat === 'wajib' ? '⭐ Misi Wajib' : '💎 Side Quest');
            });
        });
        var btnGanti = document.getElementById('btn-ganti-kategori');
        if (btnGanti) {
            btnGanti.addEventListener('click', function () {
                var secKat = document.getElementById('section-kategori');
                var secGame = document.getElementById('section-game');
                if (secKat) secKat.classList.remove('hidden');
                if (secGame) secGame.classList.add('hidden');
            });
        }

        document.querySelectorAll('.btn-mode').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var card = btn.closest('.mode-card');
                var modeId = card.getAttribute('data-mode-id');
                var modeData = GAME_MODES[modeId];
                if (!modeData) { window.showToast('Mode belum tersedia!', 3000); return; }

                if (modeData.type === 'kuis' || modeData.type === 'latihan' || modeData.type === 'story') {
                    fokusSebelum = btn;
                    mulaiSesi(modeData);
                } else {
                    window.showToast(modeData.judul + ' segera hadir! 🚀', 3500);
                    arazkaBicara('Mode ' + modeData.judul + ' sedang disiapkan! 🚀', 'halo', 5000);
                }
            });
        });

        if (pilihanEl) {
            pilihanEl.addEventListener('click', function (e) {
                var btn = e.target.closest('.opsi-jawaban');
                if (btn && !btn.disabled) pilihJawaban(btn);
            });
        }
        if (lanjutBtn) lanjutBtn.addEventListener('click', lanjutSoal);
        if (tutupBtn) tutupBtn.addEventListener('click', tutupKuis);
        if (hasilSelesai) hasilSelesai.addEventListener('click', selesaiSesi);
        if (hasilUlang) hasilUlang.addEventListener('click', ulangSesi);

        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) tutupKuis();
            });
        }

        if (tokoBtn) tokoBtn.addEventListener('click', function(){ window.location.href='shop.php'; });
        if (tokoTutup) tokoTutup.addEventListener('click', tutupToko);
        if (tokoModal) {
            tokoModal.addEventListener('click', function (e) {
                if (e.target === tokoModal) tutupToko();
            });
        }
        if (tokoGrid) {
            tokoGrid.addEventListener('click', function (e) {
                var btn = e.target.closest('.btn-beli');
                if (btn && !btn.disabled) beliItem(btn.closest('.item-toko'));
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (modalAktif === modal) tutupKuis();
                else if (modalAktif === tokoModal) tutupToko();
            }
        });
        terapkanOutfitAwal();
        // BS_STAGE6B_SHOP_LINK: jangan buat pembelian lokal di dashboard.\n        renderShopDashboard6B();
        renderShopFull();
        initArazkaChat();
        initChatCS();
        initProfile();

        setTimeout(function () {
            if (!modalAktif && !document.getElementById('arazka-chat')) {
                arazkaBicara('Halo, Kapten ' + USER.nama + '! Siap bertualang? 🚀', 'halo', 9000);
            }
        }, 1200);
    }

       // ============ RENDER SHOP DI DASHBOARD ============
    function renderShopDashboard() {
        var grid = document.getElementById('shop-grid');
        if (!grid) return;
        if (!ITEM_TOKO || !ITEM_TOKO.length) {
            grid.innerHTML = '<p class="col-span-full text-center text-sm font-bold text-slate-400 py-4">Item toko belum tersedia.</p>';
            return;
        }
        grid.innerHTML = '';
        ITEM_TOKO.slice(0, 4).forEach(function (item) {
            var card = document.createElement('article');
            card.className = 'item-toko flex flex-col text-center bg-white border-2 border-violet-200 rounded-2xl p-3 hover:shadow-md transition-shadow';
            card.setAttribute('data-item-id', item.id);
            card.setAttribute('data-harga', item.harga);
            card.setAttribute('data-nama', item.nama);
            card.innerHTML =
                '<div class="w-14 h-14 mx-auto mb-2">' + item.ikon + '</div>' +
                '<h4 class="text-xs font-black leading-tight">' + item.nama + '</h4>' +
                '<div class="mt-2 inline-flex items-center justify-center gap-1 bg-cosmic-accent border-2 border-amber-300 rounded-full px-2 py-0.5 mx-auto">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-3.5 h-3.5"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" fill="#FCD34D" stroke="#B45309" stroke-width="1.2"/></svg>' +
                    '<span class="text-xs font-extrabold">' + item.harga + '</span>' +
                '</div>' +
                '<button type="button" class="btn-beli-dash w-full mt-2 bg-violet-500 hover:bg-violet-600 text-white font-extrabold text-xs py-1.5 rounded-full">Beli</button>';
            grid.appendChild(card);
        });

        grid.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-beli-dash');
            if (!btn || btn.disabled) return;
            var card = btn.closest('.item-toko');
            var id = card.getAttribute('data-item-id');
            var harga = parseInt(card.getAttribute('data-harga'), 10) || 0;
            var nama = card.getAttribute('data-nama');

            if (koinTotal < harga) {
                window.showToast('Koin belum cukup! Butuh ' + (harga - koinTotal) + ' koin lagi 💪', 3000);
                return;
            }
            ubahKoin(-harga);
            btn.disabled = true;
            btn.textContent = 'Dimiliki ✓';
            btn.className = 'w-full mt-2 bg-emerald-200 text-emerald-800 font-extrabold text-xs py-1.5 rounded-full cursor-default';

            fetch('api/beli_item.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ item_id: id })
            }).catch(function () {});

            if (typeof pakaiOutfitItem === 'function') pakaiOutfitItem(id);
            window.showToast('🎉 ' + nama + ' jadi milikmu!', 2500);
        });
    }

    function renderShopDashboard6B() {
        const grid=document.getElementById('shop-grid');
        if(!grid)return;
        grid.innerHTML='<a href="shop.php" class="block col-span-full text-center rounded-2xl bg-violet-500 hover:bg-violet-600 px-5 py-5 text-white font-black">🛍️ Buka Toko Bintang — transaksi aman MySQL →</a>';
    }
    // ============ PANGGIL INIT DI AKHIR ============
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
    