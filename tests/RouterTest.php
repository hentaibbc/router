<?php
/**
 * Router test
 */

// use PHPUnit_Framework_TestCase;
use henlibs\router\Router;

class RouterTest extends PHPUnit_Framework_TestCase
{
    /**
     * Get Instance
     */
    public function testGetInstance()
    {
        $router = Router::getInstance();
        $this->assertInstanceOf('henlibs\router\Router', $router);
    }

    /** Cases */
    public function getCase($case) {
        switch ($case) {
            case 1:
                return [
                    function ($router) {
                        $router
                            ->addGroup('/api', function () {
                                echo 'in api. ';
                            }, 'api', function ($router) {
                                $router
                                    ->addGroup('/member', function () {
                                        echo 'in api/member. ';
                                    }, 'api_member', function ($router) {
                                        $router
                                            ->add('/u/{i:id}', function ($router) {
                                                echo 'in api/member/u (grouped), get: '.$router->getArg('id');
                                            }, 'api_member_u_grouped')
                                            ->add('/get', function ($router) {
                                                echo 'in api/member/user';
                                            }, 'api_member_get')
                                        ;
                                    })
                                    ->add('/member/u-{i:id}', function ($router) {
                                        echo 'in api/member/u, get: '.$router->getArg('id');
                                    }, 'api_member_u')
                                    ->add('/member/name-{name}', function ($router) {
                                        echo 'in api/member/name, get: '.$router->getArg('name');
                                    }, 'api_member_name')
                                    ->add('/', function () {
                                        echo 'in api folder';
                                    }, 'api_member_slash')
                                    ->add('', function () {
                                        echo 'in api root';
                                    }, 'api_member_root')
                                ;
                            })
                            ->add('/profile', function () {
                                echo 'in profile';
                            }, 'profile')
                            ->add('', function () {
                                echo 'in root';
                            }, 'home')
                        ;
                    },
                ];
            case 2:
                return [];
        }
    }

    public function getSuccessTesters() {
        return [
            function ($router) {
                @ob_start();
                $router->dispatch('');
                $output = ob_get_clean();
                $this->assertEquals('in root', $output);
            },
            function ($router) {
                @ob_start();
                $router->dispatch('profile');
                $output = ob_get_clean();
                $this->assertEquals('in profile', $output);
            },
            function ($router) {
                @ob_start();
                $router->dispatch('profile/1');
                $output = ob_get_clean();
                $this->assertEquals('', $output);
            },
            function ($router) {
                @ob_start();
                $router->dispatch('api');
                $output = ob_get_clean();
                $this->assertEquals('in api. in api root', $output);
            },
            function ($router) {
                @ob_start();
                $router->dispatch('api/');
                $output = ob_get_clean();
                $this->assertEquals('in api. in api folder', $output);
            },
            function ($router) {
                @ob_start();
                $router->dispatch('api/member/u-aaa');
                $output = ob_get_clean();
                $this->assertEquals('in api. in api/member. ', $output);
            },
            function ($router) {
                $id = mt_rand(1, 100000);
                @ob_start();
                $router->dispatch('api/member/u-'.$id);
                $output = ob_get_clean();
                $this->assertEquals('in api. in api/member/u, get: '.$id, $output);
            },
            function ($router) {
                $id = mt_rand(1, 100000);
                @ob_start();
                $router->dispatch('api/member/u/'.$id);
                $output = ob_get_clean();
                $this->assertEquals('in api. in api/member. in api/member/u (grouped), get: '.$id, $output);
            },
            function ($router) {
                @ob_start();
                $router->dispatch('api/member/get');
                $output = ob_get_clean();
                $this->assertEquals('in api. in api/member. in api/member/user', $output);
            },
            function ($router) {
                @ob_start();
                $router->dispatch('api/member/get/1');
                $output = ob_get_clean();
                $this->assertEquals('in api. in api/member. ', $output);
            },
        ];
    }

    /** Success cases */
    public function providerSuccessRouterRules()
    {
        $builders = $this->getCase(1);
        $testers = $this->getSuccessTesters();

        $confs = [];
        foreach ($builders as $builder) {
            foreach ($testers as $tester) {
                $confs[] = [$builder, $tester];
            }
        }

        return $confs;
    }

    /** Error cases */
    public function providerErrorRouterRules()
    {

    }

    /** Exception cases */
    public function providerExceptionRules()
    {

    }

    /**
     * Test success rules
     * @dataProvider providerSuccessRouterRules
     */
    public function testSuccessRouterRules($builder, $tester)
    {
        $router = Router::getInstance();
        $builder($router);
        $tester($router);
    }
}
