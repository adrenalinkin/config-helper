Config Helper [![На Русском](https://img.shields.io/badge/Перейти_на-Русский-green.svg?style=flat-square)](./README.RU.md)
=============

Introduction
------------

Component allows extend standard class `Symfony\Component\DependencyInjection\Extension\Extension` and open possibility
for collect `YAML` configurations across all registered bundles.

Installation
------------

Open a command console, enter your project directory and execute the following command to download the latest stable
version of this component:
```text
    composer require adrenalinkin/config-helper
```
*This command requires you to have [Composer](https://getcomposer.org) install globally.*

Usage examples and compare with standard methods
------------------------------------------------

Let's say we have two bundles in our project. Bundles contains business-logic of the two separate system parts:
 * `AcmeBundle` with entities `AcmeBundle:User` and `AcmeBundle:Position`
 * `AcmePostBundle` with entity `AcmePostBundle:Post`

Imagine you need to add configuration for each entity. Let's say we need configuration which should determine
user's system role for get access to specific functionality. For the configuration creation has been created bundle
`AcmeConfigBundle`. Configuration example:

```yaml
acme_config:
    AcmeBundle:User:     ROLE_USER_ADMIN # key - name of the entity; value - role
    AcmeBundle:Position: ROLE_USER_ADMIN
    AcmePostBundle:Post: ROLE_POST_ADMIN
```

### Standard methods

We can put configuration into global configuration file `app/config/config.yml`:

```yaml
# app/config/config.yml

# other bundle's configurations

acme_config:
    AcmeBundle:User:     ROLE_USER_ADMIN
    AcmeBundle:Position: ROLE_USER_ADMIN
    AcmePostBundle:Post: ROLE_POST_ADMIN

# other bundle's configurations
```

Also, we can put configuration into `AcmeConfigBundle` bundle under specific configuration file and load that from
`AcmeConfigExtension`:

```yaml
#Acme/ConfigBundle/Resources/config/custom.yml
acme_config:
    AcmeBundle:User:     ROLE_USER_ADMIN
    AcmeBundle:Position: ROLE_USER_ADMIN
    AcmePostBundle:Post: ROLE_POST_ADMIN
```

However, both of method, for our realisation, got one flaw. All time when we will create new bundles - we will need
modify global configuration file or configuration file in the `AcmeConfigBundle`.
**Both situation provoke a hard-linking between separate parts of the project**

### Component usage

To prevent hard-linkin between separate parts of the project you need:

* Choose file name for configuration store, for example `acme_config.yml`.
* Extends `AcmeConfigExtension` from [AbstractExtension](./Extension/AbstractExtension.php):
```php
<?php

namespace Acme\Bundle\ConfigBundle\DependencyInjection;

use Linkin\Component\ConfigHelper\Extension\AbstractExtension;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;

class AcmeConfigExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'acme_config';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // load all configurations from the all registered bundles
        $configs = $this->getConfigurationsFromFile('acme_config.yml', $container);
        // process received configuration
        $configs = $this->processConfiguration(new Configuration(), $configs);
        
        // some actions

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
```
* Create configuration files per bundles:
```yaml
# Acme/AcmeBundle/Resources/config/acme_config.yml
acme_config:
    AcmeBundle:User:     ROLE_USER_ADMIN
    AcmeBundle:Position: ROLE_USER_ADMIN
```
```yaml
# AcmePost/AcmePostBundle/Resources/config/acme_config.yml
acme_config:
    AcmePostBundle:Post: ROLE_POST_ADMIN
```

This method allows you create and remove configurations in the bundles without global changes in the project. You can
remove some configuration in the needed bundle or even remove whole bundle ( for example `AcmePostBundle`).

**Note**: By default method `getConfigurationsFromFile($fileName, ContainerBuilder $container, $merge = true)` uses
standard PHP function [array_merge_recursive](http://php.net/manual/en/function.array-merge-recursive.php) to merge
all found configurations. If you want prepare configuration by yourself put `false` as third parameter and receive
stack of the all registered configurations.

License
-------

[![license](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](./LICENSE)
