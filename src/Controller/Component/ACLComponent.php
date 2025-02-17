<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use App\Model\Entity\User;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\TableRegistry;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Utility\Inflector;
use Cake\Routing\Router;

class ACLComponent extends Component
{

    private $user = null;
    protected $components = ['Navigation'];

    public function initialize(array $config): void
    {
        $this->request = $config['request'];
        $this->Authentication = $config['Authentication'];
    }

    // syntax:
    // $__aclList[$controller][$action] = $permission_rules
    // $controller == '*'                 -  any controller can have this action
    // $action == []                 -  site admin only has access
    // $action == '*'                     -  any role has access
    // $action == array('OR' => [])  -  any role in the array has access
    // $action == array('AND' => []) -  roles with all permissions in the array have access
    // If we add any new functionality to MISP and we don't add it to this list, it will only be visible to site admins.
    private $aclList = array(
        '*' => [
            'checkPermission' => ['*'],
            'generateUUID' => ['*'],
            'getRoleAccess' => ['*'],
            'queryACL' => ['perm_admin']
        ],
        'Alignments' => [
            'add' => ['perm_admin'],
            'delete' => ['perm_admin'],
            'index' => ['*'],
            'view' => ['*']
        ],
        'AuditLogs' => [
            'filtering' => ['perm_admin'],
            'index' => ['perm_admin'],
        ],
        'AuthKeys' => [
            'add' => ['*'],
            'delete' => ['*'],
            'index' => ['*']
        ],
        'Broods' => [
            'add' => ['perm_admin'],
            'delete' => ['perm_admin'],
            'downloadIndividual' => ['perm_admin'],
            'downloadOrg' => ['perm_admin'],
            'downloadSharingGroup' => ['perm_admin'],
            'edit' => ['perm_admin'],
            'index' => ['perm_admin'],
            'interconnectTools' => ['perm_admin'],
            'previewIndex' => ['perm_admin'],
            'testConnection' => ['perm_admin'],
            'view' => ['perm_admin']
        ],
        'EncryptionKeys' => [
            'view' => ['*'],
            'add' => ['*'],
            'edit' => ['*'],
            'delete' => ['*'],
            'index' => ['*']
        ],
        'Inbox' => [
            'createEntry' => ['OR' => ['perm_admin', 'perm_sync']],
            'delete' => ['perm_admin'],
            'filtering' => ['perm_admin'],
            'index' => ['perm_admin'],
            'listProcessors' => ['OR' => ['perm_admin', 'perm_sync']],
            'process' => ['perm_admin'],
            'view' => ['perm_admin'],
        ],
        'Individuals' => [
            'add' => ['perm_admin'],
            'delete' => ['perm_admin'],
            'edit' => ['perm_admin'],
            'filtering' => ['*'],
            'index' => ['*'],
            'tag' => ['perm_tagger'],
            'untag' => ['perm_tagger'],
            'view' => ['*'],
            'viewTags' => ['*']
        ],
        'Instance' => [
            'home' => ['*'],
            'migrate' => ['perm_admin'],
            'migrationIndex' => ['perm_admin'],
            'rollback' => ['perm_admin'],
            'saveSetting' => ['perm_admin'],
            'searchAll' => ['*'],
            'settings' => ['perm_admin'],
            'status' => ['*']
        ],
        'LocalTools' => [
            'action' => ['perm_admin'],
            'add' => ['perm_admin'],
            'batchAction' => ['perm_admin'],
            'broodTools' => ['perm_admin'],
            'connectionRequest' => ['perm_admin'],
            // 'connectLocal' => ['perm_admin'],
            'delete' => ['perm_admin'],
            'edit' => ['perm_admin'],
            'exposedTools' => ['OR' => ['perm_admin', 'perm_sync']],
            'index' => ['perm_admin'],
            'connectorIndex' => ['perm_admin'],
            'view' => ['perm_admin'],
            'viewConnector' => ['perm_admin']
        ],
        'MailingLists' => [
            "add" => ['perm_org_admin'],
            "addIndividual" => ['perm_org_admin'],
            "delete" => ['perm_org_admin'],
            "edit" => ['perm_org_admin'],
            "index" => ['*'],
            "listIndividuals" => ['perm_org_admin'],
            "removeIndividual" => ['perm_org_admin'],
            "view" => ['*'],
        ],
        'MetaTemplateFields' => [
            'index' => ['perm_admin']
        ],
        'MetaTemplates' => [
            'createNewTemplate' => ['perm_admin'],
            'delete' => ['perm_admin'],
            'disable' => ['perm_admin'],
            'enable' => ['perm_admin'],
            'getMetaFieldsToUpdate' => ['perm_admin'],
            'index' => ['perm_admin'],
            'migrateOldMetaTemplateToNewestVersionForEntity' => ['perm_admin'],
            'update' => ['perm_admin'],
            'updateAllTemplates' => ['perm_admin'],
            'toggle' => ['perm_admin'],
            'view' => ['perm_admin'],
        ],
        'Organisations' => [
            'add' => ['perm_admin'],
            'delete' => ['perm_admin'],
            'edit' => ['perm_admin'],
            'filtering' => ['*'],
            'index' => ['*'],
            'tag' => ['perm_tagger'],
            'untag' => ['perm_tagger'],
            'view' => ['*'],
            'viewTags' => ['*']
        ],
        'Outbox' => [
            'createEntry' => ['perm_admin'],
            'delete' => ['perm_admin'],
            'filtering' => ['perm_admin'],
            'index' => ['perm_admin'],
            'listProcessors' => ['perm_admin'],
            'process' => ['perm_admin'],
            'view' => ['perm_admin']
        ],
        'Pages' => [
            'display' => ['*']
        ],
        'Roles' => [
            'add' => ['perm_admin'],
            'delete' =>  ['perm_admin'],
            'edit' =>  ['perm_admin'],
            'index' =>  ['*'],
            'view' =>  ['*']
        ],
        'SharingGroups' => [
            'add' => ['perm_org_admin'],
            'addOrg' => ['perm_org_admin'],
            'delete' => ['perm_org_admin'],
            'edit' => ['perm_org_admin'],
            'index' => ['*'],
            'listOrgs' => ['*'],
            'removeOrg' => ['perm_org_admin'],
            'view' => ['*']
        ],
        'Users' => [
            'add' => ['perm_org_admin'],
            'delete' => ['perm_org_admin'],
            'edit' => ['*'],
            'index' => ['perm_org_admin'],
            'login' => ['*'],
            'logout' => ['*'],
            'register' => ['*'],
            'settings' => ['*'],
            'toggle' => ['perm_org_admin'],
            'view' => ['*']
        ],
        'UserSettings' => [
            'index' => ['*'],
            'view' => ['*'],
            'add' => ['*'],
            'edit' => ['*'],
            'delete' => ['*'],
            'getMySettingByName' => ['*'],
            'setMySetting' => ['*'],
            'saveSetting' => ['*'],
            'getMyBookmarks' => ['*'],
            'saveMyBookmark' => ['*'],
            'deleteMyBookmark' => ['*']
        ],
        'Api' => [
            'index' => ['*']
        ]
    );

    private function __checkLoggedActions($user, $controller, $action)
    {
        $loggedActions = array(
            'servers' => array(
                'index' => array(
                    'role' => array(
                        'NOT' => array(
                            'perm_site_admin'
                        )
                    ),
                    'message' => __('This could be an indication of an attempted privilege escalation on older vulnerable versions of MISP (<2.4.115)')
                )
            )
        );
        foreach ($loggedActions as $k => $v) {
            $loggedActions[$k] = array_change_key_case($v);
        }
        $message = '';
        if (!empty($loggedActions[$controller])) {
            if (!empty($loggedActions[$controller][$action])) {
                $message = $loggedActions[$controller][$action]['message'];
                $hit = false;
                if (empty($loggedActions[$controller][$action]['role'])) {
                    $hit = true;
                } else {
                    $role_req = $loggedActions[$controller][$action]['role'];
                    if (empty($role_req['OR']) && empty($role_req['AND']) && empty($role_req['NOT'])) {
                        $role_req = array('OR' => $role_req);
                    }
                    if (!empty($role_req['NOT'])) {
                        foreach ($role_req['NOT'] as $k => $v) {
                            if (!$user['Role'][$v]) {
                                $hit = true;
                                continue;
                            }
                        }
                    }
                    if (!$hit && !empty($role_req['AND'])) {
                        $subhit = true;
                        foreach ($role_req['AND'] as $k => $v) {
                            $subhit = $subhit && $user['Role'][$v];
                        }
                        if ($subhit) {
                            $hit = true;
                        }
                    }
                    if (!$hit && !empty($role_req['OR'])) {
                        foreach ($role_req['OR'] as $k => $v) {
                            if ($user['Role'][$v]) {
                                $hit = true;
                                continue;
                            }
                        }
                    }
                    if ($hit) {
                        $this->Log = TableRegistry::get('Log');
                        $this->Log->create();
                        $this->Log->save(array(
                                'org' => 'SYSTEM',
                                'model' => 'User',
                                'model_id' => $user['id'],
                                'email' => $user['email'],
                                'action' => 'security',
                                'user_id' => $user['id'],
                                'title' => __('User triggered security alert by attempting to access /%s/%s. Reason why this endpoint is of interest: %s', $controller, $action, $message),
                        ));
                    }
                }
            }
        }
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?User
    {
        if (!empty($this->user)) {
            return $this->user;
        }
        return null;
    }

    public function canEditUser(User $currentUser, User $user): bool
    {
        if (empty($user) || empty($currentUser)) {
            return false;
        }
        if (!$currentUser['role']['perm_admin']) {
            if ($user['role']['perm_admin']) {
                return false; // org_admins cannot edit admins
            }
            if (!$currentUser['role']['perm_org_admin']) {
                return false;
            } else {
                if ($currentUser['organisation_id'] !== $user['organisation_id']) {
                    return false;
                }
            }
        }
        return true;
    }

    /*
     *  By default nothing besides the login is public. If configured, override the list with the additional interfaces
     */
    public function setPublicInterfaces(): void
    {
        $this->Authentication->allowUnauthenticated(['login', 'register']);
    }

    private function checkAccessInternal($controller, $action, $soft): bool
    {
        if (empty($this->user)) {
            // we have to be in a publically allowed scope otherwise the Auth component will kick us out anyway.
            return true;
        }
        if (!empty($this->user->role->perm_admin)) {
            return true;
        }
        //$this->__checkLoggedActions($user, $controller, $action);
        if (isset($this->aclList['*'][$action])) {
            if ($this->evaluateAccessLeaf('*', $action)) {
                return true;
            }
        }
        if (!isset($this->aclList[$controller])) {
            return $this->__error(404, __('Invalid controller.'), $soft);
        }
        return $this->evaluateAccessLeaf($controller, $action);
    }

    private function evaluateAccessLeaf(string $controller, string $action): bool
    {
        if (isset($this->aclList[$controller][$action]) && !empty($this->aclList[$controller][$action])) {
            if (in_array('*', $this->aclList[$controller][$action])) {
                return true;
            }
            if (isset($this->aclList[$controller][$action]['OR'])) {
                foreach ($this->aclList[$controller][$action]['OR'] as $permission) {
                    if ($this->user['role'][$permission]) {
                        return true;
                    }
                }
            } elseif (isset($this->aclList[$controller][$action]['AND'])) {
                $allConditionsMet = true;
                foreach ($this->aclList[$controller][$action]['AND'] as $permission) {
                    if (!$this->user['role'][$permission]) {
                        $allConditionsMet = false;
                    }
                }
                if ($allConditionsMet) {
                    return true;
                }
            } else {
                foreach ($this->aclList[$controller][$action] as $permission) {
                    if ($this->user['role'][$permission]) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function checkAccessUrl($url, $soft = false): bool
    {
        $urlParts = explode('/', $url);
        if ($urlParts[1] === 'open') {
            return in_array($urlParts[2], Configure::read('Cerebrate.open'));
        } else {
            return $this->checkAccessInternal(Inflector::camelize($urlParts[1]), $urlParts[2], $soft);
        }
    }

    // The check works like this:
    // If the user is a site admin, return true
    // If the requested action has an OR-d list, iterate through the list. If any of the permissions are set for the user, return true
    // If the requested action has an AND-ed list, iterate through the list. If any of the permissions for the user are not set, turn the check to false. Otherwise return true.
    // If the requested action has a permission, check if the user's role has it flagged. If yes, return true
    // If we fall through all of the checks, return an exception.
    public function checkAccess(bool $soft = false): bool
    {
        $controller = $this->request->getParam('controller');
        $action = $this->request->getParam('action');
        if ($this->checkAccessInternal($controller, $action, $soft) === true) {
            return true;
        }
        return $this->__error(403, 'You do not have permission to use this functionality.', $soft);
    }

    private function __error($code, $message, $soft = false)
    {
        if ($soft) {
            return false;
        }
        switch ($code) {
            case 404:
                throw new NotFoundException($message);
                break;
            case 403:
                throw new MethodNotAllowedException($message);
            default:
                throw new InternalErrorException('Unknown error: ' . $message);
        }
    }

    private function __findAllFunctions()
    {
        $functionFinder = '/public.function[\s\n]+(\S+)[\s\n]*\(/';
        $files = scandir(ROOT . '/src/Controller/');
        foreach ($files as $k => $file) {
            if (substr($file, -14) !== 'Controller.php') {
                unset($files[$k]);
            }
        }
        $results = [];
        foreach ($files as $file) {
            $controllerName = lcfirst(str_replace('Controller.php', "", $file));
            if ($controllerName === 'app') {
                $controllerName = '*';
            }
            $functionArray = [];
            $fileContents = file_get_contents(APP . 'Controller' . DS . $file);
            $fileContents = preg_replace('/\/\*[^\*]+?\*\//', '', $fileContents);
            preg_match_all($functionFinder, $fileContents, $functionArray);
            foreach ($functionArray[1] as $function) {
                if (substr($function, 0, 1) !== '_' && $function !== 'beforeFilter' && $function !== 'afterFilter') {
                    $results[$controllerName][] = $function;
                }
            }
        }
        return $results;
    }

    public function printAllFunctionNames($content = false)
    {
        $results = $this->__findAllFunctions();
        ksort($results);
        return $results;
    }

    public function findMissingFunctionNames($content = false)
    {
        $results = $this->__findAllFunctions();
        $missing = [];
        foreach ($results as $controller => $functions) {
            $controller = Inflector::camelize($controller);
            foreach ($functions as $function) {
                if (in_array($function, ['beforeFilter', 'beforeRender', 'initialize', 'afterFilter'])) {
                    continue;
                }
                if (!isset($this->aclList[$controller])
                || !in_array($function, array_keys($this->aclList[$controller]))) {
                    $missing[$controller][] = $function;
                }
            }
        }
        return $missing;
    }

    public function getRoleAccess($role = false, $url_mode = true)
    {
        return $this->__checkRoleAccess($role, $url_mode);
    }

    public function printRoleAccess($content = false)
    {
        $results = [];
        $this->Role = TableRegistry::get('Roles');
        $conditions = [];
        if (is_numeric($content)) {
            $conditions = array('id' => $content);
        }
        $roles = $this->Role->find('all', array(
            'recursive' => -1,
            'conditions' => $conditions
        ));
        if (empty($roles)) {
            throw new NotFoundException('Role not found.');
        }
        foreach ($roles as $role) {
            $urls = $this->__checkRoleAccess($role['Role']);
            $results[$role['Role']['id']] = array('name' => $role['Role']['name'], 'urls' => $urls);
        }
        return $results;
    }

    private function __formatControllerAction(array $results, string $controller, string $action, $url_mode = true): array
    {
        if ($url_mode) {
            $results[] = DS . $controller . DS . $action . DS . '*';
        } else {
            $results[$controller][] = $action;
        }
        return $results;
    }

    private function __checkRoleAccess($role = false, $url_mode = true)
    {
        $results = [];
        if ($role === false) {
            $role = $this->getUser()['role'];
        }
        foreach ($this->aclList as $controller => $actions) {
            foreach ($actions as $action => $permissions) {
                if ($role['perm_admin']) {
                    $results = $this->__formatControllerAction($results, $controller, $action, $url_mode);
                } elseif (in_array('*', $permissions)) {
                    $results = $this->__formatControllerAction($results, $controller, $action, $url_mode);
                } elseif (isset($permissions['OR'])) {
                    $access = false;
                    foreach ($permissions['OR'] as $permission) {
                        if ($role[$permission]) {
                            $access = true;
                        }
                    }
                    if ($access) {
                        $results = $this->__formatControllerAction($results, $controller, $action, $url_mode);
                    }
                } elseif (isset($permissions['AND'])) {
                    $access = true;
                    foreach ($permissions['AND'] as $permission) {
                        if ($role[$permission]) {
                            $access = false;
                        }
                    }
                    if ($access) {
                        $results = $this->__formatControllerAction($results, $controller, $action, $url_mode);
                    }
                } elseif (isset($permissions[0]) && $role[$permissions[0]]) {
                    $results = $this->__formatControllerAction($results, $controller, $action, $url_mode);
                }
            }
        }
        return $results;
    }

    public function getMenu()
    {
        $menu = $this->Navigation->getSideMenu();
        foreach ($menu as $group => $subMenu) {
            if ($group == '__bookmarks') {
                continue;
            }
            foreach ($subMenu as $subMenuElementName => $subMenuElement) {
                if (!empty($subMenuElement['url']) && !$this->checkAccessUrl($subMenuElement['url'], true) === true) {
                    unset($menu[$group][$subMenuElementName]);
                    continue;
                }
                if (!empty($subMenuElement['children'])) {
                    foreach ($subMenuElement['children'] as $menuItem => $menuItemData) {
                        if (!empty($menuItemData['url']) && !$this->checkAccessUrl($menuItemData['url'], true) === true) {
                            unset($menu[$group][$subMenuElementName]['children'][$menuItem]);
                            continue;
                        }
                    }
                    if (empty($menu[$group][$subMenuElementName]['children'])) {
                        unset($subMenu[$subMenuElementName]);
                    }
                }
            }
            if (empty($menu[$group])) {
                unset($menu[$group]);
            }
        }
        return $menu;
    }
}
