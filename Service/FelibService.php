<?php

namespace SmartCore\Bundle\FelibBundle\Service;

use RickySu\Tagcache\Adapter\TagcacheAdapter;
use Symfony\Component\HttpFoundation\RequestStack;

class FelibService
{
    /**
     * @var string
     */
    protected $basePath;

    /**
     * Список всех доступных скриптов.
     *
     * @var array
     */
    protected $scripts;

    /**
     * Список запрошенных библиотек.
     */
    protected $calledLibs = [];

    /**
     * Путь до ресурсов.
     *
     * @var string
     */
    protected $globalAssets;

    /**
     * @var TagcacheAdapter
     */
    protected $tagcache;

    /**
     * @param string $cacheDir
     * @param RequestStack $requestStack
     * @param TagcacheAdapter $tagcache
     */
    public function __construct($cacheDir, RequestStack $requestStack, TagcacheAdapter $tagcache)
    {
        $this->basePath     = $requestStack->getMasterRequest() ? $requestStack->getMasterRequest()->getBasePath() . '/' : '/';
        $this->globalAssets = $this->basePath . 'bundles/felib/';
        $this->tagcache     = $tagcache;
        $this->scripts      = unserialize(file_get_contents($cacheDir . '/smart_felib_libs.php.meta'));

        uasort($this->scripts, function($a, $b) {
            return ($a['proirity'] <= $b['proirity']) ? +1 : -1;
        });
    }

    /**
     * Запрос библиотеки.
     *
     * @param array|string $data
     * @param string $version
     * @param array  $files
     */
    public function call($data, $version = false, array $files = [])
    {
        if (is_array($data)) {
            foreach ($data as $name => $version) {
                $this->calledLibs[$name] = $version;
            }
        } else {
            $this->calledLibs[$data] = $version;
        }

        return $this;
    }

    /**
     * Получить список запрошенных либ.
     *
     * @return array
     */
    public function all()
    {
        $cache_key = md5('smart_felib_called_libs' . serialize($this->calledLibs) . $this->basePath);
        if (false == $output = $this->tagcache->get($cache_key)) {
            $output = [];
        } else {
            return $output;
        }

        // Т.к. запрашивается в произвольном порядке - сначала надо сформировать массив с ключами в правильном порядке.
        foreach ($this->scripts as $key => $_dummy) {
            $output[$key] = false;
        }

        // Затем вычисляются зависимости.
        $flag = 1;
        while ($flag == 1) {
            $flag = 0;
            foreach ($this->calledLibs as $name => $value) {
                $deps = isset($this->scripts[$name]) ? $this->scripts[$name]['deps'] : null;
                if (is_array($deps)) {
                    foreach ($deps as $dep) {
                        if (!empty($dep) and !isset($this->calledLibs[$dep])) {
                            $this->calledLibs[$dep] = false;
                            $flag = 1;
                        }
                    }
                } else {
                    if (!empty($deps) and !isset($this->calledLibs[$deps])) {
                        $this->calledLibs[$deps] = false;
                        $flag = 1;
                    }
                }
            }
        }

        foreach ($this->calledLibs as $name => $version) {
            if (!isset($this->scripts[$name])) {
                continue;
            }

            if (empty($version) and isset($this->scripts[$name]['version'])) {
                $version = $this->scripts[$name]['version'];
            }

            if (!empty($version)) {
                $version = $version . '/';
            }

            $path = $this->globalAssets . $name . '/' . $version;

            if (isset($this->scripts[$name]['js'])) {
                foreach ($this->scripts[$name]['js'] as $file) {
                    $output[$name]['js'][] = $path . $file;
                }
            }

            if (isset($this->scripts[$name]['css'])) {
                foreach ($this->scripts[$name]['css'] as $file) {
                    $output[$name]['css'][] = $path . $file;
                }
            }
        }

        // Удаляются пустые ключи
        foreach ($output as $key => $value) {
            if ($output[$key] === false) {
                unset($output[$key]);
            }
        }

        $this->tagcache->set($cache_key, $output, ['smart_felib']);

        return $output;
    }

    /**
     * @param string $name
     * @param string $version
     * @return array
     */
    public function getCss($name, $version = null)
    {
        return $this->getFiles('css', $name, $version);
    }

    /**
     * @param string $name
     * @param string $version
     * @return array
     */
    public function getJs($name, $version = null)
    {
        return $this->getFiles('js', $name, $version);
    }

    /**
     * @param string $type
     * @param string $name
     * @param string $version
     * @return array
     */
    protected function getFiles($type, $name, $version = null)
    {
        $files = [];

        if (empty($version)) {
            if (isset($this->scripts[$name]['version']) and !empty($this->scripts[$name]['version'])) {
                $version = $this->scripts[$name]['version'];
            }
        }

        if (!empty($version)) {
            $version = $version . '/';
        }

        $path = $this->globalAssets . $name . '/' . $version;

        if (isset($this->scripts[$name][$type])) {
            foreach ($this->scripts[$name][$type] as $file) {
                $files[] = $path . $file;
            }
        }

        return $files;
    }
}
