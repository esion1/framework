<?php
/**
 * Kerisy Framework
 *
 * PHP Version 7
 *
 * @author          Jiaqing Zou <zoujiaqing@gmail.com>
 * @copyright      (c) 2015 putao.com, Inc.
 * @package         kerisy/framework
 * @subpackage      Core
 * @since           2015/11/11
 * @version         2.0.0
 */

namespace Kerisy\Core;

use Kerisy\Core\Object;

/**
 * Class Router
 *
 * @package Kerisy\Core
 */
class Router
{
    private $_groups;
    private $_domain_groups;
    private $_directory_groups;
    private $_default_group;

    private static $_instance;

    private function __construct()
    {
        // Get instance, please;
    }

    private function __clone()
    {
        // Get instance, please;
    }

    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new Router();
        }

        return self::$_instance;
    }

    public function setConfig($configs)
    {
        foreach ($configs as $config) {
            $this->addGroup($config);
        }
    }

    private function getGroupByDomain($domain)
    {
        return isset($this->_domain_groups[$domain]) ? $this->_domain_groups[$domain] : false;
    }

    private function getGroupByDirectory($directory)
    {
        return isset($this->_directory_groups[$directory]) ? $this->_directory_groups[$directory] : false;
    }

    private function addGroup($config)
    {
        $group = new RouteGroup();
        $group->setPrefix($config['prefix']);

        $this->_groups[$config['prefix']] = $group;

        if (isset($config['domain']) && $config['domain'] != '') {
            $this->_domain_groups[$config['domain']] = &$this->_groups[$config['prefix']];
        } elseif (isset($config['directory']) && $config['directory'] != '') {
            $this->_directory_groups[$config['directory']] = &$this->_groups[$config['prefix']];
        } else {
            $this->_default_group = &$this->_groups[$config['prefix']];
        }


        foreach ($config['routes'] as $route) {
            $group->addRoute($route);
        }
    }

    public function routing($request)
    {
        $group = null;
        $route = null;
        $path = trim($request->path, '/');

        $cacheKey = "route_cached_" . $request->host . '_' . base64_encode($path);
        if ($route = \apcu_fetch($cacheKey)) {
            return $route;
        }

        if ($path == null) {
            $path = "/";
        }

        if (!$group = $this->getGroupByDomain($request->host)) {
            if ($path == null && $this->_default_group) {
                $group = $this->_default_group;
            } else {
                $tmp = explode('/', $path);

                $directory = $tmp[0];
                if ($group = $this->getGroupByDirectory($directory)) {
                    unset($tmp[0]);
                    $path = implode('/', $tmp);
                } elseif ($this->_default_group) {
//                    $group = $this->_default_group;
                }

                unset($tmp);
            }
        }

        //打补丁 如果没有匹配, 取所有路由循环
        if ($this->_groups && !$group) {
            foreach ($this->_groups as $v) {
                $route = $v->match($path);
                if ($route) {
                    $group = $v;
                    break;
                }
            }
        }

        $group = $group ? $group : $this->_default_group;

        $route = $route ? $route : ($group ? $group->match($path) : null);

        if ($group && $route) {
        } else {
            $route = $this->getRouteByPath($path);
            $route->setPrefix($group->getPrefix());

            //传递所有前缀
            $allPrefix = [];
            if ($this->_groups) {
                foreach ($this->_groups as $k=>$v) {
                    $allPrefix[] =$k;
                }
            }
            $route->setAllPrefix($allPrefix);
        }

        if ($route !== null) {
            \apcu_add($cacheKey, $route, 3600);
            return $route;
        }

        return false;
    }


    public function getRouteByPath($path = '/')
    {
        $mca = explode('/', $path);

        $route = new Route();
        $route->setModule(!empty($mca[0]) ? $mca[0] : $this->getDefaultModule());
        $route->setController(!empty($mca[1]) ? $mca[1] : $this->getDefaultController());
        $route->setAction(!empty($mca[2]) ? $mca[2] : $this->getDefaultAction());

        return $route;
    }

    public function getDefaultPrefix()
    {
        return 'Front';
    }

    public function getDefaultModule()
    {
        return 'Core';
    }

    public function getDefaultController()
    {
        return 'index';
    }

    public function getDefaultAction()
    {
        return 'index';
    }

    public function createUrl($path, $params)
    {
        $url = "";
        return $url;
    }
}
