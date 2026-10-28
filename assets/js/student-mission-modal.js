/* BRIGHT SPACE — player for tutor-created missions; legacy quiz stays untouched. */
(() => {
  'use strict';
  let dialog = null;
  let frame = null;
  let activeButton = null;
  let oldOverflow = '';
  let endingMission = false;

  function closeMission() {
    if (!dialog) return;
    dialog.remove();
    dialog = null;
    frame = null;
    document.body.style.overflow = oldOverflow;
    activeButton?.focus();
    activeButton = null;
  }

  // BS_STAGE6A_FINISHED_UI — status selesai hanya dipercaya setelah halaman PHP
  // selesai menyimpan hasil ke MySQL dan menandai metadata server-side.
  function isSavedFinish() {
    try {
      return !!frame?.contentDocument?.querySelector('meta[name="bs-mission-finished"][content="1"]');
    } catch (_) { return false; }
  }

  async function closeSavedFinish() {
    if (!dialog || endingMission || !isSavedFinish()) return;
    const currentFrame=frame;
    const csrf=currentFrame?.contentDocument?.querySelector('meta[name="bs-mission-csrf"]')?.content || '';
    if (!csrf) { window.alert('Halaman hasil belum selesai dimuat. Coba lagi.'); return; }
    endingMission=true;
    const btn=dialog.querySelector('[data-bs-mission-close]');
    if(btn)btn.disabled=true;
    currentFrame.style.pointerEvents='none';
    try {
      const response=await fetch(currentFrame.src,{
        method:'POST',credentials:'same-origin',
        headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','Accept':'application/json'},
        body:new URLSearchParams({csrf_token:csrf,action:'close_finished'})
      });
      if(!response.ok)throw new Error('HTTP '+response.status);
      const data=await response.json();
      if(data?.ok!==true)throw new Error('Server belum mengonfirmasi hasil tersimpan.');
      closeMission();
    } catch(error){
      window.alert('Tidak dapat menutup hasil misi dengan aman. Coba tekan Selesai lagi.\n'+error.message);
    } finally {
      endingMission=false;
      if(dialog && btn) btn.disabled=false;
      if(dialog && frame===currentFrame) currentFrame.style.pointerEvents='';
    }
  }
  async function confirmEndMission() {
    if (!dialog || endingMission) return;
    if (isSavedFinish()) return closeSavedFinish(); // Sudah tersimpan: tanpa alert hangus progres.
    const confirmed = window.confirm(
      'Yakin ingin mengakhiri misi?\n\nSemua progres soal saat ini akan hangus, dan kamu harus mulai lagi dari soal pertama.'
    );
    if (!confirmed) return; // Jika Batal, sesi dan timer tetap berjalan.

    const currentFrame = frame;
    let csrf = '';
    try {
      // Iframe satu origin; token didapat dari halaman permainan saat ini.
      csrf = currentFrame?.contentDocument?.querySelector('meta[name="bs-mission-csrf"]')?.content || '';
    } catch (_) {
      csrf = '';
    }
    if (!csrf) {
      window.alert('Permainan belum selesai dimuat. Coba tekan X lagi sebentar lagi.');
      return;
    }

    endingMission = true;
    const closeButton = dialog.querySelector('[data-bs-mission-close]');
    if (closeButton) closeButton.disabled = true;
    currentFrame.style.pointerEvents = 'none';

    try {
      const response = await fetch(currentFrame.src, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
          'Accept': 'application/json'
        },
        body: new URLSearchParams({ csrf_token: csrf, action: 'abandon' })
      });
      if (!response.ok) throw new Error('Server gagal mengakhiri misi (HTTP ' + response.status + ').');
      const result = await response.json();
      if (result?.ok !== true) throw new Error('Server tidak mengonfirmasi pengakhiran sesi.');
      closeMission(); // Tutup modal hanya SETELAH sesi PHP berhasil dihapus.
    } catch (error) {
      window.alert('Sesi belum berhasil diakhiri. Modal tetap terbuka agar progres tidak hilang tanpa konfirmasi.\n\n' + error.message);
    } finally {
      endingMission = false;
      if (closeButton && dialog) closeButton.disabled = false;
      if (dialog && frame === currentFrame) currentFrame.style.pointerEvents = '';
    }
  }
  function openMission(url, button) {
    if (dialog) return; // Jangan menutup sesi lain secara diam-diam.
    activeButton = button;
    oldOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    dialog = document.createElement('div');
    dialog.id = 'bs-mission-dialog';
    dialog.className = 'fixed inset-0 z-[120] bg-indigo-950/65 flex items-center justify-center p-2 sm:p-4';
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-modal', 'true');
    dialog.setAttribute('aria-label', 'Permainan Misi');

    const panel = document.createElement('div');
    panel.className = 'relative bg-violet-100 border-2 border-amber-200 rounded-3xl shadow-2xl overflow-hidden';
    panel.style.width = 'min(540px, 97vw)';
    panel.style.height = 'min(780px, 94dvh)';
    panel.style.maxHeight = '94vh';

    const close = document.createElement('button');
    close.type = 'button';
    close.textContent = '×';
    close.setAttribute('aria-label', 'Tutup permainan dan kembali ke planet');
    close.title = 'Akhiri permainan';
    close.setAttribute('data-bs-mission-close', '1');
    close.className = 'absolute right-2 top-2 z-10 bg-white text-indigo-950 hover:bg-rose-50 rounded-full shadow border border-violet-200 w-9 h-9 font-black text-2xl leading-none';
    close.addEventListener('click', confirmEndMission);

    frame = document.createElement('iframe');
    frame.title = 'Permainan Misi BRIGHT SPACE';
    frame.className = 'w-full h-full border-0';
    frame.setAttribute('referrerpolicy', 'same-origin');
    frame.src = url.toString();
    panel.append(frame, close);
    dialog.appendChild(panel);
    document.body.appendChild(dialog);
    close.focus();
    dialog.addEventListener('click', event => {
      if (event.target === dialog) confirmEndMission();
    });
  }

  document.addEventListener('click', event => {
    const link = event.target.closest('a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    const url = new URL(link.href, location.href);
    if (url.origin !== location.origin || !url.pathname.endsWith('/mainkan-misi.php') || !/^\d+$/.test(url.searchParams.get('mission') || '')) return;
    event.preventDefault();
    url.searchParams.set('embed','1');
    openMission(url, link);
  });

  window.addEventListener('message', event => {
    if (!dialog || !frame || event.origin !== location.origin || event.source !== frame.contentWindow) return;
    // BS_MISSION_BALANCE_STAGE5: refresh badge koin dari hasil server, tidak dari browser.
    if (event.data && event.data.type === 'bs-mission-reward') {
      const balance=Number(event.data.balance);
      if (Number.isSafeInteger(balance) && balance>=0) {
        document.querySelectorAll('#sidebar-koin,#star-coin-count').forEach(el=>el.textContent=String(balance));
        if (window.BRIGHT_SPACE?.user) window.BRIGHT_SPACE.user.star_coins=balance;
      }
    }
    if (event.data && event.data.type === 'bs-mission-done') closeSavedFinish();
    if (event.data && event.data.type === 'bs-mission-close') confirmEndMission();
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && dialog) {
      event.preventDefault();
      confirmEndMission();
    }
  });
})();
