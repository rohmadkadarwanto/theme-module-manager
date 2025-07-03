<div class="card">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="bi bi-arrow-repeat"></i> <?= htmlspecialchars($title) ?></h5>
    </div>
    <div class="card-body">
        <div class="list-group" id="sync-results">
            <?php foreach ($results as $result): ?>
            <div class="list-group-item <?= $result['success'] ? 'list-group-item-success' : 'list-group-item-danger' ?>">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">
                        <?= ucfirst($result['type']) ?>: <?= $result['name'] ?>
                    </h6>
                    <span class="badge bg-<?= $result['success'] ? 'success' : 'danger' ?>">
                        <?= $result['success'] ? 'Success' : 'Failed' ?>
                    </span>
                </div>
                <p class="mb-1"><?= htmlspecialchars($result['message']) ?></p>
                <?php if ($result['success']): ?>
                <small class="text-muted">
                    Status: <?= $result['status'] ?><br>
                    Commit: <?= substr($result['commit'], 0, 7) ?>
                </small>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-3">
            <a href="/?page=dashboard" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>