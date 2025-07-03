document.addEventListener('DOMContentLoaded', function() {
    // Confirm before sync actions
    document.querySelectorAll('[data-action="sync"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to sync this repository?')) {
                e.preventDefault();
            }
        });
    });
    
    // Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});