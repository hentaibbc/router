<?php
/**
 * Router Loader.
 *
 * @version 1.0.0
 */

namespace henlibs\router;

use ErrorException;

/**
 * Router Loader.
 */
class Router
{
    /** @var array Role 集合 */
    private $roles = array('src' => array(), 'alias' => array(), 'hash' => array(), 'current' => null);

    /**
     * 工廠方法.
     *
     * @return object
     */
    public static function getInstance()
    {
        return new self();
    }

    /**
     * 加入 alias.
     *
     * @param string $alias Alias 名稱
     * @param object $role  Role 物件
     */
    public function addAlias($alias, $role)
    {
        if (isset($this->roles['alias'][$alias])) {
            throw new ErrorException(sprintf('Router alias %s already exists', $alias));
        }
        $this->roles['alias'][$alias] = $role;
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
        $role = RouterRoleGroup::getInstance($this, $uri, $action);

        $this->roles['src'][] = $role;
        if ($alias) {
            $this->addAlias($alias, $role);
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
        $role = RouterRole::getInstance($this, $uri, $action);

        // Validate if already exists
        $this->validate($role);

        $this->roles['src'][] = $role;
        if ($alias) {
            $this->addAlias($alias, $role);
        }

        $this->sortRoles();

        return $this;
    }

    /**
     * 產生一個 role.
     *
     * @param string $uri Sub URI
     *
     * @return object
     */
    public function build($uri)
    {
        return RouterRole::getInstance($this, $uri);
    }

    /**
     * 檢查是否合格.
     *
     * @param object $role Role 物件
     */
    public function validate($role)
    {
        if (isset($this->roles['hash'][$role->getHash()])) {
            throw new ErrorException(sprintf('Router "%s" already exists', $role->getRule()));
        }
        $this->roles['hash'][$role->getHash()] = true;
    }

    /**
     * 設定目前執行的終端 role.
     *
     * @param object $role Role
     *
     * @return object
     */
    public function setRole($role)
    {
        $this->roles['current'] = $role;

        return $this;
    }

    /**
     * 取得 role 物件.
     *
     * @param string $alias Alias 名稱
     *
     * @return object
     */
    public function get($alias = null)
    {
        if ($alias === null) {
            return $this->roles['current'];
        }

        if (!$this->roles['alias'][$alias]) {
            throw new ErrorException(sprintf('Router alias "%s" is not exists', $alias));
        }

        return $this->roles['alias'][$alias] ?: null;
    }

    /**
     * 向 Role 發佈 URI.
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
}
