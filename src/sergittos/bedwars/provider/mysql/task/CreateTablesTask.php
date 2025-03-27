<?php

declare(strict_types=1);


namespace sergittos\bedwars\provider\mysql\task;


use mysqli;
use sergittos\bedwars\provider\mysql\MysqlAsyncTask;

class CreateTablesTask extends MysqlAsyncTask {

    protected function onConnection(mysqli $mysqli): void {
    $mysqli->query(
        "CREATE TABLE IF NOT EXISTS bedwars_users (
            xuid VARCHAR(16) PRIMARY KEY,
            coins INT DEFAULT 0,
            kills INT DEFAULT 0,
            wins INT DEFAULT 0,
            level INT DEFAULT 1,
            
            flying_speed INT DEFAULT 0,
            auto_teleport BOOL DEFAULT FALSE,
            night_vision BOOL DEFAULT FALSE
        )"
    );
}

}