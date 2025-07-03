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


header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$type = $_GET['type'] ?? '';
$name = $_GET['name'] ?? '';

try {
    $manager = new App\Core\RepositoryManager();
    
    switch ($action) {
        case 'get_repos':
            echo json_encode([
                'success' => true,
                'data' => $manager->getRepos()
            ]);
            break;
            
        case 'get_installed':
            echo json_encode([
                'success' => true,
                'data' => $manager->listInstalled()
            ]);
            break;
            
        case 'get_details':
            if (empty($type) || empty($name)) {
                throw new Exception("Type and name parameters are required");
            }
            echo json_encode([
                'success' => true,
                'data' => $manager->getRepoDetails($type, $name)
            ]);
            break;
            
        case 'sync':
            if (empty($type) || empty($name)) {
                throw new Exception("Type and name parameters are required");
            }
            echo json_encode([
                'success' => true,
                'data' => $manager->syncRepo($type, $name)
            ]);
        break;
        case 'sync_all':
            try {
                if (empty($type)) {
                    throw new Exception("Type parameter is required");
                }
                
                $repos = $manager->getRepos();
                $results = [];
                
                if (!isset($repos[$type])) {
                    throw new Exception("No repositories found for type: $type");
                }
        
                foreach ($repos[$type] as $name => $config) {
                    try {
                        $results[$name] = $manager->syncRepo($type, $name);
                        // Add small delay between syncs to prevent rate limiting
                        usleep(500000); // 0.5 second
                    } catch (Exception $e) {
                        $results[$name] = [
                            'success' => false,
                            'message' => $e->getMessage()
                        ];
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $results
                ]);
            } catch (Exception $e) {
                error_log("Sync all error: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
        break;        
        case 'get_branches':
            if (empty($type) || empty($name)) {
                throw new Exception("Type and name parameters are required");
            }
            echo json_encode([
                'success' => true,
                'data' => $manager->getBranches($type, $name)
            ]);
            break;
        
        case 'get_tags':
            if (empty($type) || empty($name)) {
                throw new Exception("Type and name parameters are required");
            }
            echo json_encode([
                'success' => true,
                'data' => $manager->getTags($type, $name)
            ]);
            break;
        
        case 'create_branch':
            if (empty($type) || empty($name) || empty($_POST['branch_name'])) {
                throw new Exception("Type, name and branch_name parameters are required");
            }
            echo json_encode([
                'success' => true,
                'data' => $manager->createBranch(
                    $type,
                    $name,
                    $_POST['branch_name'],
                    ($_POST['checkout'] ?? 'false') === 'true'
                )
            ]);
            break;
        
        case 'delete_branch':
            if (empty($type) || empty($name) || empty($_POST['branch_name'])) {
                throw new Exception("Type, name and branch_name parameters are required");
            }
            echo json_encode([
                'success' => true,
                'data' => $manager->removeBranch($type, $name, $_POST['branch_name'])
            ]);
            break;
        
        case 'create_tag':
            if (empty($type) || empty($name) || empty($_POST['tag_name'])) {
                throw new Exception("Type, name and tag_name parameters are required");
            }
            echo json_encode([
                'success' => true,
                'data' => $manager->createTag(
                    $type,
                    $name,
                    $_POST['tag_name'],
                    $_POST['message'] ?? null
                )
            ]);
            break;
        
        case 'delete_tag':
            if (empty($type) || empty($name) || empty($_POST['tag_name'])) {
                throw new Exception("Type, name and tag_name parameters are required");
            }
            echo json_encode([
                'success' => true,
                'data' => $manager->removeTag($type, $name, $_POST['tag_name'])
            ]);
        break;
        case 'push':
            if (empty($type) || empty($name) || empty($_POST['remote']) || empty($_POST['branch'])) {
                throw new Exception("Type, name, remote and branch parameters are required");
            }
            echo json_encode([
                'success' => true,
                'data' => $manager->push(
                    $type,
                    $name,
                    $_POST['remote'],
                    $_POST['branch'],
                    $_POST['options'] ?? []
                )
            ]);
            break;
            default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}