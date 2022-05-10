<?php


class DB
{

    protected static $connection;

    public static function getConnection()
    {
        if (!self::$connection) {
            self::$connection = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);;
        }
        return self::$connection;
    }

}