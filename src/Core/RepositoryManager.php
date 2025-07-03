<?php
namespace App\Core;

use App\Services\GitService;
use Exception;

class RepositoryManager
{
    private $gitService;
    private $basePath;
    private $repos;

    public function __construct()
    {
        $this->gitService = new GitService();
        $this->basePath = realpath(__DIR__ . '/../../');
        $this->loadReposConfig();
    }

    private function loadReposConfig(): void
    {
        $configFile = $this->basePath . '/config/repos.php';
        if (!file_exists($configFile)) {
            throw new Exception("Repository configuration file not found");
        }
        
        $this->repos = require $configFile;
    }

    private function getTempDir(string $url, string $branch): string
    {
        $tempDir = sys_get_temp_dir() . '/git_temp_' . md5($url . $branch);
        if (is_dir($tempDir)) {
            $this->removeDirectory($tempDir);
        }
        return $tempDir;
    }

    public function getRepos(): array
    {
        return [
            'themes' => $this->repos['themes'] ?? [],
            'modules' => $this->repos['modules'] ?? []
        ];
    }

    public function syncRepo(string $type, string $name): array
    {
        $type = strtolower($type);
        $type = str_ends_with($type, 's') ? $type : $type . 's';
    
        $repoConfig = $this->repos[$type][$name] ?? null;
        
        if (!$repoConfig) {
            throw new Exception("Repository configuration not found for $type/$name");
        }
    
        $this->validateRepositoryConfig($repoConfig);
    
        $result = [
            'type' => $type,
            'name' => $name,
            'status' => '',
            'message' => '',
            'success' => false,
            'commit' => null
        ];
    
        $targetDir = $this->getRepoPath($type, $name);
        $repoToken = $repoConfig['token'] ?? null;
    
        try {
            if (!is_dir($targetDir)) {
                // New clone
                $result = $this->cloneNewRepo(
                    $repoConfig['url'],
                    $targetDir,
                    $repoConfig['branch'],
                    $result,
                    $repoToken
                );
            } elseif (!is_dir($targetDir . '/.git')) {
                // Invalid repo, remove and reclone
                $this->removeDirectory($targetDir);
                $result = $this->cloneNewRepo(
                    $repoConfig['url'],
                    $targetDir,
                    $repoConfig['branch'],
                    $result,
                    $repoToken
                );
            } else {
                // Existing repo - update
                $result = $this->updateExistingRepo($targetDir, $repoConfig, $result);
            }
        } catch (Exception $e) {
            error_log("Sync failed for $type/$name: " . $e->getMessage());
            
            // Clean up if failed during clone
            if (isset($targetDir) && is_dir($targetDir) && !is_dir($targetDir . '/.git')) {
                try {
                    $this->removeDirectory($targetDir);
                } catch (Exception $cleanupError) {
                    error_log("Cleanup failed: " . $cleanupError->getMessage());
                }
            }
            
            $result['message'] = $e->getMessage();
        }
    
        return $result;
    }

    private function validateRepositoryConfig(array $config): void
    {
        if (empty($config['url'])) {
            throw new Exception("Repository URL is required");
        }
        
        if (!filter_var($config['url'], FILTER_VALIDATE_URL)) {
            throw new Exception("Invalid repository URL format");
        }
        
        if (empty($config['branch'])) {
            throw new Exception("Repository branch is required");
        }
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0775, true)) {
                throw new Exception("Failed to create directory: $path");
            }
            chmod($path, 0775);
        }
        
        if (!is_writable($path)) {
            throw new Exception("Directory is not writable: $path");
        }
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        rmdir($path);
    }

    public function isUpdateAvailable(string $type, string $name): bool
    {
        $localPath = $this->getRepoPath($type, $name);
        $repoConfig = $this->repos[$type][$name] ?? null;

        if (!$repoConfig || !is_dir($localPath)) {
            return false;
        }

        try {
            $localRepo = $this->gitService->openRepository($localPath);
            $localCommit = (string) $localRepo->getLastCommitId();

            $remoteInfo = $this->getRemoteRepoInfo(
                $repoConfig['url'],
                $repoConfig['branch'],
                $repoConfig['token'] ?? null
            );
            
            return $localCommit !== ($remoteInfo['version'] ?? null);
        } catch (Exception $e) {
            return false;
        }
    }

    private function updateExistingRepo(string $path, array $config, array $result): array
    {
        try {
            $repo = $this->gitService->openRepository($path);
            
            $repo->fetch('origin');
            $repo->execute('reset', '--hard', 'origin/' . $config['branch']);
            
            $result['status'] = 'updated';
            $result['message'] = "Repository updated successfully";
            $result['commit'] = (string) $repo->getLastCommitId();
            $result['success'] = true;
            
        } catch (Exception $e) {
            throw new Exception("Failed to update repository: " . $e->getMessage());
        }
        
        return $result;
    }

    private function cloneNewRepo(string $url, string $path, string $branch, array $result, ?string $repoToken = null): array
    {
        try {
            $this->ensureDirectoryExists(dirname($path));

            $repo = $this->gitService->cloneRepository(
                $url, 
                $path, 
                ['--branch' => $branch],
                $repoToken
            );

            if (!is_dir($path . '/.git')) {
                throw new Exception(".git directory not detected after clone");
            }

            $result['status'] = 'cloned';
            $result['message'] = "Repository cloned successfully";
            $result['commit'] = (string) $repo->getLastCommitId();
            $result['success'] = true;

        } catch (Exception $e) {
            if (is_dir($path)) {
                $this->removeDirectory($path);
            }
            throw new Exception("Clone failed: " . $e->getMessage());
        }
        
        return $result;
    }

    public function listInstalled(): array
    {
        $installed = ['themes' => [], 'modules' => []];

        foreach (['themes', 'modules'] as $type) {
            $dir = $this->basePath . "/repository/{$type}";
            if (is_dir($dir)) {
                $installed[$type] = $this->scanRepoDirectory($dir);
            }
        }

        return $installed;
    }

    private function scanRepoDirectory(string $dir): array
    {
        $repos = [];
        foreach (scandir($dir) as $item) {
            if ($this->isValidRepoItem($dir, $item)) {
                $repos[$item] = $this->getRepoInfo("$dir/$item");
            }
        }
        return $repos;
    }

    private function isValidRepoItem(string $dir, string $item): bool
    {
        return $item !== '.' && 
               $item !== '..' && 
               is_dir("$dir/$item/.git");
    }

    private function getRemoteRepoInfo(string $url, string $branch, ?string $token = null): array
    {
        $tempDir = $this->getTempDir($url, $branch);
        
        try {
            $repo = $this->gitService->cloneRepository(
                $url,
                $tempDir,
                ['--branch' => $branch, '--depth' => '1'],
                $token
            );
            
            $info = [
                'version' => (string) $repo->getLastCommitId(),
                'branch' => $repo->getCurrentBranchName(),
                'has_changes' => false,
                'history' => $this->getCommitHistoryFromRepo($repo, 5) // Get recent commits
            ];
            
            return $info;
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'version' => 'unknown',
                'branch' => $branch
            ];
        } finally {
            if (is_dir($tempDir)) {
                $this->removeDirectory($tempDir);
            }
        }
    }

    private function getCommitHistoryFromRepo($repo, int $limit = 10): array
    {
        $history = [];
        $commits = $repo->execute('log', "--pretty=format:%h|%s|%an|%ad", "--date=short", "-n", (string)$limit);
        
        $lines = is_array($commits) ? $commits : explode("\n", $commits);
        
        foreach ($lines as $line) {
            if (!empty($line)) {
                $parts = explode('|', $line, 4);
                if (count($parts) === 4) {
                    [$hash, $message, $author, $date] = $parts;
                    $history[] = [
                        'hash' => $hash,
                        'message' => $message,
                        'author' => $author,
                        'date' => $date
                    ];
                }
            }
        }
        
        return $history;
    }

    public function getRepoDetails(string $type, string $name): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $repoConfig = $this->repos[$type][$name] ?? null;
        
        $details = [
            'config' => $repoConfig,
            'installed' => is_dir($targetDir),
            'details' => [],
            'branches' => [],
            'tags' => [],
            'history' => [],
            'error' => null
        ];
    
        if ($details['installed']) {
            $details = $this->loadRepoDetails($targetDir, $details);
            $details['history'] = $this->getCommitHistory($type, $name, 5);
        } elseif ($repoConfig) {
            $remoteInfo = $this->getRemoteRepoInfo(
                $repoConfig['url'],
                $repoConfig['branch'],
                $repoConfig['token'] ?? null
            );
            $details['details'] = $remoteInfo;
            $details['history'] = $remoteInfo['history'] ?? [];
        }
    
        return $details;
    }

    private function loadRepoDetails(string $path, array $details): array
    {
        try {
            $repo = $this->gitService->openRepository($path);
            
            $details['installed'] = true;
            $details['details'] = [
                'branch' => $repo->getCurrentBranchName(),
                'last_commit' => (string) $repo->getLastCommitId(),
                'has_changes' => $repo->hasChanges()
            ];
            
            $details['branches'] = $repo->getBranches();
            $details['tags'] = $repo->getTags();
            
        } catch (Exception $e) {
            $details['error'] = $e->getMessage();
        }

        return $details;
    }

    private function getRepoInfo(string $path, ?string $remoteUrl = null, ?string $branch = null, ?string $token = null): array {
        if (is_dir($path)) {
            try {
                $repo = $this->gitService->openRepository($path);
                return [
                    'version' => (string) $repo->getLastCommitId(),
                    'branch' => $repo->getCurrentBranchName(),
                    'has_changes' => $repo->hasChanges()
                ];
            } catch (Exception $e) {
                // Fall through to remote check
            }
        }
        
        if ($remoteUrl && $branch) {
            return $this->getRemoteRepoInfo($remoteUrl, $branch, $token);
        }
        
        return ['error' => 'Repository not available'];
    }

    private function getRepoPath(string $type, string $name): string
    {
        $type = strtolower($type);
        $dirName = str_ends_with($type, 's') ? $type : $type . 's';
        return $this->basePath . "/repository/{$dirName}/" . trim($name, '/');
    }

    public function getCommitHistory(string $type, string $name, int $limit = 10): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $history = [];

        try {
            if (!is_dir($targetDir)) {
                return [];
            }

            $repo = $this->gitService->openRepository($targetDir);
            $output = $repo->execute('log', "--pretty=format:%h|%s|%an|%ad", "--date=short", "-n", (string)$limit);
            
            $lines = is_array($output) ? $output : explode("\n", $output);
            
            foreach ($lines as $line) {
                if (!empty($line)) {
                    $parts = explode('|', $line, 4);
                    if (count($parts) === 4) {
                        [$hash, $message, $author, $date] = $parts;
                        $history[] = [
                            'hash' => $hash,
                            'message' => $message,
                            'author' => $author,
                            'date' => $date
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error getting commit history: " . $e->getMessage());
            return [];
        }

        return $history;
    }

    public function rollback(string $type, string $name, string $commitHash): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => '',
            'new_commit' => null
        ];
    
        try {
            $repo = $this->gitService->openRepository($targetDir);
            
            // Reset to the specific commit
            $repo->execute('reset', '--hard', $commitHash);
            
            // Get the new commit ID after reset
            $result['new_commit'] = (string) $repo->getLastCommitId();
            $result['success'] = true;
            $result['message'] = "Successfully rolled back to commit " . substr($commitHash, 0, 7);
            
        } catch (Exception $e) {
            $result['message'] = "Rollback failed: " . $e->getMessage();
        }
    
        return $result;
    }

    public function createBranch(string $type, string $name, string $branchName, bool $checkout = false): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            $repo->createBranch($branchName, $checkout);
            
            $result['success'] = true;
            $result['message'] = "Branch $branchName created" . ($checkout ? " and checked out" : "");
                               
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    public function createTag(string $type, string $name, string $tagName, string $message = null): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            
            $options = $message ? ['-m' => $message] : [];
            $repo->createTag($tagName, $options);
            
            $result['success'] = true;
            $result['message'] = "Tag $tagName created";
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }



    /**
     * Create a new file in repository and commit it
     */
    public function createFile(string $type, string $name, string $filename, string $content, string $commitMessage): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            $fullPath = $targetDir . '/' . ltrim($filename, '/');
            
            if (!file_put_contents($fullPath, $content)) {
                throw new Exception("Failed to create file");
            }
            
            $repo->addFile($fullPath);
            $repo->commit($commitMessage);
            
            $result['success'] = true;
            $result['message'] = "File created and committed successfully";
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Initialize a new empty repository
     */
    public function initRepository(string $type, string $name, array $options = []): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $this->ensureDirectoryExists(dirname($targetDir));
            $this->gitService->initRepository($targetDir, $options);
            
            $result['success'] = true;
            $result['message'] = "Repository initialized successfully";
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Get repository branches
     */
    public function getBranches(string $type, string $name, bool $localOnly = false): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        
        try {
            $repo = $this->gitService->openRepository($targetDir);
            return $localOnly ? $repo->getLocalBranches() : $repo->getBranches();
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get current branch name
     */
    public function getCurrentBranch(string $type, string $name): string
    {
        $targetDir = $this->getRepoPath($type, $name);
        
        try {
            $repo = $this->gitService->openRepository($targetDir);
            return $repo->getCurrentBranchName();
        } catch (Exception $e) {
            return 'unknown';
        }
    }

    /**
     * Remove a branch
     */
    public function removeBranch(string $type, string $name, string $branchName): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            $repo->removeBranch($branchName);
            
            $result['success'] = true;
            $result['message'] = "Branch $branchName removed successfully";
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Get repository tags
     */
    public function getTags(string $type, string $name): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        
        try {
            $repo = $this->gitService->openRepository($targetDir);
            return $repo->getTags();
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }


    /**
     * Rename a tag
     */
    public function renameTag(string $type, string $name, string $oldTag, string $newTag): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            $repo->renameTag($oldTag, $newTag);
            
            $result['success'] = true;
            $result['message'] = "Tag renamed from $oldTag to $newTag";
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Remove a tag
     */
    public function removeTag(string $type, string $name, string $tagName): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            $repo->removeTag($tagName);
            
            $result['success'] = true;
            $result['message'] = "Tag $tagName removed successfully";
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Get commit details
     */
    public function getCommit(string $type, string $name, string $commitHash): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        
        try {
            $repo = $this->gitService->openRepository($targetDir);
            $commit = $repo->getCommit($commitHash);
            
            return [
                'id' => (string) $commit->getId(),
                'subject' => $commit->getSubject(),
                'body' => $commit->getBody(),
                'author_name' => $commit->getAuthorName(),
                'author_email' => $commit->getAuthorEmail(),
                'author_date' => $commit->getAuthorDate(),
                'committer_name' => $commit->getCommitterName(),
                'committer_email' => $commit->getCommitterEmail(),
                'committer_date' => $commit->getCommitterDate(),
                'date' => $commit->getDate()
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get last commit details
     */
    public function getLastCommit(string $type, string $name): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        
        try {
            $repo = $this->gitService->openRepository($targetDir);
            $commit = $repo->getLastCommit();
            
            return [
                'id' => (string) $commit->getId(),
                'subject' => $commit->getSubject(),
                'body' => $commit->getBody(),
                'author_name' => $commit->getAuthorName(),
                'author_email' => $commit->getAuthorEmail(),
                'author_date' => $commit->getAuthorDate(),
                'committer_name' => $commit->getCommitterName(),
                'committer_email' => $commit->getCommitterEmail(),
                'committer_date' => $commit->getCommitterDate(),
                'date' => $commit->getDate()
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Pull changes from remote
     */
    public function pull(string $type, string $name, string $remote = 'origin', array $options = []): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            $repo->pull($remote, $options);
            
            $result['success'] = true;
            $result['message'] = "Pulled changes from $remote";
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Push changes to remote
     */
    public function push(string $type, string $name, string $remote = 'origin', array $options = []): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            $repo->push($remote, $options);
            
            $result['success'] = true;
            $result['message'] = "Pushed changes to $remote";
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Fetch changes from remote
     */
    public function fetch(string $type, string $name, string $remote = 'origin', array $options = []): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            $repo->fetch($remote, $options);
            
            $result['success'] = true;
            $result['message'] = "Fetched changes from $remote";
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Add a remote
     */
    public function addRemote(string $type, string $name, string $remoteName, string $url, array $options = []): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            $repo->addRemote($remoteName, $url, $options);
            
            $result['success'] = true;
            $result['message'] = "Remote $remoteName added";
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Rename a remote
     */
    public function renameRemote(string $type, string $name, string $oldName, string $newName): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            $repo->renameRemote($oldName, $newName);
            
            $result['success'] = true;
            $result['message'] = "Remote renamed from $oldName to $newName";
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Remove a remote
     */
    public function removeRemote(string $type, string $name, string $remoteName): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            $repo->removeRemote($remoteName);
            
            $result['success'] = true;
            $result['message'] = "Remote $remoteName removed";
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Change remote URL
     */
    public function setRemoteUrl(string $type, string $name, string $remoteName, string $url): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            $repo->setRemoteUrl($remoteName, $url);
            
            $result['success'] = true;
            $result['message'] = "Remote URL updated for $remoteName";
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Checkout a branch/tag/commit
     */
    public function checkout(string $type, string $name, string $ref): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            $repo->checkout($ref);
            
            $result['success'] = true;
            $result['message'] = "Checked out $ref";
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Merge a branch
     */
    public function merge(string $type, string $name, string $branch): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            $repo->merge($branch);
            
            $result['success'] = true;
            $result['message'] = "Merged $branch into current branch";
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Add files to staging
     */
    public function addFiles(string $type, string $name, array $files): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            
            if (empty($files)) {
                $repo->addAllChanges();
                $result['message'] = "All changes staged";
            } else {
                $repo->addFile($files);
                $result['message'] = "Files staged: " . implode(', ', $files);
            }
            
            $result['success'] = true;
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Rename files in repository
     */
    public function renameFiles(string $type, string $name, array $renames): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            $repo->renameFile($renames);
            
            $result['success'] = true;
            $result['message'] = "Files renamed successfully";
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Remove files from repository
     */
    public function removeFiles(string $type, string $name, array $files): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            $repo->removeFile($files);
            
            $result['success'] = true;
            $result['message'] = "Files removed from repository";
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Execute custom git command
     */
    public function executeCommand(string $type, string $name, string $command, ...$args): array
    {
        $targetDir = $this->getRepoPath($type, $name);
        $result = [
            'success' => false,
            'message' => '',
            'output' => []
        ];

        try {
            $repo = $this->gitService->openRepository($targetDir);
            $output = $repo->execute($command, ...$args);
            
            $result['success'] = true;
            $result['message'] = "Command executed successfully";
            $result['output'] = is_array($output) ? $output : [$output];
            
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Check if repository has changes
     */
    public function hasChanges(string $type, string $name): bool
    {
        $targetDir = $this->getRepoPath($type, $name);
        
        try {
            $repo = $this->gitService->openRepository($targetDir);
            return $repo->hasChanges();
        } catch (Exception $e) {
            return false;
        }
    }


}