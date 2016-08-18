<?php
/**
 * Router Role.
 *
 * @version 1.0.0
 */

namespace henlibs\router;

use ErrorException;

/**
 * Router Role.
 */
class RouterRoleGroup extends RouterRole
{
    /** @var array Group 集合 */
    private static $groupRoles = array();
    /** @var array Role 集合 */
    private $roles = array('src' => array(), 'alias' => array());
    /** @var array Action 集合 */
    private $actions = array();

    /**
     * Constructor.
     *
     * @param object   $collection 集合物件
     * @param string   $uri        URI
     * @param callable $action     Action
     */
    public function __construct($collection, $uri, $action = null)
    {
        $this->collection = $collection;
        $this->ruleUri = ltrim($uri, '/');
        $this->matchString = $this->parseMatchString($this->ruleUri);
        $this->uriTemplate = $this->parseUriTemplate($this->ruleUri);
        $this->layer = $this->parseLayer($this->ruleUri);

        $this->addAction($action);
    }

    /**
     * 工廠方法.
     *
     * @param object   $collection 集合物件
     * @param string   $uri        URI
     * @param callable $action     Action
     *
     * @return object
     */
    public static function getInstance($collection, $uri, $action = null)
    {
        $role = new self($collection, $uri, $action);
        $hash = $role->getHash();

        if (!isset(self::$groupRoles[$hash])) {
            self::$groupRoles[$hash] = $role;
        } else {
            unset($role);
        }

        return self::$groupRoles[$hash];
    }

    /**
     * 設定 action.
     *
     * @param callable $action Action
     *
     * @return object
     */
    protected function setAction($action = null)
    {
        if (!$action) {
            $this->actions = array();
        } else {
            $this->actions = array($action);
        }

        return $this;
    }

    /**
     * 增加 action.
     *
     * @param callable $action Action
     *
     * @return object
     */
    public function addAction($action = null)
    {
        if ($action) {
            $this->actions[] = $action;
        }

        return $this;
    }

    /**
     * 執行 action.
     *
     * @return mixed|null
     */
    public function run()
    {
        if (count($this->actions)) {
            foreach ($this->actions as $action) {
                if (is_callable($action)) {
                    call_user_func($action, $this, $this->getArgs());
                } elseif (!empty($action)) {
                    throw new ErrorException(sprintf('Router "%s" error occured when trigger action', $this->getUri()));
                }
            }
        }

        return $this->dispatch($this->getUri());
    }

    /**
     * 建立 Group URI.
     *
     * @param string $uri Sub URI
     *
     * @return string
     */
    private function makeGroupUri($uri)
    {
        if (!$uri) {
            return sprintf('/%s', $this->ruleUri);
        }

        return sprintf('/%s%s', (substr($this->ruleUri, -1) !== '/' ? $this->ruleUri.'/' : $this->ruleUri), trim($uri, '/'));
    }

    /**
     * 增加 Group role.
     *
     * @param string   $uri      Sub URI
     * @param callable $action   Action
     * @param string   $alias    Alias 名稱
     * @param callable $callable Callback 函式
     */
    public function addGroup($uri, $action = null, $alias = null, $callable = null)
    {
        if (!trim($uri)) {
            throw new ErrorException(sprintf('Router rule cannot be empty'));
        }
        $uri = $this->makeGroupUri($uri);
        $role = self::getInstance($this->collection, $uri, $action);

        $this->roles['src'][$role->getHash()] = $role;
        if ($alias) {
            $this->collection->addAlias($alias, $role);
        }
        $this->sortRoles();

        if (is_callable($callable)) {
            $callable($role);
        }

        return $this;
    }

    /**
     * 增加 role.
     *
     * @param string   $uri    Sub URI
     * @param callable $action Action
     * @param string   $alias  Alias 名稱
     */
    public function add($uri, $action = null, $alias = null)
    {
        $uri = $this->makeGroupUri($uri);
        $role = RouterRole::getInstance($this->collection, $uri, $action);

        // Validate if already exists
        $this->collection->validate($role);

        $this->roles['src'][$role->getHash()] = $role;
        if ($alias) {
            $this->collection->addAlias($alias, $role);
        }

        $this->sortRoles();

        return $this;
    }

    /**
     * 取得 role 物件.
     *
     * @param string $alias Alias 名稱
     *
     * @return object
     */
    public function get($alias)
    {
        return $this->collection->get($alias);
    }

    /**
     * 向子 Role 發佈 URI.
     *
     * @param string $uri URI
     *
     * @return mixed|null
     */
    public function dispatch($uri)
    {
        $role = $this->mapping($uri);
        if ($role) {
            $vars = $role->run();

            return $vars;
        }

        return;
    }

    /**
     * 比對 URI.
     *
     * @param string $uri URI
     *
     * @return object
     */
    private function mapping($uri)
    {
        foreach ($this->roles['src'] as $role) {
            if ($role->match($uri)) {
                return $role->setUri($uri);
            }
        }

        return false;
    }

    /**
     * Role 排序.
     */
    private function sortRoles()
    {
        usort($this->roles['src'], function ($role, $roleComp) {
            if ($role->getLayer() == $roleComp->getLayer()) {
                return 0;
            }

            return ($role->getLayer() > $roleComp->getLayer()) ? -1 : 1;
        });
    }

    /**
     * 解析比對正規式.
     *
     * @param string $uri URI
     */
    protected function parseMatchString($uri)
    {
        return parent::parseMatchString($uri).'(.*)';
    }

    /**
     * 解析 router 層級.
     *
     * @param string $uri URI
     */
    protected function parseLayer($uri)
    {
        return parent::parseLayer($uri) - 10;
    }
}
