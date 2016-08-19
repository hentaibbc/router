# Router
這是一個簡單的 PHP Router 用以管理單一入口程式。

## Usage

### 引用 Loader.php
```PHP
include 'path/to/Loader.php';
```

### 撰寫 Router 規則方法
Router 共有兩種主要的註冊方法

* 註冊節點: RouterRole
```
public function add(string $uri, callable $action, string $alias = null) {}
```

* 註冊節點群: RouterRoleGroup
```
public function addGroup(string $uri, callable $action, string $alias = null, callable $registerFun = null) {}
```

## 規則註冊及觸發範例

#### Example class
```PHP
class Product
{
    public static function handler($router)
    {
        $id = $router->getArg('id');

        echo 'I\'m product'.($id ? ', ID: '.$id : '');
    }

    public static function namedHandler($router)
    {
        $name = $router->getArg('name');

        echo 'I\'m product'.($name ? ', Name: '.$name : '');
    }

    public static function groupHandler()
    {
        echo "I'm in products\n\n";
    }

    public static function groupRoot()
    {
        echo "I'm products page";
    }

    public static function groupRootPage()
    {
        echo "I'm index of products";
    }
}
```
#### Example
```PHP
use henlibs\router\Router;

$router = Router::getInstance();

// 註冊節點.
$router->add('/product', 'Product::handler', 'product');

// 註冊節點 (含參數).
// 註冊時可設定參數 {[type:]name} 這樣的型式，目前 type 有 i (integer) 及 s (string) 兩種，若不設定時，預設為 s。
$router->add('/product/{i:id}', 'Product::handler', 'product_id');
$router->add('/product/{name}', 'Product::namedHandler', 'product_name');

// 註冊節點群.
$router->addGroup('/products', 'Product::groupHandler', 'products', function ($group) {
    // 可在群底下註冊其他的節點或節點群.
    $group->add('/', 'Product::groupRootPage');
    // 注意，在群中的 '/' 和 '' 會是不同的 rule.
    $group->add('', 'Product::groupRoot');
});

// 觸發
$router->dispatch('product');
// -- Result
// I'm product

$router->dispatch('product/100');
// -- Result
// I'm product, ID: 100

$router->dispatch('product/tv');
// -- Result
// I'm product, Name: tv

$router->dispatch('products');
// -- Result
// I'm in products
//
// I'm products page

$router->dispatch('products/');
// -- Result
// I'm in products
//
// I'm index of products

```
**__注意: 一個 Router 實體建議只觸發一次，觸發後相關的節點實體會存下觸發時取得的參數結果，多次觸發不同的 URI 可能會導致在取資料時出現與預期不相符的狀況。__**


## 網址組成
```PHP
use henlibs\router\Router;

$router = Router::getInstance();
// 額外補充: add 及 addGroup 的回傳值是 Router 本身，所以可以使用串接的型式撰寫。
$router->add('product/{i:id}', null, 'product_id')
       ->add('product/{name}', null, 'product_name')
       ->addGroup('products', null, 'products', function ($router) {
            $router->add('/id-{i:id}', null, 'products_id');
       });

echo $router->get('product_id')->url(['id' => 100]);
// -- Result
// product/100

echo $router->get('product_name')->url(['name' => 'test']);
// -- Result
// product/test

echo $router->get('products_id')->url(['id' => 500]);
// -- Result
// products/id-500

```
**__注意: 上例中，若 product_name 的 name 給的是數字的話，在 dispatch 時，會以觸發 id 為優先。__**
