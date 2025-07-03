<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-code-branch"></i> Branches</h5>
                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#createBranchModal">
                    <i class="bi bi-plus-circle"></i> New Branch
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover" id="branchesTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="branchesList">
                            <tr>
                                <td colspan="2" class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="ms-2">Loading branches...</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-tag"></i> Tags</h5>
                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#createTagModal">
                    <i class="bi bi-plus-circle"></i> New Tag
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover" id="tagsTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tagsList">
                            <tr>
                                <td colspan="2" class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="ms-2">Loading tags...</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Branch Modal -->
<div class="modal fade" id="createBranchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Branch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createBranchForm">
                    <div class="mb-3">
                        <label for="branchName" class="form-label">Branch Name</label>
                        <input type="text" class="form-control" id="branchName" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="checkoutBranch">
                        <label class="form-check-label" for="checkoutBranch">Checkout after creation</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="createBranchBtn">Create</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Tag Modal -->
<div class="modal fade" id="createTagModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Tag</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createTagForm">
                    <div class="mb-3">
                        <label for="tagName" class="form-label">Tag Name</label>
                        <input type="text" class="form-control" id="tagName" required>
                    </div>
                    <div class="mb-3">
                        <label for="tagMessage" class="form-label">Message (optional)</label>
                        <textarea class="form-control" id="tagMessage" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="createTagBtn">Create</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="deleteConfirmMessage">Are you sure you want to delete this item?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>


<!-- Add this modal after the existing modals -->
<!-- Push Modal -->
<div class="modal fade" id="pushModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Push Changes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="pushForm">
                    <div class="mb-3">
                        <label for="pushRemote" class="form-label">Remote</label>
                        <select class="form-select" id="pushRemote" required>
                            <option value="origin" selected>origin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="pushBranch" class="form-label">Branch</label>
                        <select class="form-select" id="pushBranch" required>
                            <!-- Will be populated by JavaScript -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="forcePush">
                            <label class="form-check-label" for="forcePush">Force push</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="pushBtn">Push</button>
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
    
    // Load initial data
    loadBranches(type, name);
    loadTags(type, name);
    
    // Setup create branch button
    document.getElementById('createBranchBtn').addEventListener('click', function() {
        createBranch(type, name);
    });
    
    // Setup create tag button
    document.getElementById('createTagBtn').addEventListener('click', function() {
        createTag(type, name);
    });
    
    // Setup delete confirmation
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        const action = this.dataset.action;
        const itemName = this.dataset.itemName;
        
        if (action === 'branch') {
            deleteBranch(type, name, itemName);
        } else if (action === 'tag') {
            deleteTag(type, name, itemName);
        }
    });

    document.getElementById('pushBtn').addEventListener('click', function() {
        pushChanges(type, name);
    });
});

function pushChanges(type, name) {
    const remote = document.getElementById('pushRemote').value;
    const branch = document.getElementById('pushBranch').value;
    const force = document.getElementById('forcePush').checked;
    
    const btn = document.getElementById('pushBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Pushing...
    `;
    
    const formData = new FormData();
    formData.append('remote', remote);
    formData.append('branch', branch);
    
    if (force) {
        formData.append('options[]', '--force');
    }
    
    fetch(`/api.php?action=push&type=${type}&name=${encodeURIComponent(name)}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.data.message);
            $('#pushModal').modal('hide');
        } else {
            showToast('danger', data.message);
        }
    })
    .catch(error => {
        showToast('danger', 'Failed to push changes');
        console.error(error);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function loadBranches(type, name) {
    const container = document.getElementById('branchesList');
    
    fetch(`./api.php?action=get_branches&type=${type}&name=${encodeURIComponent(name)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderBranches(data.data, type, name);
            } else {
                container.innerHTML = `
                    <tr>
                        <td colspan="2" class="text-danger">
                            <i class="bi bi-exclamation-triangle"></i> ${escapeHtml(data.message)}
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            container.innerHTML = `
                <tr>
                    <td colspan="2" class="text-danger">
                        <i class="bi bi-exclamation-triangle"></i> Failed to load branches
                    </td>
                </tr>
            `;
            console.error(error);
        });
}

function renderBranches(branches, type, name) {
    const container = document.getElementById('branchesList');
    container.innerHTML = '';
    
    if (!branches || branches.length === 0) {
        container.innerHTML = `
            <tr>
                <td colspan="2" class="text-muted">No branches found</td>
            </tr>
        `;
        return;
    }
    
    branches.forEach(branch => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                ${escapeHtml(branch)}
                ${branch === 'master' || branch === 'main' ? `
                    <span class="badge bg-primary ms-2">Default</span>
                ` : ''}
            </td>
            <td class="text-end">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary checkout-btn" data-branch="${escapeHtml(branch)}">
                        <i class="bi bi-arrow-left-right"></i> Checkout
                    </button>
                    <button class="btn btn-outline-success push-btn" 
                            data-branch="${escapeHtml(branch)}"
                            data-bs-toggle="modal" 
                            data-bs-target="#pushModal">
                        <i class="bi bi-upload"></i> Push
                    </button>
                    ${branch !== 'master' && branch !== 'main' ? `
                    <button class="btn btn-outline-danger delete-btn" 
                            data-action="branch" 
                            data-item-name="${escapeHtml(branch)}">
                        <i class="bi bi-trash"></i>
                    </button>
                    ` : ''}
                </div>
            </td>
        `;
        container.appendChild(row);
    });
    
    // Add event listeners
    document.querySelectorAll('.checkout-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            checkoutBranch(type, name, this.dataset.branch);
        });
    });
    
    document.querySelectorAll('.push-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            preparePushModal(this.dataset.branch);
        });
    });
    
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            showDeleteConfirm(
                this.dataset.action,
                this.dataset.itemName,
                `Are you sure you want to delete branch "${this.dataset.itemName}"?`
            );
        });
    });
}

function preparePushModal(branch) {
    document.getElementById('pushBranch').innerHTML = `
        <option value="${escapeHtml(branch)}" selected>${escapeHtml(branch)}</option>
    `;
    document.getElementById('forcePush').checked = false;
}

function loadTags(type, name) {
    const container = document.getElementById('tagsList');
    
    fetch(`./api.php?action=get_tags&type=${type}&name=${encodeURIComponent(name)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderTags(data.data, type, name);
            } else {
                container.innerHTML = `
                    <tr>
                        <td colspan="2" class="text-danger">
                            <i class="bi bi-exclamation-triangle"></i> ${escapeHtml(data.message)}
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            container.innerHTML = `
                <tr>
                    <td colspan="2" class="text-danger">
                        <i class="bi bi-exclamation-triangle"></i> Failed to load tags
                    </td>
                </tr>
            `;
            console.error(error);
        });
}

function renderTags(tags, type, name) {
    const container = document.getElementById('tagsList');
    container.innerHTML = '';
    
    if (!tags || tags.length === 0) {
        container.innerHTML = `
            <tr>
                <td colspan="2" class="text-muted">No tags found</td>
            </tr>
        `;
        return;
    }
    
    tags.forEach(tag => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${escapeHtml(tag)}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-danger delete-btn" 
                        data-action="tag" 
                        data-item-name="${escapeHtml(tag)}">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        container.appendChild(row);
    });
    
    // Add event listeners
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            showDeleteConfirm(
                this.dataset.action,
                this.dataset.itemName,
                `Are you sure you want to delete tag "${this.dataset.itemName}"?`
            );
        });
    });
}

function createBranch(type, name) {
    const branchName = document.getElementById('branchName').value.trim();
    const checkout = document.getElementById('checkoutBranch').checked;
    
    if (!branchName) {
        showToast('danger', 'Branch name is required');
        return;
    }
    
    const btn = document.getElementById('createBranchBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Creating...
    `;
    
    const formData = new FormData();
    formData.append('branch_name', branchName);
    formData.append('checkout', checkout);
    
    fetch(`./api.php?action=create_branch&type=${type}&name=${encodeURIComponent(name)}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.data.message);
            loadBranches(type, name);
            $('#createBranchModal').modal('hide');
            document.getElementById('createBranchForm').reset();
        } else {
            showToast('danger', data.message);
        }
    })
    .catch(error => {
        showToast('danger', 'Failed to create branch');
        console.error(error);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function createTag(type, name) {
    const tagName = document.getElementById('tagName').value.trim();
    const message = document.getElementById('tagMessage').value.trim();
    
    if (!tagName) {
        showToast('danger', 'Tag name is required');
        return;
    }
    
    const btn = document.getElementById('createTagBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Creating...
    `;
    
    const formData = new FormData();
    formData.append('tag_name', tagName);
    if (message) formData.append('message', message);
    
    fetch(`./api.php?action=create_tag&type=${type}&name=${encodeURIComponent(name)}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.data.message);
            loadTags(type, name);
            $('#createTagModal').modal('hide');
            document.getElementById('createTagForm').reset();
        } else {
            showToast('danger', data.message);
        }
    })
    .catch(error => {
        showToast('danger', 'Failed to create tag');
        console.error(error);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function deleteBranch(type, name, branchName) {
    const formData = new FormData();
    formData.append('branch_name', branchName);
    
    fetch(`./api.php?action=delete_branch&type=${type}&name=${encodeURIComponent(name)}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.data.message);
            loadBranches(type, name);
        } else {
            showToast('danger', data.message);
        }
    })
    .catch(error => {
        showToast('danger', 'Failed to delete branch');
        console.error(error);
    })
    .finally(() => {
        $('#deleteConfirmModal').modal('hide');
    });
}

function deleteTag(type, name, tagName) {
    const formData = new FormData();
    formData.append('tag_name', tagName);
    
    fetch(`./api.php?action=delete_tag&type=${type}&name=${encodeURIComponent(name)}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.data.message);
            loadTags(type, name);
        } else {
            showToast('danger', data.message);
        }
    })
    .catch(error => {
        showToast('danger', 'Failed to delete tag');
        console.error(error);
    })
    .finally(() => {
        $('#deleteConfirmModal').modal('hide');
    });
}

function checkoutBranch(type, name, branch) {
    if (!confirm(`Are you sure you want to checkout branch "${branch}"?`)) {
        return;
    }
    
    showToast('info', `Checking out branch ${branch}...`);
    
    fetch(`./api.php?action=checkout&type=${type}&name=${encodeURIComponent(name)}&branch=${encodeURIComponent(branch)}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', `Successfully checked out branch ${branch}`);
            loadBranches(type, name);
        } else {
            showToast('danger', data.message);
        }
    })
    .catch(error => {
        showToast('danger', 'Failed to checkout branch');
        console.error(error);
    });
}

function showDeleteConfirm(action, itemName, message) {
    const modal = document.getElementById('deleteConfirmModal');
    const btn = document.getElementById('confirmDeleteBtn');
    
    document.getElementById('deleteConfirmMessage').textContent = message;
    btn.dataset.action = action;
    btn.dataset.itemName = itemName;
    
    $(modal).modal('show');
}

// Helper functions
function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = `toast show align-items-center text-white bg-${type} border-0`;
    toast.role = 'alert';
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${escapeHtml(message)}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    const container = document.getElementById('toast-container') || createToastContainer();
    container.appendChild(toast);
    
    setTimeout(() => toast.remove(), 5000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '11';
    document.body.appendChild(container);
    return container;
}

function escapeHtml(unsafe) {
    return unsafe.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>