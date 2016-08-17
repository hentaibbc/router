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
class RouterRole
{
    /** @var string 目前的 URI */
    public $uri;
    /** @var array 目前的參數 */
    public $args = array();

    /** @var string URI 規則 */
    protected $ruleUri;
    /** @var string 比對正規式 */
    protected $matchString;
    /** @var array 參數設定 */
    protected $argConfs = array();
    /** @var callable Action */
    protected $action;
    /** @var string URI 樣版 */
    protected $uriTemplate;
    /** @var int 層級 */
    protected $layer;
    /** @var object 集合物件 */
    protected $collection;

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

        $this->setAction($action);
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
        return new static($collection, $uri, $action);
    }

    /**
     * 取得 Hash.
     *
     * @return string
     */
    public function getHash()
    {
        return $this->generateHash($this->matchString);
    }

    /**
     * 建立 hash.
     *
     * @param string $string 要建立的字串
     *
     * @return string
     */
    protected function generateHash($string = null)
    {
        return md5($string ?: $this->matchString);
    }

    /**
     * 取得檢查層級.
     *
     * @return int
     */
    public function getLayer()
    {
        return $this->layer;
    }

    /**
     * 檢查是否符合.
     *
     * @param string $uri URI
     *
     * @return bool
     */
    public function match($uri)
    {
        if (preg_match('#^'.$this->matchString.'$#', $uri)) {
            return true;
        }

        return false;
    }

    /**
     * 設定目前 URI.
     *
     * @param string $uri URI
     *
     * @return object
     */
    public function setUri($uri)
    {
        if (preg_match('#^'.$this->matchString.'$#', $uri, $matches)) {
            foreach ($matches as $index => $match) {
                if (!$index) {
                    $this->uri = $match;
                } else {
                    if (!isset($this->argConfs[$index])) {
                        continue;
                    }
                    $conf = $this->argConfs[$index];
                    $this->args[$conf['name']] = $conf['formatter']($match);
                }
            }
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
        $this->collection->setRole($this);

        if (is_callable($this->action)) {
            $return = call_user_func($this->action, $this, $this->getArgs());

            return $return ?: true;
        } elseif (!empty($this->action)) {
            if (file_exists($this->action)) {
                include $this->action;
            } else {
                throw new ErrorException(sprintf('Router "%s" error occured when trigger action', $this->getUri()));
            }
        }

        return;
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
        $this->action = $action;

        return $this;
    }

    /**
     * 取得 Action.
     *
     * @return callable
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * 取得 URI 規則.
     *
     * @return string
     */
    public function getRule()
    {
        return '/'.$this->ruleUri;
    }

    /**
     * 取得目前的 URI.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * 是否為執行中.
     *
     * @return bool
     */
    public function isRun()
    {
        return $this->uri ? true : false;
    }

    /**
     * 取得所有參數.
     *
     * @return array
     */
    public function getArgs()
    {
        return $this->args ?: array();
    }

    /**
     * 取得指定參數.
     *
     * @param string $name 參數名稱
     *
     * @return mixed|null
     */
    public function getArg($name)
    {
        return $this->args[$name] ?: null;
    }

    /**
     * 建立網址.
     *
     * @param array  $args   參數
     * @param object $router 參考用的 router
     *
     * @return string
     */
    public function url($args = array(), $router = null)
    {
        $url = $this->uriTemplate;

        $tmpParams = array_merge($this->args, $args);
        // 利用現有的 router 去補共用資料
        if ($router instanceof self) {
            $ary = $router->getArgs();
            if (count($this->argConfs)) {
                foreach ($this->argConfs as $conf) {
                    if (!isset($tmpParams[$conf['name']]) && isset($ary[$conf['name']])) {
                        $tmpParams[$conf['name']] = $ary[$conf['name']];
                    }
                }
            }
        }
        foreach ($tmpParams as $key => $val) {
            $search = '{'.$key.'}';
            if (strpos($url, $search) !== false) {
                unset($args[$key]);
            }
            $url = str_replace($search, $val, $url);
        }

        $returnUrl = $url.(count($args) ? '?'.http_build_query($args) : '');
        if ($result) {
            $returnUrl = $result;
        }

        return $returnUrl;
    }

    /**
     * 解析比對正規式.
     *
     * @param string $uri URI
     */
    protected function parseMatchString($uri)
    {
        $names = array();
        $matchString = preg_replace_callback('#\{((i|s):)?([a-zA-Z0-9_]+[\?]?)\}#', function ($matches) use ($names) {
            $varName = $matches[3];
            if (substr($varName, -1) == '?') {
                $nullable = true;
                $varName = substr($varName, 0, -1);
            }
            if (isset($names[$varName])) {
                throw new ErrorException(sprintf('Parameter name "%s" is duplicated', $varName));
            }
            $names[$varName] = true;

            $preg = '([^/]'.($nullable ? '*' : '+').')';
            $formatter = function ($text) {
                return $text;
            };
            if ($matches[2] == 'i') {
                $preg = '([0-9]+)';
                $formatter = function ($text) {
                    return (int) $text;
                };
            }
            $index = count($this->argConfs) + 1;
            $this->argConfs[$index] = array(
                'name' => $varName,
                'formatter' => $formatter,
                'preg' => $preg,
            );

            return '@@'.$index.'@@';
        }, $uri);

        if (count($this->argConfs)) {
            $matchString = preg_quote($matchString, '#');
            foreach ($this->argConfs as $index => $conf) {
                $matchString = str_replace('@@'.$index.'@@', $conf['preg'], $matchString);
            }
        }

        return $matchString;
    }

    /**
     * 解析 URI 樣版.
     *
     * @param string $uri URI
     */
    protected function parseUriTemplate($uri)
    {
        $names = array();
        $uriTemplate = preg_replace_callback('#\{((i|s):)?([a-zA-Z0-9_]+)[\?]?\}#', function ($matches) use ($names) {
            if (isset($names[$matches[3]])) {
                throw new ErrorException(sprintf('Parameter name "%s" is duplicated', $matches[3]));
            }
            $names[$matches[3]] = true;

            return '{'.$matches[3].'}';
        }, $uri);

        return $uriTemplate;
    }

    /**
     * 解析 router 層級.
     *
     * @param string $uri URI
     */
    protected function parseLayer($uri)
    {
        if ($uri == '') {
            $this->layer = -1;

            return;
        }
        $patterns = explode('/', $uri);

        $flag = true;
        $layer = 0;
        foreach ($patterns as $pattern) {
            if (strpos($pattern, '{') === false) {
                if ($flag) {
                    $layer += 1000000;
                } else {
                    $layer += 10000;
                }

                if (!$pattern) {
                    $layer -= 10;
                }
            } else {
                $layer += 100;
                if (strpos($pattern, '{i:') !== false) {
                    $layer += 1;
                }

                $flag = false;
            }
        }

        return $layer;
    }
}
