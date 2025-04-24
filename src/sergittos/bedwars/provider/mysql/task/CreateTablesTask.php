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
                wins INT  DEFAULT 0,
                levels float DEFAULT 1,
                deaths INT DEFAULT 0,
                final_kills INT DEFAULT 0,
                xp INT  DEFAULT 0,
    
                
                flying_speed INT,
                auto_teleport BOOL,
                night_vision BOOL
            )"
        );
    }

}