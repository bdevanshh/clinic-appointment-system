document.addEventListener('DOMContentLoaded', () => {
  // Date inputs cannot select a past appointment.
  const today = new Date();
  const localToday = [
    today.getFullYear(),
    String(today.getMonth() + 1).padStart(2, '0'),
    String(today.getDate()).padStart(2, '0'),
  ].join('-');

  document.querySelectorAll('input[type="date"][data-min-today]').forEach((input) => {
    if (!input.min) {
      input.min = localToday;
    }
  });

  // Show the sideways-scroll cue only while the table actually overflows,
  // so the hint never promises a scrollbar that is not there.
  const tables = document.querySelectorAll('.table-shell');

  const syncScrollHint = (shell) => {
    const scroller = shell.querySelector('.table-scroll');
    if (!scroller) return;
    const overflowing = scroller.scrollWidth - scroller.clientWidth > 1;
    shell.dataset.scrollable = overflowing ? 'true' : 'false';
  };

  tables.forEach((shell) => {
    syncScrollHint(shell);
    const scroller = shell.querySelector('.table-scroll');
    if (!scroller) return;
    scroller.addEventListener('scroll', () => syncScrollHint(shell), { passive: true });
  });

  let resizeTimer;
  window.addEventListener('resize', () => {
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(() => {
      tables.forEach(syncScrollHint);
    }, 100);
  });
});
