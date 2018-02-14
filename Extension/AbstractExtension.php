<?php

/*
 * This file is part of the ConfigHelper component package.
 *
 * (c) Viktor Linkin <adrenalinkin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Linkin\Component\ConfigHelper\Extension;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
abstract class AbstractExtension extends Extension
{
    /**
     * List of the path to configuration directories of the all registered bundles
     *
     * @var array
     */
    private static $directoriesCache = [];

    /**
     * Returns all configurations registered in the specific yaml file.
     *
     * @param string           $fileName  Name of the file with extension
     * @param ContainerBuilder $container Container builder
     *
     * @return array
     */
    protected function getConfigurationsFromFile($fileName, ContainerBuilder $container)
    {
        $configs = [];

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($this->getFinder($fileName, $container) as $file) {
            $configs = array_merge_recursive($configs, Yaml::parse($file->getContents()));
        }

        return $configs;
    }

    /**
     * Build and return finder
     *
     * @param string           $fileName
     * @param ContainerBuilder $container
     *
     * @return Finder
     */
    private function getFinder($fileName, ContainerBuilder $container)
    {
        $finder       = (new Finder())->files()->name($fileName);
        $resourcesDir = 'Resources'.DIRECTORY_SEPARATOR.'config';

        if (self::$directoriesCache) {
            return $finder->in(self::$directoriesCache);
        }

        foreach ($container->getParameter('kernel.bundles') as $name => $pathToBundle) {
            try {
                $reflector = new \ReflectionClass($pathToBundle);
            } catch (\ReflectionException $e) {
                continue;
            }

            $fileName = $reflector->getFileName();
            $fileName = str_replace($name.'.php', $resourcesDir, $fileName);

            try {
                $finder->in($fileName);
                self::$directoriesCache[$name] = $fileName;
            } catch (\InvalidArgumentException $e) {
                // remove invalid directories
                unset(self::$directoriesCache[$name]);
            }
        }

        return $finder;
    }
}
