<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-palette"></i> Themes</h5>
                <button id="sync-themes" class="btn btn-sm btn-light">
                    <i class="bi bi-arrow-repeat"></i> Sync All
                </button>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="themes-list">
                    <div class="list-group-item text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 mb-0">Loading themes...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-puzzle"></i> Modules</h5>
                <button id="sync-modules" class="btn btn-sm btn-light">
                    <i class="bi bi-arrow-repeat"></i> Sync All
                </button>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="modules-list">
                    <div class="list-group-item text-center py-5">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 mb-0">Loading modules...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load repositories
    loadRepositories();
    
    // Setup sync buttons
    document.getElementById('sync-themes').addEventListener('click', function() {
        syncAll('themes');
    });
    
    document.getElementById('sync-modules').addEventListener('click', function() {
        syncAll('modules');
    });
});

function loadRepositories() {
    fetch('./api.php?action=get_repos')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderRepositories('themes', data.data.themes);
                renderRepositories('modules', data.data.modules);
            } else {
                showError('themes-list', data.message);
                showError('modules-list', data.message);
            }
        })
        .catch(error => {
            showError('themes-list', 'Failed to load repositories');
            showError('modules-list', 'Failed to load repositories');
            console.error(error);
        });
}

function renderRepositories(type, repos) {
    const container = document.getElementById(`${type}-list`);
    container.innerHTML = '';
    
    if (!repos || Object.keys(repos).length === 0) {
        container.innerHTML = `
            <div class="list-group-item text-danger">
                No ${type} configured
            </div>
        `;
        return;
    }
    
    // Get installed status
    fetch('./api.php?action=get_installed')
        .then(response => response.json())
        .then(installedData => {
            if (!installedData.success) {
                throw new Error(installedData.message);
            }
            
            Object.keys(repos).forEach(name => {
                const repo = repos[name];
                const isInstalled = installedData.data[type] && installedData.data[type][name];
                const installedInfo = isInstalled ? installedData.data[type][name] : null;
                
                const item = document.createElement('a');
                item.href = `./index.php?page=repo_detail&type=${type}&name=${encodeURIComponent(name)}`;
                item.className = 'list-group-item list-group-item-action';
                item.innerHTML = `
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">
                            <i class="bi bi-folder"></i> ${escapeHtml(name)}
                            ${isInstalled ? 
                                '<span class="badge bg-success ms-2">Installed</span>' : 
                                '<span class="badge bg-secondary ms-2">Not Installed</span>'}
                        </h6>
                        <small class="text-muted">${escapeHtml(repo.branch || 'main')}</small>
                    </div>
                    <p class="mb-1 text-muted">${escapeHtml(repo.desc || 'No description')}</p>
                    ${isInstalled && installedInfo ? `
                    <small class="text-muted d-block mt-1">
                        <i class="bi bi-git"></i> ${escapeHtml(installedInfo.version ? installedInfo.version.substring(0, 7) : '')}
                        (${escapeHtml(installedInfo.branch || '')})
                    </small>
                    ` : ''}
                `;
                container.appendChild(item);
            });
        })
        .catch(error => {
            showError(container.id, 'Failed to load installation status');
            console.error(error);
        });
}

function syncAll(type) {
    const container = document.getElementById(`${type}-list`);
    const syncButton = document.getElementById(`sync-${type}`);
    const originalButtonHTML = syncButton.innerHTML;
    
    // Disable button and show loading state
    syncButton.disabled = true;
    syncButton.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Syncing...
    `;
    
    // Show loading in container
    const loadingHTML = `
        <div class="list-group-item text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Syncing...</span>
            </div>
            <p class="mt-2 mb-0">Syncing all ${type}...</p>
        </div>
    `;
    
    container.innerHTML = loadingHTML;
    
    fetch(`./api.php?action=sync_all&type=${type}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Unknown error during sync');
            }
            
            // Clear loading state
            container.innerHTML = '';
            
            let allSuccess = true;
            let hasFailures = false;
            
            // Process each result
            Object.entries(data.data).forEach(([name, result]) => {
                const success = result.success === true;
                allSuccess = allSuccess && success;
                hasFailures = hasFailures || !success;
                
                const item = document.createElement('div');
                item.className = `list-group-item list-group-item-${success ? 'success' : 'danger'}`;
                item.innerHTML = `
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${escapeHtml(name)}</h6>
                        <span class="badge bg-${success ? 'success' : 'danger'}">
                            ${success ? 'Success' : 'Failed'}
                        </span>
                    </div>
                    <p class="mb-1">${escapeHtml(result.message)}</p>
                    ${success && result.commit ? `
                    <small class="text-muted">
                        Commit: ${escapeHtml(result.commit.substring(0, 7))}
                    </small>
                    ` : ''}
                `;
                container.appendChild(item);
            });
            
            // Add refresh button
            const refreshItem = document.createElement('div');
            refreshItem.className = 'list-group-item';
            refreshItem.innerHTML = `
                <button class="btn btn-primary w-100" onclick="refreshRepositoryList('${type}')">
                    <i class="bi bi-arrow-repeat"></i> Refresh List
                </button>
            `;
            container.appendChild(refreshItem);
            
            // Show appropriate toast message
            if (allSuccess) {
                showToast('success', `All ${type} synced successfully!`);
            } else if (hasFailures) {
                showToast('warning', `Some ${type} failed to sync`);
            }
        })
        .catch(error => {
            console.error('Sync error:', error);
            container.innerHTML = `
                <div class="list-group-item text-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    ${escapeHtml(error.message)}
                </div>
                <div class="list-group-item">
                    <button class="btn btn-secondary" onclick="loadRepositories()">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </button>
                </div>
            `;
            showToast('danger', 'Failed to sync repositories');
        })
        .finally(() => {
            // Restore button state
            syncButton.innerHTML = originalButtonHTML;
            syncButton.disabled = false;
        });
}

function showError(containerId, message) {
    const container = document.getElementById(containerId);
    container.innerHTML = `
        <div class="list-group-item text-danger">
            ${escapeHtml(message)}
        </div>
    `;
}

function refreshRepositoryList(type) {
    const container = document.getElementById(`${type}-list`);
    const originalContent = container.innerHTML;
    
    container.innerHTML = `
        <div class="list-group-item text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Refreshing...</span>
            </div>
            <p class="mt-2 mb-0">Refreshing ${type} list...</p>
        </div>
    `;
    
    fetch('./api.php?action=get_repos')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderRepositories(type, data.data[type]);
                showToast('success', `${type} list refreshed`);
            } else {
                container.innerHTML = originalContent;
                showToast('danger', data.message || 'Failed to refresh list');
            }
        })
        .catch(error => {
            container.innerHTML = originalContent;
            showToast('danger', 'Failed to refresh repository list');
            console.error(error);
        });
}

function showToast(type, message) {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '11';
        document.body.appendChild(container);
    }
    
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = `toast show align-items-center text-white bg-${type} border-0`;
    toast.role = 'alert';
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${escapeHtml(message)}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    document.getElementById('toast-container').appendChild(toast);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        const toastElement = document.getElementById(toastId);
        if (toastElement) {
            toastElement.remove();
        }
    }, 5000);
}

function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>