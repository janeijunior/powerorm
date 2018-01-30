<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\App;

use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Signals\SignalManagerInterface;

class Settings
{
    private $dateFormats = [
        'Y-m-d',      // '2006-10-25'
        'm/d/Y',      // '10/25/2006'
        'm/d/y',     // '10/25/06'
    ];

    private $timeFormats = [
        'H:i:s',     // '14:30:59'
        'H:i:s.u',  // '14:30:59.000200'
        'H:i',        // '14:30'
    ];

    /**
     * @var SignalManagerInterface event manager to use
     */
    private $signalManager;

    private $timezone = '';

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
    private $database;

    /**
     * @var
     */
    private $charset = 'utf-8';

    /**
     * The value to prefix the database tables with.
     *
     * @var string
     */
    private $dbPrefix = '';

    /**
     * A list of identifiers of messages generated by the system check (e.g. ["models.W001"]) that you wish to
     * permanently acknowledge and ignore.
     *
     * Silenced warnings will no longer be output to the console;
     * silenced errors will still be printed, but will not prevent management commands from running.
     *
     * @var array
     */
    private $silencedChecks = [];

    private $components;

    /**
     * Settings constructor.
     *
     * @param array $configs
     *
     * @throws \Eddmash\PowerOrm\Exception\KeyError
     */
    public function __construct(array $configs)
    {
        $this->signalManager = ArrayHelper::pop(
            $configs,
            'signalManager',
            null
        );

        ClassHelper::setAttributes($this, $configs);
        if (!$this->components):
            $this->components = []; // incase nothing is set, guarantee it iterable
        endif;
    }

    /**
     * @return array
     */
    public function getDateFormats()
    {
        return $this->dateFormats;
    }

    /**
     * @param array $dateFormats
     */
    public function setDateFormats($dateFormats)
    {
        $this->dateFormats = $dateFormats;
    }

    /**
     * @return array
     */
    public function getTimeFormats()
    {
        return $this->timeFormats;
    }

    /**
     * @param array $timeFormats
     */
    public function setTimeFormats($timeFormats)
    {
        $this->timeFormats = $timeFormats;
    }

    /**
     * @return SignalManagerInterface
     */
    public function getSignalManager()
    {
        return $this->signalManager;
    }

    /**
     * @param SignalManagerInterface $signalManager
     */
    public function setSignalManager($signalManager)
    {
        $this->signalManager = $signalManager;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param string $timezone
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * @return array
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @param array $database
     */
    public function setDatabase($database)
    {
        $this->database = $database;
    }

    /**
     * @return mixed
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * @param mixed $charset
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    /**
     * @return string
     */
    public function getDbPrefix()
    {
        return $this->dbPrefix;
    }

    /**
     * @param string $dbPrefix
     */
    public function setDbPrefix($dbPrefix)
    {
        $this->dbPrefix = $dbPrefix;
    }

    /**
     * @return array
     */
    public function getSilencedChecks()
    {
        return $this->silencedChecks;
    }

    /**
     * @param array $silencedChecks
     */
    public function setSilencedChecks($silencedChecks)
    {
        $this->silencedChecks = $silencedChecks;
    }

    /**
     * @return mixed
     */
    public function getComponents()
    {
        return $this->components;
    }

    /**
     * @param mixed $components
     */
    public function setComponents($components)
    {
        $this->components = $components;
    }
}
