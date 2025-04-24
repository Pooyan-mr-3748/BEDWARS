<?php

declare(strict_types=1);


namespace sergittos\bedwars\provider\mysql;


use pocketmine\Server;
use sergittos\bedwars\provider\mysql\task\CreateTablesTask;
use sergittos\bedwars\provider\mysql\task\LoadSessionTask;
use sergittos\bedwars\provider\mysql\task\UpdateCoinsTask;
use sergittos\bedwars\provider\mysql\task\UpdateKillsTask;
use sergittos\bedwars\provider\mysql\task\UpdateWinsTask;
use sergittos\bedwars\provider\Provider;
use sergittos\bedwars\session\Session;
use sergittos\bedwars\utils\ConfigGetter;

class MysqlProvider extends Provider {

    private MysqlCredentials $credentials;

    public function __construct() {
        $this->credentials = MysqlCredentials::fromData(ConfigGetter::getMysqlCredentials());

        $this->scheduleAsyncTask(new CreateTablesTask($this->credentials));
    }

    public function getCredentials(): MysqlCredentials {
        return $this->credentials;
    }

    public function loadSession(Session $session): void {
        $this->scheduleAsyncTask(new LoadSessionTask($session));
    }

    public function updateCoins(Session $session): void {
        $this->scheduleAsyncTask(new UpdateCoinsTask($session));
    }

    public function updateKills(Session $session): void {
        $this->scheduleAsyncTask(new UpdateKillsTask($session));
    }

    public function updateWins(Session $session): void {
        $this->scheduleAsyncTask(new UpdateWinsTask($session));
    }

    public function updateLevels(Session $session): void {
        $this->scheduleAsyncTask(new UpdateLevelsTask($session));
    }

    public function updateDeaths(Session $session): void {
        $this->scheduleAsyncTask(new UpdateDeathsTask($session));
    }

    public function updateXps(Session $session): void {
        $this->scheduleAsyncTask(new UpdateXpTask($session));
    }

    public function updateFinal_kills(Session $session): void {
        $this->scheduleAsyncTask(new UpdateFinal_killsTask($session));
    }

    public function saveSession(Session $session): void {}

    private function scheduleAsyncTask(MysqlAsyncTask $task): void {
        Server::getInstance()->getAsyncPool()->submitTask($task);
    }

}