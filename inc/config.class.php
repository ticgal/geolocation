<?php

/**
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class Plugin0GLPIXxConfig extends CommonDBTM
{
    public static $rightname = 'config';

    private static $instance = null;

    public function __construct()
    {
        /** @var \DBmysql $DB */
        global $DB;
        if ($DB->tableExists($this->getTable())) {
            $this->getFromDB(1);
        }
    }

    /**
     * getTypeName
     *
     * @param  mixed $nb
     * @return string
     */
    public static function getTypeName($nb = 0): string
    {
        return '0GLPIXO';
    }

    /**
     * getInstance
     *
     * @param  int $n
     * @return Plugin0GLPIXxConfig
     */
    public static function getInstance(int $n = 1): Plugin0GLPIXxConfig
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
            if (!self::$instance->getFromDB($n)) {
                self::$instance->getEmpty();
            }
        }

        return self::$instance;
    }

    /**
     * install
     *
     * @param  Migration $migration
     * @return void
     */
    public static function install(Migration $migration): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $default_charset    = DBConnection::getDefaultCharset();
        $default_collation  = DBConnection::getDefaultCollation();
        $default_key_sign   = DBConnection::getDefaultPrimaryKeySignOption();

        $table = self::getTable();
        if (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");
            $query = "CREATE TABLE IF NOT EXISTS $table (
				`id` INT {$default_key_sign} NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET={$default_charset}
            COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
            $DB->request($query) or die($DB->error());

            $config = new self();
            $config->add([
                'id' => 1,
            ]);
        } else {
            // Migrations
            // $migration->migrationOneTable($table);
        }
    }
}
