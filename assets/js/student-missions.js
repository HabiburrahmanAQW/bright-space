/** BRIGHT SPACE Tahap 3 - kategori kosong tidak dapat dipilih. */
(function () {
  'use strict';
  function init() {
    document.querySelectorAll('.kategori-card[data-kategori]').forEach(function (button) {
      var category = button.getAttribute('data-kategori');
      if (!['wajib', 'side'].includes(category)) return;
      var available = Array.from(document.querySelectorAll('.mode-card[data-kategori]')).some(function (card) {
        return card.getAttribute('data-kategori') === category;
      });
      if (!available) {
        button.disabled = true;
        button.setAttribute('aria-disabled', 'true');
        button.classList.add('opacity-60', 'cursor-not-allowed');
        var note = document.createElement('span');
        note.className = 'mt-2 text-xs font-extrabold text-slate-600';
        note.textContent = 'Belum ada misi di kategori ini';
        button.appendChild(note);
      }
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
