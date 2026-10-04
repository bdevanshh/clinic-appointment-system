document.addEventListener('DOMContentLoaded', () => {
  const dateInputs = document.querySelectorAll('input[type="date"][data-min-today]');
  const today = new Date().toISOString().slice(0, 10);

  dateInputs.forEach((input) => {
    if (!input.min) {
      input.min = today;
    }
  });
});
