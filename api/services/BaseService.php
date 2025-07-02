<?php
require_once __DIR__.'/../Database.php';

abstract class BaseService {
    protected $pdo;

    public function __construct() {
        try {
            $this->pdo = Database::getInstance()->getConnection();
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
