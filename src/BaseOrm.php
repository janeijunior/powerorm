<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Eddmash\PowerOrm\App\Registry;
use Eddmash\PowerOrm\Console\Manager;
use Eddmash\PowerOrm\Exception\OrmException;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\ClassHelper;

define('NOT_PROVIDED', 'POWERORM_NOT_PROVIDED');

/**
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class BaseOrm extends Object
{
    const RECURSIVE_RELATIONSHIP_CONSTANT = 'this';
    /**
     * The configurations to use to connect to the database.
     *
     * It should be an array which must contain at least one of the following.
     *
     * Either 'driver' with one of the following values:
     *
     *     pdo_mysql
     *     pdo_sqlite
     *     pdo_pgsql
     *     pdo_oci (unstable)
     *     pdo_sqlsrv
     *     pdo_sqlsrv
     *     mysqli
     *     sqlanywhere
     *     sqlsrv
     *     ibm_db2 (unstable)
     *     drizzle_pdo_mysql
     *
     * OR 'driverClass' that contains the full class name (with namespace) of the
     * driver class to instantiate.
     *
     * Other (optional) parameters:
     *
     * <b>user (string)</b>:
     * The username to use when connecting.
     *
     * <b>password (string)</b>:
     * The password to use when connecting.
     *
     * <b>driverOptions (array)</b>:
     * Any additional driver-specific options for the driver. These are just passed
     * through to the driver.
     *
     * <b>pdo</b>:
     * You can pass an existing PDO instance through this parameter. The PDO
     * instance will be wrapped in a Doctrine\DBAL\Connection.
     *
     * <b>wrapperClass</b>:
     * You may specify a custom wrapper class through the 'wrapperClass'
     * parameter but this class MUST inherit from Doctrine\DBAL\Connection.
     *
     * <b>driverClass</b>:
     * The driver class to use.
     *
     * <strong>USAGE:</strong>
     *
     * [
     *       'dbname' => 'tester',
     *       'user' => 'root',
     *       'password' => 'root1.',
     *       'host' => 'localhost',
     *       'driver' => 'pdo_mysql',
     * ]
     *
     * @var array
     */
    private $databaseConfigs;

    /**
     * @var
     */
    public $charset;

    public static $instance;
    public static $SET_NULL = 'set_null';
    public static $CASCADE = 'cascade';
    public static $PROTECT = 'protect';
    public static $SET_DEFAULT = 'set_default';

    /**
     * @var Registry
     */
    private $registryCache;

    /**
     * path from where to get and put migration files.
     *
     * @var string
     */
    public $migrationPath;

    /**
     * Path from where to get the models.
     *
     * @var string
     */
    public $modelsPath;

    /**
     * The value to prefix the database tables with.
     *
     * @var string
     */
    public $dbPrefix;

    /**
     * The namespace to check for the application models and migrations.
     *
     * @var string
     */
    public $appNamespace = 'App\\';

    /**
     * Namespace used in migration.
     *
     * @internal
     *
     * @var string
     */
    public static $fakeNamespace = 'Eddmash\PowerOrm\__Fake';

    /**
     * @var Connection
     */
    public static $connection;

    /**
     * @param array $config
     * @ignore
     */
    public function __construct($config = [])
    {
        self::configure($this, $config);

        // setup the registry
        $this->registryCache = Registry::createObject();

        if (empty($this->migrationPath)):
            $this->migrationPath = sprintf('%smigrations%s', APPPATH, DIRECTORY_SEPARATOR);
        endif;
        if (empty($this->modelsPath)):
            $this->modelsPath = sprintf('%smodels%s', APPPATH, DIRECTORY_SEPARATOR);
        endif;
        self::getDatabaseConnection();
    }

    public static function getModelsPath()
    {
        return self::getInstance()->modelsPath;
    }

    public static function getMigrationsPath()
    {
        return self::getInstance()->migrationPath;
    }

    public static function getCharset() {
        return self::getInstance()->charset;
    }

    //********************************** ORM Registry*********************************

    /**
     * Returns the numeric version of the orm.
     *
     * @return string
     */
    public function getVersion()
    {
        if (defined('POWERORM_VERSION')):
            return POWERORM_VERSION;
        endif;
    }

    /**
     * @deprecated
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function version()
    {
        return $this->getVersion();
    }

    /**
     * Returns the application registry. This method populates the registry the first time its invoked and caches it since
     * its a very expensive method. subsequent calls get the cached registry.
     *
     * @return Registry
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function getRegistry()
    {
        $orm = static::getInstance();

        if (!$orm->registryCache->isAppReady()):
            $orm->registryCache->populate();
        endif;

        return $orm->registryCache;
    }

    /**
     * This is just a shortcut method. get the current instance of the orm.
     *
     * @return BaseOrm
     */
    public static function &getInstance($config = null)
    {
        $instance = null;

        if (ENVIRONMENT == 'POWERORM_DEV'):
            $instance = static::_standAloneEnvironment($config);

        else:
            $instance = static::getOrmFromContext();
        endif;

        return $instance;
    }

    public static function getOrmFromContext()
    {
        $ci = static::getCiObject();
        if(!isset($ci->orm)):
            $message = 'The ORM has not been loaded yet. On Codeigniter 3, ensure to add the '.
                '$autoload[\'libraries\'] = array(\'powerorm/orm\'). On the autoload.php';

            throw new OrmException($message);
        endif;
        $orm = &$ci->orm;

        return $orm;
    }

    public static function _standAloneEnvironment($config)
    {
        return static::createObject($config);
    }

    /**
     * @return \CI_Controller
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function &getCiObject()
    {
        return \get_instance();
    }

    /**
     * @return Connection
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function getDbConnection()
    {
        return self::getInstance()->getDatabaseConnection();
    }

    /**
     * Returns the prefix to use on database tables.
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function getDbPrefix()
    {
        return self::getInstance()->dbPrefix;
    }

    /**
     * @return Connection
     *
     * @throws OrmException
     * @throws \Doctrine\DBAL\DBALException
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getDatabaseConnection()
    {
        if (empty($this->databaseConfigs)):

            $message = 'The database configuration have no been provided, On Codeigniter 3 create orm.php and '.
                'add configuration, consult documentation for options';
            throw new OrmException($message);
        endif;
        if (static::$connection == null):
            $config = new Configuration();

            static::$connection = DriverManager::getConnection($this->databaseConfigs, $config);
        endif;

        return static::$connection;
    }

    /**
     * Configures an object with the initial property values.
     *
     * @param object $object     the object to be configured
     * @param array  $properties the property initial values given in terms of name-value pairs
     * @param array  $map        if set the the key should be a key on the $properties and the value should a a property on
     *                           the $object to which the the values of $properties will be assigned to
     *
     * @return object the object itself
     */
    public static function configure($object, $properties, $map = [])
    {
        if (empty($properties)):
            return $object;
        endif;

        foreach ($properties as $name => $value) :

            if (ArrayHelper::hasKey($map, $name)):

                $name = $map[$name];
            endif;

            if (property_exists($object, $name)):
                $object->$name = $value;
            endif;

        endforeach;

        return $object;
    }

    public static function createObject($config = [])
    {
        if (static::$instance == null):

            if (ENVIRONMENT == 'POWERORM_DEV'):
                require POWERORM_BASEPATH.DIRECTORY_SEPARATOR.'config.php';
            endif;

            static::$instance = new static($config);
        endif;

        return static::$instance;
    }

    public static function consoleRunner($config = [])
    {
        Manager::run();
    }

    /**
     * The fake namespace to use in migration.
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function getFakeNamespace()
    {
        return self::$fakeNamespace;
    }

    public static function getModelsNamespace()
    {
        $namespace = ClassHelper::getFormatNamespace(self::getInstance()->appNamespace, true);

        return ClassHelper::getFormatNamespace(sprintf('%s%s', $namespace, 'models'), true, false);
    }

    public static function getMigrationsNamespace()
    {
        $namespace = ClassHelper::getFormatNamespace(self::getInstance()->appNamespace, true);

        return ClassHelper::getFormatNamespace(sprintf('%s%s', $namespace, 'Migrations'), true, false);
    }
}
