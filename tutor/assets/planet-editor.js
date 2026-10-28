/* BRIGHT SPACE - Tutor Planet Editor (Vanilla JavaScript only) */
(() => {
  'use strict';
  const dataEl = document.getElementById('editor-data');
  const form = document.getElementById('planet-form');
  if (!dataEl || !form) return;
  const boot = JSON.parse(dataEl.textContent);
  const modes = boot.modes;
  const icons = boot.icons;
  const list = document.getElementById('mission-list');
  const empty = document.getElementById('mission-empty');
  const error = document.getElementById('form-client-error');
  const nameField = document.getElementById('planet-name');
  const slugField = document.getElementById('planet-slug');
  const levelField = document.getElementById('planet-level');
  const descriptionField = document.getElementById('planet-description');
  const iconField = document.getElementById('planet-icon');
  const missions = Array.isArray(boot.missions) ? boot.missions.slice(0, 24) : [];
  let slugModified = !!slugField.value;

  const slugify = (value) => value.toLowerCase().normalize('NFKD')
    .replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '').slice(0, 64).replace(/-+$/g, '');

  function updatePreview() {
    document.getElementById('preview-title').textContent = nameField.value.trim() || 'Planet Baru';
    document.getElementById('preview-description').textContent = descriptionField.value.trim() || 'Deskripsi planet akan muncul di sini.';
    document.getElementById('preview-icon').innerHTML = icons[iconField.value] || icons.umum; // SVG dari katalog hardcoded PHP, bukan input pengguna.
    const level = Number.parseInt(levelField.value, 10);
    const req = Number.isInteger(level) && level >= 0 ? level : 0;
    document.getElementById('preview-level').textContent = req === 0 ? '✅ Terbuka untuk Semua' : `🔒 Membutuhkan Level ${req}`;
    document.getElementById('planet-preview').dataset.theme = form.querySelector('input[name="theme_key"]:checked')?.value || 'indigo';
    document.querySelector('.preview-button').textContent = req === 0 ? '🚀 Jelajahi Planet' : `🔒 Terbuka di Level ${req}`;
  }

  function updateCounts() {
    const wajib = missions.filter(m => m.category === 'wajib').length;
    document.getElementById('count-wajib').textContent = wajib;
    document.getElementById('count-side').textContent = missions.length - wajib;
    document.getElementById('mission-count').textContent = `${missions.length}/24 Misi`;
    empty.hidden = missions.length > 0;
    document.getElementById('add-wajib').disabled = missions.length >= 24;
    document.getElementById('add-side').disabled = missions.length >= 24;
  }

  function createMission(category) {
    const mode = category === 'wajib' ? 'kuis' : 'puzzle';
    const def = modes[mode];
    return {
      id: null, category, mode_type: mode,
      title: def.name, description: def.description,
      reward_coins: def.reward, timer_seconds: def.timer,
      question_target: def.questions,
    };
  }

  function appendOptions(select, values, currentValue) {
    for (const [key, value] of Object.entries(values)) {
      const option = document.createElement('option');
      option.value = key;
      option.textContent = value;
      option.selected = key === currentValue;
      select.appendChild(option);
    }
  }

  function render() {
    list.replaceChildren();
    missions.forEach((mission, i) => {
      const card = document.createElement('article');
      card.className = 'mission-card';
      card.dataset.missionIndex = String(i);
      card.innerHTML = `
        <div class="flex flex-wrap justify-between gap-2 items-center mb-4">
          <div class="flex flex-wrap items-center gap-2"><strong class="text-lg" data-card-number></strong><span class="category-badge" data-card-category></span></div>
          <div class="flex gap-1">
            <button type="button" class="mission-action" data-action="up" title="Naikkan urutan misi" aria-label="Naikkan misi">↑</button>
            <button type="button" class="mission-action" data-action="down" title="Turunkan urutan misi" aria-label="Turunkan misi">↓</button>
            <button type="button" class="mission-action mission-action-danger" data-action="remove" title="Hapus misi" aria-label="Hapus misi">✕</button>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div><label class="field-label" data-field-label="category">Kategori Misi</label><select class="field-input" data-mission-field="category"><option value="wajib">⭐ Misi Wajib</option><option value="side">💎 Misi Sampingan</option></select></div>
          <div><label class="field-label" data-field-label="mode_type">Gamemode</label><select class="field-input" data-mission-field="mode_type"></select></div>
          <div class="sm:col-span-2"><label class="field-label" data-field-label="title">Judul Misi *</label><input class="field-input" data-mission-field="title" type="text" required maxlength="160" placeholder="Judul misi"></div>
          <div class="sm:col-span-2"><label class="field-label" data-field-label="description">Deskripsi Misi</label><textarea class="field-input resize-y" data-mission-field="description" rows="2" maxlength="500" placeholder="Ceritakan tujuan misi..."></textarea></div>
          <div><label class="field-label" data-field-label="reward_coins">Reward Koin</label><input class="field-input" data-mission-field="reward_coins" type="number" min="0" max="1000" step="1" required></div>
          <div><label class="field-label" data-field-label="timer_seconds">Timer (detik)</label><input class="field-input" data-mission-field="timer_seconds" type="number" min="0" max="3600" step="1" required></div>
          <div class="sm:col-span-2"><label class="field-label" data-field-label="question_target">Target Jumlah Soal</label><input class="field-input" data-mission-field="question_target" type="number" min="1" max="50" step="1" required></div>
        </div>
        <p class="mt-3 text-xs font-bold text-slate-500" data-mode-hint></p>`;

      card.querySelector('[data-card-number]').textContent = `Misi #${i + 1}`;
      const categoryBadge = card.querySelector('[data-card-category]');
      categoryBadge.textContent = mission.category === 'side' ? '💎 Sampingan' : '⭐ Wajib';
      categoryBadge.classList.add(mission.category === 'side' ? 'category-side' : 'category-wajib');
      card.querySelector('[data-action="up"]').disabled = i === 0;
      card.querySelector('[data-action="down"]').disabled = i === missions.length - 1;

      const modeSelect = card.querySelector('[data-mission-field="mode_type"]');
      appendOptions(modeSelect, Object.fromEntries(Object.entries(modes).map(([k, v]) => [k, `${v.emoji} ${v.name}`])), mission.mode_type);
      for (const [field, value] of Object.entries(mission)) {
        const control = card.querySelector(`[data-mission-field="${field}"]`);
        if (control) control.value = String(value ?? '');
      }
      for (const control of card.querySelectorAll('[data-mission-field]')) {
        const attr = control.dataset.missionField;
        const id = `mission-${i}-${attr}`;
        control.id = id;
        card.querySelector(`[data-field-label="${attr}"]`).htmlFor = id;
      }
      const isPractice = mission.mode_type === 'latihan';
      for (const field of ['reward_coins', 'timer_seconds']) {
        card.querySelector(`[data-mission-field="${field}"]`).readOnly = isPractice;
      }
      card.querySelector('[data-mode-hint]').textContent = isPractice
        ? 'Latihan Bebas selalu 0 koin dan tanpa timer.'
        : `${modes[mission.mode_type]?.emoji || '🎯'} Target soal adalah konfigurasi, bukan isi soal. Editor soal dibangun pada tahap selanjutnya.`;
      list.appendChild(card);
    });
    updateCounts();
  }

  function addMission(category) {
    if (missions.length >= 24) return;
    missions.push(createMission(category));
    render();
    list.lastElementChild?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  }

  document.getElementById('add-wajib').addEventListener('click', () => addMission('wajib'));
  document.getElementById('add-side').addEventListener('click', () => addMission('side'));
  list.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-action]');
    const card = button?.closest('[data-mission-index]');
    if (!button || !card) return;
    const index = Number(card.dataset.missionIndex);
    if (!Number.isInteger(index) || !missions[index]) return;
    switch (button.dataset.action) {
      case 'remove':
        if (missions[index].id && !confirm('Hapus misi tersimpan ini? Penghapusan menjadi permanen setelah kamu menekan Simpan.')) return;
        missions.splice(index, 1);
        break;
      case 'up':
        if (index > 0) [missions[index - 1], missions[index]] = [missions[index], missions[index - 1]];
        break;
      case 'down':
        if (index < missions.length - 1) [missions[index + 1], missions[index]] = [missions[index], missions[index + 1]];
        break;
    }
    render();
  });

  function updateMissionField(event) {
    const control = event.target.closest('[data-mission-field]');
    const card = control?.closest('[data-mission-index]');
    if (!card) return;
    const mission = missions[Number(card.dataset.missionIndex)];
    if (!mission) return;
    const field = control.dataset.missionField;
    const oldMode = mission.mode_type;
    mission[field] = control.value;
    if (field === 'mode_type' && modes[control.value]) {
      const def = modes[control.value];
      if (mission.title === modes[oldMode]?.name) mission.title = def.name;
      if (mission.description === modes[oldMode]?.description) mission.description = def.description;
      mission.reward_coins = def.reward;
      mission.timer_seconds = def.timer;
      mission.question_target = def.questions;
      render();
    } else if (field === 'category') {
      render();
    }
  }
  list.addEventListener('input', updateMissionField);
  list.addEventListener('change', updateMissionField);

  nameField.addEventListener('input', () => {
    if (!boot.isEdit && !slugModified) slugField.value = slugify(nameField.value);
    updatePreview();
  });
  if (!boot.isEdit) {
    slugField.addEventListener('input', () => { slugModified = true; });
    document.getElementById('slug-auto')?.addEventListener('click', () => {
      slugField.value = slugify(nameField.value);
      slugModified = false;
    });
  }
  for (const control of [descriptionField, levelField, iconField]) {
    control.addEventListener('input', updatePreview);
  }
  form.querySelectorAll('input[name="theme_key"]').forEach(el => el.addEventListener('change', updatePreview));

  form.addEventListener('submit', (event) => {
    error.classList.add('hidden');
    const publish = event.submitter?.value === 'published';
    if (publish && missions.length === 0) {
      event.preventDefault();
      error.textContent = 'Tambahkan minimal satu misi sebelum mempublikasikan planet.';
      error.classList.remove('hidden');
      return;
    }
    if (!form.checkValidity()) {
      event.preventDefault();
      form.reportValidity();
      return;
    }
    document.getElementById('missions-json').value = JSON.stringify(missions);
  });

  render();
  updatePreview();
})();
