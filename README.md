Check and sync database structure with filesystem and subversion
===================
Database Backup and Restore functionality

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist helmut-kleinhans/yii2-dbtools "*"
```

or add

```
"helmut-kleinhans/yii2-dbtools": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply add it in your config by  :

Basic ```config/web.php```

Advanced ```[backend|frontend|common]/config/main.php```

>
        'modules'    => [
            'dbtools' => [
                'class' => 'helmut-kleinhans\modules\dbtools\Module',
            ],
            ...
            ...
        ],

Usage
-----

Pretty Url's ```/dbtools```

No pretty Url's ```index.php?r=dbtools```
