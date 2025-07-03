<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Core/RepositoryManager.php';

session_start();


// Initialize manager
$manager = new App\Core\RepositoryManager();



// Set default timezone
date_default_timezone_set('UTC');

// Handle actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'rollback':
 
            $type = $_GET['type'] ?? '';
            $type = strtolower($type);
            if (str_ends_with($type, 's')) {
                $type = substr($type, 0, -1);
            }
                       
            if (isset($type, $_GET['name'], $_POST['commit_hash'])) {
                $result = $manager->rollback(
                    $type,
                    $_GET['name'],
                    $_POST['commit_hash']
                );
                
                $_SESSION['message'] = [
                    'type' => $result['success'] ? 'success' : 'danger',
                    'text' => $result['message']
                ];
                
                header("Location: /?page=repo_detail&type={$type}&name={$_GET['name']}");
                exit;
            }
            break;
            
        case 'sync_single':

            $type = $_GET['type'] ?? '';
            $type = strtolower($type);
            if (str_ends_with($type, 's')) {
                $type = substr($type, 0, -1);
            }

            if (isset($type, $_GET['name'])) {
                $result = $manager->syncRepo($type, $_GET['name']);
                
                $_SESSION['message'] = [
                    'type' => $result['success'] ? 'success' : 'danger',
                    'text' => $result['message']
                ];
                
                header("Location: /?page=repo_detail&type={$type}&name={$_GET['name']}");
                exit;
            }
            break;
    }
}

// Routing
$page = $_GET['page'] ?? 'dashboard';
$data = [];

switch ($page) {
    case 'dashboard':
        $data = [
            'repos' => $manager->getRepos(),
            'installed' => $manager->listInstalled()
        ];
        break;
        
    case 'repo_detail':


        $type = $_GET['type'] ?? '';
        $type = strtolower($type);
        if (str_ends_with($type, 's')) {
            $type = substr($type, 0, -1);
        }


        if (!isset($type, $_GET['name'])) {
            header("Location: /?page=dashboard");
            exit;
        }
        
        $data = $manager->getRepoDetails($type, $_GET['name']);
        $data['type'] = $type;
        $data['name'] = $_GET['name'];
        $data['config'] = $manager->getRepos()[$type][$_GET['name']] ?? null;
        $data['history'] = $manager->getCommitHistory($type, $_GET['name'], 5);
    
        break;
    case 'branch_tag_management':


        $type = $_GET['type'] ?? '';
        $type = strtolower($type);
        if (str_ends_with($type, 's')) {
            $type = substr($type, 0, -1);
        }


        if (!isset($type, $_GET['name'])) {
            header("Location: /?page=dashboard");
            exit;
        }
        


        break;
                
        case 'sync':
            $results = [];
            $title = "Sync Result";
        

            $type = $_GET['type'] ?? '';
            $type = strtolower($type);
            if (str_ends_with($type, 's')) {
                $type = substr($type, 0, -1);
            }

            echo $type;
            // Handle different sync scenarios
            if (isset($type, $_GET['name'])) {
                // Sync single repository
                $results[] = $manager->syncRepo($type, $_GET['name']);
                $title = "Sync Result for {$type}/{$_GET['name']}";
            } elseif (isset($type)) {
                // Sync all repositories of a type
                $type = $type;
                $repos = $manager->getRepos();
                
                // Check if the type exists in repos
                if (isset($repos[$type]) && is_array($repos[$type])) {
                    foreach (array_keys($repos[$type]) as $name) {
                        $results[] = $manager->syncRepo($type, $name);
                    }
                    $title = "Sync All " . ucfirst($type);
                } else {
                    $results[] = [
                        'success' => false,
                        'message' => "No repositories found for type: $type",
                        'type' => $type,
                        'name' => ''
                    ];
                }
            } else {
                // Sync all repositories
                $repos = $manager->getRepos();
                foreach (['themes', 'modules'] as $type) {
                    if (isset($repos[$type]) && is_array($repos[$type])) {
                        foreach (array_keys($repos[$type]) as $name) {
                            $results[] = $manager->syncRepo($type, $name);
                        }
                    }
                }
                $title = "Sync All Repositories";
            }
            
            $data = [
                'results' => $results,
                'title' => $title
            ];
            break;
}

// Render view
function render($view, $data = []) {
    global $manager;
    
    extract($data); // Make array keys available as variables
    
    require __DIR__ . "/templates/components/header.php";
    
    if (file_exists(__DIR__ . "/templates/{$view}.php")) {
        require __DIR__ . "/templates/{$view}.php";
    } else {
        require __DIR__ . "/templates/404.php";
    }
    
    require __DIR__ . "/templates/components/footer.php";
    exit;
}

render($page, $data);