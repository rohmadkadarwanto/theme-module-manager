<?php
namespace App\Services;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitRepository;
use Exception;

class GitService
{
    private $git;
    private $token;

    public function __construct()
    {
        $this->git = new Git();
        $this->loadToken();
    }

    private function loadToken(): void
    {
        // Try to load token from config file first
        $configFile = __DIR__.'/../../config/git.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $this->token = $config['token'] ?? null;
        }
        
        // Fallback to environment variable
        if (empty($this->token)) {
            $this->token = getenv('GIT_TOKEN');
        }
    }

    public function openRepository(string $path): GitRepository
    {
        if (!is_dir($path)) {
            throw new Exception("Repository directory not found: $path");
        }
        return $this->git->open($path);
    }

    public function initRepository(string $path, array $options = []): GitRepository
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0775, true)) {
                throw new Exception("Failed to create directory: $path");
            }
        }
        return $this->git->init($path, $options);
    }

    public function cloneRepository(string $url, string $path, array $options = [], ?string $repoToken = null): GitRepository 
    {
        try {
            $token = $repoToken ?? $this->token;
            
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Exception("Invalid repository URL format");
            }
    
            $authenticatedUrl = $this->authenticateUrl($url, $token);
            
            // Ensure target directory doesn't exist or is empty
            if (is_dir($path)) {
                $this->removeDirectory($path);
            }
    
            // Add default options if not provided
            $finalOptions = array_merge(['--depth' => '1'], $options);
            
            $repo = $this->git->cloneRepository($authenticatedUrl, $path, $finalOptions);
            
            // Verify clone was successful
            if (!is_dir($path . '/.git')) {
                throw new Exception("Repository clone failed - .git directory not found");
            }
            
            return $repo;
        } catch (Exception $e) {
            // Clean up if partial clone occurred
            if (is_dir($path)) {
                $this->removeDirectory($path);
            }
            throw new Exception("Clone operation failed: " . $e->getMessage());
        }
    }

    private function authenticateUrl(string $url, ?string $token): string
    {
        if (empty($token)) {
            return $url;
        }

        // Handle GitHub/GitLab URLs
        $domains = [
            'github.com' => [
                'pattern' => '|https://github\.com/(.*)|',
                'replacement' => 'https://%s@github.com/$1'
            ],
            'gitlab.com' => [
                'pattern' => '|https://gitlab\.com/(.*)|',
                'replacement' => 'https://oauth2:%s@gitlab.com/$1'
            ]
        ];

        foreach ($domains as $domain => $config) {
            if (strpos($url, $domain) !== false) {
                return preg_replace(
                    $config['pattern'],
                    sprintf($config['replacement'], $token),
                    $url
                );
            }
        }

        return $url;
    }

    private function maskUrl(string $url): string
    {
        return preg_replace('/https:\/\/(.*?)@/', 'https://[MASKED]@', $url);
    }

    private function maskToken(?string $token): string
    {
        if (!$token) return '[none]';
        return substr($token, 0, 4) . '...' . substr($token, -4);
    }

        /**
     * Custom method to set remote branches
     */
    public function setRemoteBranches(string $path, string $remoteName, array $branches): GitRepository
    {
        $repo = $this->openRepository($path);
        $this->executeCommand($repo, 'remote', 'set-branches', $remoteName, ...$branches);
        return $repo;
    }

    private function executeCommand(GitRepository $repo, string $command, ...$args): array
    {
        return $repo->execute($command, ...$args);
    }

    /**
     * Get commit details
     */
    public function getCommitDetails(GitRepository $repo, string $commitHash): array
    {
        $commit = $repo->getCommit($commitHash);
        return [
            'id' => (string) $commit->getId(),
            'subject' => $commit->getSubject(),
            'body' => $commit->getBody(),
            'author' => [
                'name' => $commit->getAuthorName(),
                'email' => $commit->getAuthorEmail(),
                'date' => $commit->getAuthorDate()
            ],
            'committer' => [
                'name' => $commit->getCommitterName(),
                'email' => $commit->getCommitterEmail(),
                'date' => $commit->getCommitterDate()
            ],
            'date' => $commit->getDate()
        ];
    }

    /**
     * Get last commit details
     */
    public function getLastCommitDetails(GitRepository $repo): array
    {
        $commit = $repo->getLastCommit();
        return [
            'id' => (string) $commit->getId(),
            'subject' => $commit->getSubject(),
            'body' => $commit->getBody(),
            'author' => [
                'name' => $commit->getAuthorName(),
                'email' => $commit->getAuthorEmail(),
                'date' => $commit->getAuthorDate()
            ],
            'committer' => [
                'name' => $commit->getCommitterName(),
                'email' => $commit->getCommitterEmail(),
                'date' => $commit->getCommitterDate()
            ],
            'date' => $commit->getDate()
        ];
    }


}