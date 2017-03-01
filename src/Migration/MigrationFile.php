<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration;

use Eddmash\PowerOrm\Helpers\Tools;

/**
 * This class represent a migration file.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class MigrationFile
{
    /**
     * @var Migration
     */
    private $migration;

    /**
     * @param Migration $migration
     */
    public function __construct($migration)
    {
        $this->migration = $migration;
    }

    /**
     * Create instance.
     *
     * @param $migration
     *
     * @return static
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function createObject($migration)
    {
        return new static($migration);
    }

    /**
     * The name of the migration file.
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getFileName()
    {
        return sprintf('%s.%s', $this->migration->getName(), 'php');
    }

    /**
     * Converts migration object into a string and adds it to the migration file temple, ready to be written on disk.
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getContent()
    {
        $imports = [];

        $stringedOperations = [];

        foreach ($this->migration->getOperations() as $op) :
            list($opString, $importString) = FormatFileContent::formatObject($op);

            array_push($stringedOperations, $opString);

            $imports = array_merge($imports, $importString);
        endforeach;

        $imports = array_unique($imports);

        $importPaths = '';

        foreach ($imports as $import) :
            $import = sprintf('use %s;', $import);
            $importPaths .= $import.PHP_EOL;
        endforeach;

        $opContent = '['.PHP_EOL;
        foreach ($stringedOperations as $op) :
            $opContent .= sprintf("\t\t\t%s,".PHP_EOL, $op);
        endforeach;
        $opContent .= "\t\t]";

        $dependencies = $this->migration->getDependency();

        $indent = (count($dependencies) <= 1) ? false : 3;

        return sprintf(
            $this->getFileTemplate(),
            $importPaths,
            $this->migration->getName(),
            Tools::stringify($dependencies, $indent),
            $opContent
        );
    }

    /**
     * Creates the template for the migration file.
     *
     * @return static
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function getFileTemplate()
    {
        $content = FormatFileContent::createObject();
        $content->addItem('<?php');
        $content->addItem(
            PHP_EOL.sprintf(
                '/**Migration file generated at %s on %s by PowerOrm(%s)*/',
                date('h:m:i'),
                date('D, jS F Y'),
                POWERORM_VERSION
            ).PHP_EOL
        );

        $content->addItem('namespace App\\Migrations;'.PHP_EOL);

        $content->addItem('use Eddmash\\PowerOrm\\Migration\\Migration;');
        $content->addItem('%1$s');

        $content->addItem('class %2$s extends Migration{'.PHP_EOL);

        $content->addIndent();
        $content->addItem('public function getDependency(){');

        $content->addIndent();
        $content->addItem('return %3$s;');
        $content->reduceIndent();

        $content->addItem('}'.PHP_EOL);

        $content->addItem('public function getOperations(){');

        $content->addIndent();
        $content->addItem('return %4$s ;');
        $content->reduceIndent();

        $content->addItem('}'.PHP_EOL);
        $content->reduceIndent();

        $content->addItem('}');

        return $content;
    }
}
