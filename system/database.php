<?php


use dbpp\attrs\Query;
use dbpp\Dao;
use dbpp\Database;

class MainDatabase extends Database {
    public BotsDao $bots;
}

class BotsDao extends Dao {
    #[Query("SELECT * FROM `bots` WHERE `group_id` = :groupId AND `secret_key` = :secretCode")]
    public function get(int $groupId, string $secretCode): array|false {
        return parent::get($groupId, $secretCode);
    }
}