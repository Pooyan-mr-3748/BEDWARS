<?php

declare(strict_types=1);

namespace sergittos\bedwars\provider\mysql\task;

use mysqli;
use sergittos\bedwars\provider\mysql\MysqlAsyncTask;
use sergittos\bedwars\session\Session;

class UpdateLevelTask extends MysqlAsyncTask {

    private string $xuid;
    private int $level;

    public function __construct(Session $session, int $level) {
        $this->xuid = $session->getPlayer()->getXuid();
        $this->level = $level;
        parent::__construct();
    }

    protected function onConnection(mysqli $mysqli): void {
        $stmt = $mysqli->prepare("UPDATE bedwars_users SET level = ? WHERE xuid = ?");
        $stmt->bind_param("is", $this->level, $this->xuid);
        $stmt->execute();
        $stmt->close();
    }

}