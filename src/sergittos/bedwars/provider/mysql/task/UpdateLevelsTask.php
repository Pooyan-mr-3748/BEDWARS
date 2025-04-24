<?php

declare(strict_types=1);


namespace sergittos\bedwars\provider\mysql\task;


use mysqli;
use sergittos\bedwars\provider\mysql\MysqlAsyncTask;
use sergittos\bedwars\session\Session;

class UpdateLevelsTask extends MysqlAsyncTask {

    private string $xuid;
    private float $levels;

    public function __construct(Session $session) {
        $this->xuid = $session->getPlayer()->getXuid();
        $this->levels = $session->getLevel();
        parent::__construct();
    }

    protected function onConnection(mysqli $mysqli): void {
        $stmt = $mysqli->prepare("UPDATE bedwars_users SET levels = ? WHERE xuid = ?");
        $stmt->bind_param("is", ...[$this->levels, $this->xuid]);
        $stmt->execute();
        $stmt->close();
    }

}