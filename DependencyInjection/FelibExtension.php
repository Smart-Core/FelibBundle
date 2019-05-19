<?php

namespace SmartCore\Bundle\FelibBundle\DependencyInjection;

use SmartCore\Bundle\FelibBundle\Cache\CacheProvider;
use SmartCore\Bundle\FelibBundle\Cache\DummyCacheProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class FelibExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $libs = Yaml::parse(file_get_contents(__DIR__.'/../Resources/config/libs.yml'));

        if ($config['cache_provider']) {
            $this->createCacheService($container, CacheProvider::class, $config['cache_provider']);
        } else {
            $this->createCacheService($container, DummyCacheProvider::class, $config['cache_provider']);
        }

        foreach ($libs as $name => $lib) {
            if (!isset($lib['proirity'])) {
                $libs[$name]['proirity'] = 0;
            }

            if (!isset($lib['deps'])) {
                $libs[$name]['deps'] = false;
            }

            if (isset($lib['css']) and !is_array($lib['css'])) {
                $libs[$name]['css'] = [$lib['css']];
            }

            if (isset($lib['js']) and !is_array($lib['js'])) {
                $libs[$name]['js'] = [$lib['js']];
            }
        }

        file_put_contents($container->getParameter('kernel.cache_dir') . '/smart_felib_libs.php.meta', serialize($libs));
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $cache_proviver_id
     */
    protected function createCacheService(ContainerBuilder $container, string $class, ?string $cache_proviver_id = null): void
    {
        if ($cache_proviver_id) {
            $arguments = [new Reference($cache_proviver_id)];
        } else {
            $arguments = [];
        }

        $definition = new Definition($class, $arguments);

        //$definition->setPublic(true);

        $container->setDefinition('smart_core.felib.cache',$definition);
    }
}
