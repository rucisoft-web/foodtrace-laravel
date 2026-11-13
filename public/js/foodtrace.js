// FoodTrace: panel bantuan (tombol ? / tombol keyboard "?"), konfirmasi, pilih-semua, fokus scan.
(function () {
  const panel = document.getElementById('help-panel');
  const open = () => panel && (panel.classList.add('open'), panel.setAttribute('aria-hidden', 'false'));
  const close = () => panel && (panel.classList.remove('open'), panel.setAttribute('aria-hidden', 'true'));
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-help-open]')) { e.preventDefault(); open(); }
    if (e.target.closest('[data-help-close]')) { e.preventDefault(); close(); }
    const c = e.target.closest('[data-confirm]');
    if (c && !window.confirm(c.dataset.confirm)) { e.preventDefault(); e.stopPropagation(); }
  });
  document.addEventListener('keydown', (e) => {
    const typing = /INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName);
    if (e.key === '?' && !typing) { e.preventDefault(); panel && panel.classList.contains('open') ? close() : open(); }
    if (e.key === 'Escape') close();
  });
  document.querySelectorAll('[data-check-all]').forEach((all) => {
    all.addEventListener('change', () => {
      document.querySelectorAll(all.dataset.checkAll).forEach((c) => { c.checked = all.checked; });
    });
  });
  // Kotak scan: fokus otomatis dan kirim saat Enter (scanner mengirim Enter di akhir).
  const scan = document.querySelector('input[data-autofocus]');
  if (scan) { scan.focus(); scan.select && scan.select(); }
  document.querySelectorAll('select[data-submit], input[data-submit]').forEach((el) => el.addEventListener('change', () => el.form.submit()));
})();

// Grafik: palet FoodTrace (biru tua, merah tua, cokelat, hijau daun).
window.FT = {
  colors: ['#1b2a4e', '#7f1d2d', '#7aa83a', '#47302a', '#0f766e', '#b7791f', '#5b6b9a', '#b85667', '#9cc06a', '#8a6a5c'],
  zone: { frozen: '#1b2a4e', chilled: '#0f766e', dry: '#9a3412' },
  rp: (v) => 'Rp ' + Math.round(v).toLocaleString('id-ID'),
  short: (v) => {
    const a = Math.abs(v);
    if (a >= 1e9) return (v / 1e9).toFixed(1).replace('.', ',') + ' M';
    if (a >= 1e6) return (v / 1e6).toFixed(1).replace('.', ',') + ' jt';
    if (a >= 1e3) return (v / 1e3).toFixed(0) + ' rb';
    return Math.round(v).toString();
  },
};
if (window.Chart) {
  Chart.defaults.font.family = '"Inter","Segoe UI",system-ui,sans-serif';
  Chart.defaults.font.size = 11;
  Chart.defaults.color = '#5b6170';
  Chart.defaults.plugins.legend.labels.boxWidth = 10;
  Chart.defaults.maintainAspectRatio = false;
}
