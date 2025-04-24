<?php

declare(strict_types=1);


namespace sergittos\bedwars\provider\mysql\task;


use mysqli;
use sergittos\bedwars\provider\mysql\MysqlAsyncTask;
use sergittos\bedwars\session\Session;

class UpdateXpTask extends MysqlAsyncTask {

    private string $xuid;
    private int $xp;

    public function __construct(Session $session) {
        $this->xuid = $session->getPlayer()->getXuid();
        $this->xp = $session->getXp();
        parent::__construct();
    }

    protected function onConnection(mysqli $mysqli): void {
        $stmt = $mysqli->prepare("UPDATE bedwars_users SET xp = ? WHERE xuid = ?");
        $stmt->bind_param("is", ...[$this->xp, $this->xuid]);
        $stmt->execute();
        $stmt->close();
    }

}