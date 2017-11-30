<?php

namespace SmartCore\Bundle\FelibBundle\Service;

use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class FelibService
{
    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $isDebug;

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
     * @var TaggableCacheItemPoolInterface
     */
    protected $cache;

    /**
     * @param string          $cacheDir
     * @param RequestStack    $requestStack
     * @param CacheProvider   $cache
     * @param bool            $isDebug
     */
    public function __construct($cacheDir, RequestStack $requestStack, TaggableCacheItemPoolInterface $cache, $isDebug = false)
    {
        $this->basePath     = $requestStack->getMasterRequest() ? $requestStack->getMasterRequest()->getBasePath() . '/' : '/';
        $this->globalAssets = $this->basePath . 'bundles/felib/';
        $this->isDebug      = $isDebug;
        $this->cache        = $cache;
        $this->scripts      = unserialize(file_get_contents($cacheDir . '/smart_felib_libs.php.meta'));

        uasort($this->scripts, function($a, $b) {
            return ($a['proirity'] <= $b['proirity']) ? +1 : -1;
        });
    }

    /**
     * Запрос библиотеки.
     *
     * @param array|string $data
     * @param string       $version
     * @param array        $files - перегрузка файлов ресурсов указанных по умолчанию.
     */
    public function call($data, $version = false, array $files = [])
    {
        if (is_array($data)) {
            foreach ($data as $name => $version) {
                $this->calledLibs[$name] = [
                    'version' => $version,
                    'files'   => $files,
                ];
            }
        } else {
            $this->calledLibs[$data] = [
                'version' => $version,
                'files'   => $files,
            ];
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

        if (null == $output = $this->cache->getItem($cache_key)->get()) {
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
                            $this->calledLibs[$dep] = [
                                'version' => false,
                                'files'   => [],
                            ];

                            $flag = 1;
                        }
                    }
                } else {
                    if (!empty($deps) and !isset($this->calledLibs[$deps])) {
                        $this->calledLibs[$deps] = [
                            'version' => false,
                            'files'   => [],
                        ];

                        $flag = 1;
                    }
                }
            }
        }

        foreach ($this->calledLibs as $name => $data) {
            $version = $data['version'];
            $versionCode = isset($this->scripts[$name]['version_code']) ? '?v='.$this->scripts[$name]['version_code'] : '';

            if (!isset($this->scripts[$name])) {
                continue;
            }

            if (empty($version) and isset($this->scripts[$name]['version'])) {
                $version = $this->scripts[$name]['version'];
            }

            $path = $this->globalAssets . $name . '/';

            if (!empty($version)) {
                $path .= $version . '/';
            }

            // JS
            $jsFiles = [];
            if ($this->isDebug) {
                if (isset($this->scripts[$name]['versions'][$version]['dev']['js'])) {
                    $jsFiles = $this->scripts[$name]['versions'][$version]['dev']['js'];
                } elseif (isset($this->scripts[$name]['dev']['js'])) {
                    $jsFiles = $this->scripts[$name]['dev']['js'];
                } elseif (isset($this->scripts[$name]['js'])) {
                    $jsFiles = $this->scripts[$name]['js'];
                }
            } else {
                if (isset($this->scripts[$name]['versions'][$version]['js'])) {
                    $jsFiles = $this->scripts[$name]['versions'][$version]['js'];
                } elseif (isset($this->scripts[$name]['js'])) {
                    $jsFiles = $this->scripts[$name]['js'];
                }
            }

            if (is_array($jsFiles)) {
                foreach ($jsFiles as $file) {
                    $output[$name]['js'][] = $path . $file . $versionCode;
                }
            } elseif (!empty($jsFiles)) {
                $output[$name]['js'][] = $path . $jsFiles . $versionCode;
            }

            // CSS
            $cssFiles = [];
            if ($this->isDebug) {
                if (isset($this->scripts[$name]['versions'][$version]['dev']['css'])) {
                    $cssFiles = $this->scripts[$name]['versions'][$version]['dev']['css'];
                } elseif (isset($this->scripts[$name]['dev']['css'])) {
                    $cssFiles = $this->scripts[$name]['dev']['css'];
                } elseif (isset($this->scripts[$name]['css'])) {
                    $cssFiles = $this->scripts[$name]['css'];
                }
            } else {
                if (isset($this->scripts[$name]['versions'][$version]['css'])) {
                    $cssFiles = $this->scripts[$name]['versions'][$version]['css'];
                } elseif (isset($this->scripts[$name]['css'])) {
                    $cssFiles = $this->scripts[$name]['css'];
                }
            }

            if (is_array($cssFiles)) {
                foreach ($cssFiles as $file) {
                    $output[$name]['css'][] = $path . $file . $versionCode;
                }
            } elseif (!empty($cssFiles)) {
                $output[$name]['css'][] = $path . $cssFiles . $versionCode;
            }
        }

        // Удаляются пустые ключи
        foreach ($output as $key => $value) {
            if ($output[$key] === false) {
                unset($output[$key]);
            }
        }


//        $this->cache->save($cache_key, $output);
        $item = $this->cache->getItem($cache_key);
        $item->set($output)->setTags(['smart_felib']);
        $this->cache->save($item);

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
