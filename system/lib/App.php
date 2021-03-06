<?php

    class App
    {
        private static $instance = null;
        private $config;
        private $components;
        private $eventmanager;
        private $componentBasePath;
        private $baseUrl;

        private function __construct(Configuration $config)
        {
            $this->config = $config;
            $this->eventmanager = new EventManager();

            $this->eventmanager->trigger('app_initialized', array($this));

            $this->components = array();
            $this->componentBasePath = SYS_PATH . DIRECTORY_SEPARATOR . $this->config->get('componentDir') . DIRECTORY_SEPARATOR;

            $this->baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https' : 'http') . '://' . $_SERVER["SERVER_NAME"] . dirname($_SERVER['SCRIPT_NAME']) . '/';

            $this->autoloadComponents();
        }

        public static function initialize(Configuration $config)
        {
            if (self::$instance === null)
            {
                if (!defined('APP_PATH') || !defined('SYS_PATH'))
                {
                    throw new AppExcception('The constants APP_PATH and SYS_PATH have to be defined!');
                }
                self::$instance = new self($config);
            }
            return self::$instance;
        }

        public function loadComponent($name, array $config = null)
        {
            if ($config === null)
            {
                $componentConfigs = $config->get('components');
                if (isset($componentConfigs[$name]) && is_array($componentConfigs[$name]))
                {
                    $config = $componentConfigs[$name];
                }
                else
                {
                    throw new ComponentException('Component configuration is missing or invalid!');
                }
            }
            $path = $this->componentBasePath . $name . '.php';
            if (is_readable($path))
            {
                require_once $path;
                if (class_exists($name))
                {
                    $instance = new $name();
                    if ($instance instanceof Component)
                    {
                        $instance->initialize($this, $config);
                        $this->components[$name] = $instance;
                    }
                    else
                    {
                        throw new ComponentException('Component "' . $name . '" does not implement Component!');
                    }
                }
                else
                {
                    throw new ComponentException('Component "' . $name . '" is invalid!');
                }
            }
            else
            {
                throw new ComponentException('Failed to read component "' . $name . '" ! ' . $path);
            }
        }

        private function autoloadComponents()
        {
            $components = $this->config->get('components');
            if (is_array($components))
            {
                foreach ($components as $name => $config)
                {
                    if (is_array($config) && (!isset($config['autoload']) || !$config['autoload']))
                    {
                        $this->loadComponent($name, $config);
                    }
                }
            }
        }

        /**
         * @return App
         */
        public static function get()
        {
            return self::$instance;
        }

        public function getConfig()
        {
            return $this->config;
        }

        public function getComponent($name)
        {
            if (!isset($this->components[$name]))
            {
                $this->loadComponent($name);
            }
            return $this->components[$name];
        }

        public function __get($name)
        {
            $type = substr($name, 0, 1);
            $name = substr($name, 1);

            switch ($type)
            {
                case 'c':
                    return $this->getComponent($name);
                default:
                    throw new AppException('Unknown property "' . $type . $name . '" !');
            }
        }

        /**
         * @return EventManager
         */
        public function getEventManager()
        {
            return $this->eventmanager;
        }

        public function getBaseUrl()
        {
            return $this->baseUrl;
        }
    }

?>
