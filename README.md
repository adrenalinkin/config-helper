Config Helper
=============

Компонент позволяет расширить стандартный класс `Symfony\Component\DependencyInjection\Extension\Extension` и получить
доступ к сбору конфигурации `YML` среди всех зарегистрированных бандлов проекта.

Пример использования и сравнение со стандартными методами
---------------------------------------------------------

Допустим что в нашем проекте существуют два бандла, хранящие бизнес-логику двух обособленных частей системы:
 * `EntityUserBundle` с сущностями `EntityUserBundle:User` и `EntityUserBundle:Position`
 * `EntityPostBundle` с сущностью `EntityUserBundle:Post`

Представьте что вам понадобилось добавить конфигурацию с для каждой сущности. Допустим что конфигурация должна
определять роль пользователя обладая которой он получит доступ к расширенной настройке сущности в веб-интерфейсе.
Для создания конфигурации создан бандл `AcmeConfigBundle`, который позволяет регистрировать конфигурацию для разных
сущностей вашего проекта. Конечная конфигурация в формате `YML` будет выглядеть следующим образом:

``` yml
acme_config:
    EntityUserBundle:User:     ROLE_USER_ADMIN # ключ - это имя класса сущности, а значение - роль
    EntityUserBundle:Position: ROLE_USER_ADMIN
    EntityPostBundle:Post:     ROLE_POST_ADMIN
```

### Стандартные подходы

Конечно можно хранить конфигурацию в глобальном файле `app/config/config.yml`:

``` yml
# app/config/config.yml

# конфигурация других бандлов

acme_config:
    EntityUserBundle:User:     ROLE_USER_ADMIN
    EntityUserBundle:Position: ROLE_USER_ADMIN
    EntityPostBundle:Post:     ROLE_POST_ADMIN

# конфигурация других бандлов

```

Или поместить конфигурацию внутри бандла в свой собственный файл и затем загружать ее оттуда в `AcmeConfigExtension`

``` yml
# Acme/ConfigBundle/Resources/config/custom.yml
acme_config:
    EntityUserBundle:User:     ROLE_USER_ADMIN
    EntityUserBundle:Position: ROLE_USER_ADMIN
    EntityPostBundle:Post:     ROLE_POST_ADMIN
```

Однако оба эти способа хранения конфигурации, для нашей реализации, имеют один существенный недостаток - при появлении
новых бандлов нам придется каждый раз модифицировать глобальный файл конфигурации или файл конфигурации внутри бандла.
**В обоих случаях появляется жесткая связь бандлов и затрудняется сопровождение проекта.**

### Что предлагает компонент

Для устранения появления нежелательных связей в проекте на основании конфигураций в компоненте разработан следующий
способ:

1. Необходимо придумать название файла, в котором будет храниться ваша конфигурация, например `acmeConfig.yml`.
2. Необходимо унаследовать `AcmeConfigExtension` от [AbstractExtension](../Extension/AbstractExtension.php):
``` php
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
        $configs    = $this->getConfigurationsFromFile('acmeConfig.yml', $container);
        // обрабатываем полученную конфигурацию
        $configs    = $this->processConfiguration(new Configuration(), $configs);
        
        // необходимые действия

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }
}
```
3. Создайте конфигурационные файлы в тех бандлах, где это необходимо
``` yml
# Entity/UserBundle/Resources/config/acmeConfig.yml
acme_config:
    EntityUserBundle:User:     ROLE_USER_ADMIN
    EntityUserBundle:Position: ROLE_USER_ADMIN
```
``` yml
# Entity/PostBundle/Resources/config/acmeConfig.yml
acme_config:
    EntityPostBundle:Post: ROLE_POST_ADMIN
```

Данный подход позволяет вам создавать и удалять конфигурации в конкретных бандлах и при этом изменения не потребуются в
проекте глобально если вы удалите один из файлов или целый бандл (`EntityPostBundle` например).
Более того - вы свободно сможете удалить и бандл-носитель конфигурации `AcmeConfigBundle` без необходимости удаления
конфигураций.
