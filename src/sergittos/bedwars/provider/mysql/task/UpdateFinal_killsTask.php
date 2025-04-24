<?php

declare(strict_types=1);


namespace sergittos\bedwars\provider\mysql\task;


use mysqli;
use sergittos\bedwars\provider\mysql\MysqlAsyncTask;
use sergittos\bedwars\session\Session;

class updateFinal_kills extends MysqlAsyncTask {

    private string $xuid;
    private int $final_kills;

    public function __construct(Session $session) {
        $this->xuid = $session->getPlayer()->getXuid();
        $this->final_kills = $session->getFinalKills();
        parent::__construct();
    }

    protected function onConnection(mysqli $mysqli): void {
        $stmt = $mysqli->prepare("UPDATE bedwars_users SET final_kills = ? WHERE xuid = ?");
        $stmt->bind_param("is", ...[$this->final_kills, $this->xuid]);
        $stmt->execute();
        $stmt->close();
    }

}