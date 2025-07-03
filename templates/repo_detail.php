<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0" id="repo-title">
            <i class="bi bi-spinner spinner-border spinner-border-sm"></i>
            Loading repository...
        </h4>
        <div>
            <a href="./?page=dashboard" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <button id="sync-btn" class="btn btn-sm btn-primary" disabled>
                <i class="bi bi-arrow-repeat"></i> Sync
            </button>
        </div>
    </div>
    <div class="card-body" id="repo-content">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 mb-0">Loading repository details...</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <!--h5 class="mb-0">Branches</h5-->


                <!-- Add this tab navigation to the card header -->
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="./?page=repo_detail&type=<?= $data['type'] ?>&name=<?= $data['name'] ?>">
                            <i class="bi bi-info-circle"></i> Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./?page=branch_tag_management&type=<?= $data['type'] ?>&name=<?= $data['name'] ?>">
                            <i class="bi bi-code-branch"></i> Branches & Tags
                        </a>
                    </li>
                </ul>               
            </div>
            <div class="card-body" id="branches-content">
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="ms-2">Loading branches...</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Recent Commits</h5>
            </div>
            <div class="card-body" id="commits-content">
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="ms-2">Loading commits...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const type = urlParams.get('type');
    const name = urlParams.get('name');
    
    if (!type || !name) {
        window.location.href = './?page=dashboard';
        return;
    }
    
    // Load repository details
    loadRepoDetails(type, name);
    
    // Setup sync button
    document.getElementById('sync-btn').addEventListener('click', function() {
        syncRepository(type, name);
    });
});

function loadRepoDetails(type, name) {
    const content = document.getElementById('repo-content');
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 mb-0">Loading repository details...</p>
        </div>
    `;

    fetch(`./api.php?action=get_details&type=${type}&name=${encodeURIComponent(name)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderRepoDetails(type, name, data.data);
                loadBranches(type, name);
                loadCommits(type, name);
            } else {
                showError('repo-content', data.message);
            }
        })
        .catch(error => {
            showError('repo-content', 'Failed to load repository details');
            console.error('Error loading repo details:', error);
        });
}

function renderRepoDetails(type, name, details) {
    // Update title
    document.getElementById('repo-title').innerHTML = `
        <i class="bi bi-${type === 'theme' ? 'palette' : 'puzzle'}"></i>
        ${escapeHtml(name)}
    `;
    
    // Enable sync button
    document.getElementById('sync-btn').disabled = false;
    
    // Render main content
    const content = document.getElementById('repo-content');
    content.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h5>Repository Info</h5>
                <table class="table table-sm">
                    <tr>
                        <th width="30%">Type</th>
                        <td>${escapeHtml(type)}</td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td>${escapeHtml(details.config?.desc || 'No description available')}</td>
                    </tr>
                    <tr>
                        <th>Repository URL</th>
                        <td>
                            ${details.config?.url ? `
                                <a href="${escapeHtml(details.config.url)}" target="_blank">
                                    ${escapeHtml(details.config.url)}
                                </a>
                            ` : 'Not specified'}
                        </td>
                    </tr>
                    <tr>
                        <th>Default Branch</th>
                        <td>${escapeHtml(details.config?.branch || 'main')}</td>
                    </tr>
                </table>
            </div>
            
            <div class="col-md-6">
                <h5>Installation Status</h5>
                ${details.installed ? `
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> Repository is installed
                    </div>
                    <table class="table table-sm">
                        <tr>
                            <th width="30%">Current Branch</th>
                            <td>${escapeHtml(details.details?.branch || 'unknown')}</td>
                        </tr>
                        <tr>
                            <th>Last Commit</th>
                            <td>${escapeHtml(details.details?.last_commit || 'unknown')}</td>
                        </tr>
                        <tr>
                            <th>Has Changes</th>
                            <td>
                                ${details.details?.has_changes ? `
                                    <span class="badge bg-warning text-dark">Uncommitted Changes</span>
                                ` : `
                                    <span class="badge bg-success">Clean</span>
                                `}
                            </td>
                        </tr>
                    </table>
                ` : `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Repository is not installed
                    </div>
                    ${details.details?.error ? `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-octagon"></i> 
                            ${escapeHtml(details.details.error)}
                        </div>
                    ` : `
                        <table class="table table-sm">
                            <tr>
                                <th width="30%">Available Version</th>
                                <td>${details.details?.version ? escapeHtml(details.details.version.substring(0, 7)) : 'unknown'}</td>
                            </tr>
                            <tr>
                                <th>Branch</th>
                                <td>${escapeHtml(details.details?.branch || details.config?.branch || 'main')}</td>
                            </tr>
                        </table>
                    `}
                `}
            </div>
        </div>
        <div id="repo-data" style="display:none">${JSON.stringify(details)}</div>
    `;
}

function loadBranches(type, name) {
    fetch(`./api.php?action=get_branches&type=${type}&name=${encodeURIComponent(name)}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('branches-content');
            
            if (data.success) {
                if (data.data.length === 0) {
                    container.innerHTML = '<div class="alert alert-info">No branches found</div>';
                    return;
                }
                
                let html = '<div class="list-group list-group-flush">';
                data.data.forEach(branch => {
                    html += `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span>${escapeHtml(branch)}</span>
                            <div>
                                <button class="btn btn-sm btn-outline-primary checkout-btn" data-branch="${escapeHtml(branch)}">
                                    Checkout
                                </button>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                
                container.innerHTML = html;
                
                // Add event listeners to checkout buttons
                document.querySelectorAll('.checkout-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        checkoutBranch(type, name, this.dataset.branch);
                    });
                });
            } else {
                container.innerHTML = `<div class="alert alert-danger">${escapeHtml(data.message)}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('branches-content').innerHTML = `
                <div class="alert alert-danger">Failed to load branches</div>
            `;
            console.error(error);
        });
}

function loadCommits(type, name) {
    const container = document.getElementById('commits-content');
    const repoDataElement = document.getElementById('repo-data');
    
    if (!repoDataElement) {
        container.innerHTML = '<div class="alert alert-danger">Failed to load commit data</div>';
        return;
    }

    try {
        const repoDetails = JSON.parse(repoDataElement.textContent);
        
        if (!repoDetails.history || repoDetails.history.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No commit history available</div>';
            return;
        }

        let html = `
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Commit</th>
                            <th>Message</th>
                            <th>Author</th>
                            <th>Date</th>
                            ${repoDetails.installed ? '<th>Actions</th>' : ''}
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        repoDetails.history.forEach(commit => {
            html += `
                <tr>
                    <td><code>${escapeHtml(commit.hash.substring(0, 7))}</code></td>
                    <td>${escapeHtml(commit.message)}</td>
                    <td>${escapeHtml(commit.author)}</td>
                    <td>${escapeHtml(commit.date)}</td>
                    ${repoDetails.installed ? `
                    <td>
                        <button class="btn btn-sm btn-outline-primary rollback-btn" 
                                data-hash="${escapeHtml(commit.hash)}">
                            Rollback
                        </button>
                    </td>
                    ` : ''}
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        container.innerHTML = html;
        
        // Add event listeners to rollback buttons
        document.querySelectorAll('.rollback-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                rollbackToCommit(type, name, this.dataset.hash);
            });
        });
    } catch (error) {
        console.error('Error loading commits:', error);
        container.innerHTML = '<div class="alert alert-danger">Failed to load commit history</div>';
    }
}

function rollbackToCommit(type, name, hash) {
    if (!confirm(`Are you sure you want to rollback to commit ${hash.substring(0, 7)}?`)) {
        return;
    }
    
    fetch(`./api.php?action=rollback&type=${type}&name=${encodeURIComponent(name)}&commit_hash=${encodeURIComponent(hash)}`, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', `Rolled back to commit ${hash.substring(0, 7)} successfully`);
            loadRepoDetails(type, name);
        } else {
            showToast('danger', data.message);
        }
    })
    .catch(error => {
        showToast('danger', 'Failed to rollback commit');
        console.error(error);
    });
}

function syncRepository(type, name) {
    const btn = document.getElementById('sync-btn');
    const originalHtml = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Syncing...
    `;
    
    fetch(`./api.php?action=sync&type=${type}&name=${encodeURIComponent(name)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.data.message);
                loadRepoDetails(type, name);
            } else {
                showToast('danger', data.message);
            }
        })
        .catch(error => {
            showToast('danger', 'Failed to sync repository');
            console.error(error);
        })
        .finally(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
}

function checkoutBranch(type, name, branch) {
    // This would call a new API endpoint to checkout a branch
    showToast('info', `Checking out branch ${branch}...`);
    console.log(`Would checkout branch ${branch} for ${type}/${name}`);
    
    // In a real implementation, we would:
    // 1. Call API to checkout branch
    // 2. Show loading state
    // 3. Reload details on success
    // 4. Show error on failure
}

// Helper functions (same as dashboard.php)
function showError(containerId, message) { /* ... */ }
function showToast(type, message) { /* ... */ }
function escapeHtml(unsafe) { return unsafe; }
</script>