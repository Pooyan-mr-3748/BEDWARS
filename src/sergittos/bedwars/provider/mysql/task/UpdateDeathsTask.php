<?php

declare(strict_types=1);


namespace sergittos\bedwars\provider\mysql\task;


use mysqli;
use sergittos\bedwars\provider\mysql\MysqlAsyncTask;
use sergittos\bedwars\session\Session;

class UpdateDeathsTask extends MysqlAsyncTask {

    private string $xuid;
    private int $deaths;

    public function __construct(Session $session) {
        $this->xuid = $session->getPlayer()->getXuid();
        $this->deaths = $session->getdeaths();
        parent::__construct();
    }

    protected function onConnection(mysqli $mysqli): void {
        $stmt = $mysqli->prepare("UPDATE bedwars_users SET deaths = ? WHERE xuid = ?");
        $stmt->bind_param("is", ...[$this->deaths, $this->xuid]);
        $stmt->execute();
        $stmt->close();
    }

}