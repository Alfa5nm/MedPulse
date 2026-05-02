// Future Custom JS interactions can go here
document.addEventListener('DOMContentLoaded', () => {
    // Basic tooltips initialization if needed
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
});
