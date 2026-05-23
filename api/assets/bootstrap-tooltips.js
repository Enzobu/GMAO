function initTooltips() {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
    bootstrap.Tooltip.getOrCreateInstance(el);
  });
}

document.addEventListener('DOMContentLoaded', initTooltips);
document.addEventListener('turbo:load', initTooltips);
document.addEventListener('turbo:render', initTooltips);

document.addEventListener('turbo:before-cache', () => {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
    const instance = bootstrap.Tooltip.getInstance(el);
    if (instance) instance.dispose();
  });
});