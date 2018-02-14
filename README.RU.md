Config Helper [![In English](https://img.shields.io/badge/Switch_To-English-green.svg?style=flat-square)](./README.md)
=============

Введение
--------

Компонент позволяет расширить стандартный класс `Symfony\Component\DependencyInjection\Extension\Extension` и получить
доступ к сбору конфигурации `YAML` среди всех зарегистрированных бандлов проекта.

Установка
---------

Откройте консоль и, перейдя в директорию проекта, выполните следующую команду для загрузки наиболее подходящей
стабильной версии этого компонента:
```text
    composer require adrenalinkin/config-helper
```
*Эта команда подразумевает что [Composer](https://getcomposer.org) установлен и доступен глобально.*

Пример использования и сравнение со стандартными методами
---------------------------------------------------------

Допустим что в нашем проекте существуют два бандла, хранящие бизнес-логику двух обособленных частей системы:
 * `AcmeBundle` с сущностями `AcmeBundle:User` и `AcmeBundle:Position`
 * `AcmePostBundle` с сущностью `AcmePostBundle:Post`

Представьте что вам понадобилось добавить конфигурацию для каждой сущности. Допустим что конфигурация должна
определять роль пользователя, обладая которой, он получит доступ к специфичиским функциям.
Для создания конфигурации создан бандл `AcmeConfigBundle`. Конечная конфигурация в формате `YAML`
будет выглядеть следующим образом:

```yaml
acme_config:
    AcmeBundle:User:     ROLE_USER_ADMIN # ключ - это имя класса сущности, а значение - роль
    AcmeBundle:Position: ROLE_USER_ADMIN
    AcmePostBundle:Post: ROLE_POST_ADMIN
```

### Стандартные подходы

Конечно можно хранить конфигурацию в глобальном файле `app/config/config.yml`:

```yaml
# app/config/config.yml

# конфигурация других бандлов

acme_config:
    AcmeBundle:User:     ROLE_USER_ADMIN
    AcmeBundle:Position: ROLE_USER_ADMIN
    AcmePostBundle:Post: ROLE_POST_ADMIN

# конфигурация других бандлов
```

Или поместить конфигурацию внутри бандла в свой собственный файл и затем загружать ее оттуда в `AcmeConfigExtension`

```yaml
#Acme/ConfigBundle/Resources/config/custom.yml
acme_config:
    AcmeBundle:User:     ROLE_USER_ADMIN
    AcmeBundle:Position: ROLE_USER_ADMIN
    AcmePostBundle:Post: ROLE_POST_ADMIN
```

Однако оба эти способа хранения конфигурации, для нашей реализации, имеют один существенный недостаток - при появлении
новых бандлов нам придется каждый раз модифицировать глобальный файл конфигурации или файл конфигурации внутри бандла.
**В обоих случаях появляется жесткая связь бандлов и затрудняется сопровождение проекта.**

### Использование компонента

Для устранения появления нежелательных связей в проекте необходимо:

 * Определить название файла для храениения конфигурации, например `acmeConfig.yml`.
 * Унаследовать `AcmeConfigExtension` от [AbstractExtension](./Extension/AbstractExtension.php):
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
        // загружаются конфигурации из всех бандлов из файла acmeConfig.yml
        $configs = $this->getConfigurationsFromFile('acmeConfig.yml', $container);
        // обрабатываем полученную конфигурацию
        $configs = $this->processConfiguration(new Configuration(), $configs);
        
        // необходимые действия

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
```
 * Создать конфигурационные файлы в тех бандлах, где это необходимо:
```yaml
# Acme/AcmeBundle/Resources/config/acmeConfig.yml
acme_config:
    AcmeBundle:User:     ROLE_USER_ADMIN
    AcmeBundle:Position: ROLE_USER_ADMIN
```
```yaml
# AcmePost/AcmePostBundle/Resources/config/acmeConfig.yml
acme_config:
    AcmePostBundle:Post: ROLE_POST_ADMIN
```

Данный подход позволяет вам создавать и удалять конфигурации в конкретных бандлах и при этом изменения не потребуются в
проекте глобально если вы удалите один из файлов или целый бандл (`AcmePostBundle` например).

Лицензия
--------

[![license](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](./LICENSE)
