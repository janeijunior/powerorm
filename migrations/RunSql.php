<?php
/**
 * Creates sql statements.
 */

/**
 *
 */
namespace powerorm\migrations;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Provides consistent api and elegant for migrations
 * @package powerorm\migrations
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class RunSql{
    /**
     * @ignore
     */
    public static $_ci;

    /**
     * @var string
     * @ignore
     */
    public static $before_insert = "BEFORE INSERT";

    /**
     * Does some setup.
     * @ignore.
     */
    public static function init(){
        RunSql::$_ci = & get_instance();
    }

    /**
     * Drops a table column.
     * @param string $table the affected table.
     * @param string $field_name the affected field.
     */
    public static function drop_column($table, $field_name){
        RunSql::init();
        RunSql::$_ci->dbforge->drop_column($table, $field_name);
    }

    /**
     * Adds field to a table used together with create_table, and never alone.
     * @param string $field the affected field.
     */
    public static function add_field($field){
        RunSql::init();
        RunSql::$_ci->dbforge->add_field($field);
    }

    /**
     * Add a column to the table.
     * @param string $table affected table.
     * @param string $field affected field.
     */
    public static function add_column($table, $field){
        RunSql::init();
        RunSql::$_ci->dbforge->add_column("$table", "$field");
    }

    /**
     * @param string $table table name
     * @param array $field_options an array of affected field containting and inner array of affected options.e.g
     * ['name'=>['type'=>'text']]
     */
    public static function modify_column($table, $field_options){
        RunSql::init();

        RunSql::$_ci->dbforge->modify_column("$table", $field_options);
    }

    /**
     * Custom drop beyond what CI offers.
     * @param string $table affected field.
     * @param string $field affected field.
     */
    public static function power_drop_column($table, $field){
        RunSql::init();
        RunSql::$_ci->db->query("ALTER TABLE $table DROP $field");
    }

    /**
     * Drops constraints and fields.
     * @param string $table affected table.
     * @param string $field field/constraint to drop.
     */
    public static function drop_constraint($table, $field){
        RunSql::power_drop_column($table, $field);
    }

    /**
     * Adds constraints used together with create_table never alone.
     * @param string $field affected field.
     */
    public static function add_field_constraint($field){
        RunSql::add_field($field);
    }

    /**
     * Alters table to add constraints,
     * @param string $field affected field.
     * @param $table
     */
    public static function add_column_constraint($table, $field){
        RunSql::init();
        RunSql::$_ci->dbforge->add_column("$table", "$field");
    }
    /**
     * Custom add beyond what CI offers.
     * @param string $table affected field.
     * @param string $field affected field.
     */
    public static function power_add_column($table, $field){
        RunSql::init();
        RunSql::$_ci->db->query("ALTER TABLE $table ADD $field");
    }
    /**
     * Custom modify beyond what CI offers.
     * @param string $table affected field.
     * @param string $field affected field.
     */
    public static function power_modify_column($table, $field){
        RunSql::init();
        RunSql::$_ci->db->query("ALTER TABLE $table MODIFY $field");
    }

    /**
     * Creates a database table.
     * @param string $name the name of the table to create.
     * @param bool|FALSE $check_exist if TRUE checks if table exists.
     * @param array $attrs any other attributes required fro table creation.
     */
    public static function create_table($name, $check_exist=FALSE, $attrs=['ENGINE'=>'InnoDB']){
        RunSql::init();
        RunSql::$_ci->dbforge->create_table("$name", $check_exist, $attrs);
    }

    /**
     * Drops a table from the database.
     * @param string $name name of table to drop.
     * @param bool|FALSE $check_exist if TRUE checks if table already exists.
     */
    public static function drop_table($name, $check_exist=FALSE){
        RunSql::init();
        RunSql::$_ci->dbforge->drop_table("$name", $check_exist);
    }

    /**
     * Creates triggers in the database.
     * @param string $time  at what time should the trigger be invoked e.g. BEFORE,AFTER.
     * @param string $when what happens for trigger to be invoked e.g. INSERT, UPDATE, DELETE.
     * @param string $table name of affected table.
     * @param array $fields an array of field names that the trigger will act on.
     */
    public static function create_trigger($time, $when, $table, $fields){
        $conditions = '';

        foreach ($fields as $field) :
            $conditions .=sprintf('set NEW.%1$s= %2$s;', $field , 'now()');
        endforeach;

        $name = sprintf('%1$s_%2$s_%3$s', $table, strtolower($time), strtolower($when));
        $trigger = "CREATE TRIGGER $name $time $when ON $table FOR EACH ROW BEGIN $conditions END ;";



        RunSql::init();

        RunSql::$_ci->db->query($trigger);
    }

    /**
     * Drops triggers from the database.
     * @param string $time  at what time should the trigger be invoked e.g. BEFORE,AFTER.
     * @param string $when what happens for trigger to be invoked e.g. INSERT, UPDATE, DELETE.
     * @param string $table affected table.
     */
    public static function drop_trigger($time, $when, $table){

        $name = sprintf('%1$s_%2$s_%3$s', $table, strtolower($time), strtolower($when));
        $trigger = sprintf("DROP TRIGGER IF EXISTS  %s ;", $name);

        RunSql::init();

        RunSql::$_ci->db->query($trigger);
    }

    /**
     * We use this to show in the migration file that the current model being created a many to many relation
     */
    public function many_to_many_field($through){
    
    }
}