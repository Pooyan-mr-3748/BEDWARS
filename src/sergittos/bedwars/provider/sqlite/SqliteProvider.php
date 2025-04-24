<?php

declare(strict_types=1);


namespace sergittos\bedwars\provider\sqlite;


use sergittos\bedwars\BedWars;
use sergittos\bedwars\provider\Provider;
use sergittos\bedwars\session\Session;
use sergittos\bedwars\session\settings\SpectatorSettings;
use SQLite3;

class SqliteProvider extends Provider {

    private SQLite3 $sqlite;

    public function __construct() {
        $this->sqlite = new SQLite3(BedWars::getInstance()->getDataFolder() . "database.db");
        $this->sqlite->exec(
            "CREATE TABLE IF NOT EXISTS bedwars_users (
        xuid VARCHAR(16) PRIMARY KEY,
        coins INT DEFAULT 0,
        kills INT DEFAULT 0,
        wins INT DEFAULT 0,
        levels INT DEFAULT 0,
        deaths INT DEFAULT 0,
        final_kills INT DEFAULT 0,
        xp INT DEFAULT 0,
        flying_speed INT DEFAULT 0,
        auto_teleport BOOL DEFAULT true,
        night_vision BOOL DEFAULT true
    );"
        );

    }

    public function loadSession(Session $session): void {
        $xuid = $session->getPlayer()->getXuid();
        $this->insertIfNotExists($xuid);

        $data = $this->fetchUserDetails($xuid);

        $session->setCoins($data["coins"]);
        $session->setKills($data["kills"]);
        $session->setWins($data["wins"]);
        $session->setLevel($data["levels"]);
        $session->setFinalKills($data["final_kills"]);
        $session->setXp($data["xp"]);
        $session->setDeaths($data["deaths"]);



        $session->setSpectatorSettings(SpectatorSettings::fromData($session, $data));
    }

    public function updateCoins(Session $session): void {
        $this->updateProperty($session, "coins");
    }

    public function updateKills(Session $session): void {
        $this->updateProperty($session, "kills");
    }

    public function updateWins(Session $session): void {
        $this->updateProperty($session, "wins");
    }

    public function updateLevels(Session $session): void {
        $this->updateProperty($session, "levels");
    }

    public function updateFinalKills(Session $session): void {
        $this->updateProperty($session, "final_kills");
    }

    public function updateXp(Session $session): void {
        $this->updateProperty($session, "xp");
    }

    public function updateDeaths(Session $session): void {
        $this->updateProperty($session, "deaths");
    }


    private function insertIfNotExists(string $xuid): void {
        $stmt = $this->sqlite->prepare("INSERT OR IGNORE INTO bedwars_users (xuid, coins, kills, wins, xp, final_kills, deaths, levels, flying_speed, auto_teleport, night_vision) VALUES (:xuid, 0, 0, 0, 0, 0, 0, 1, 1, true, true)");
        $stmt->bindParam(":xuid", $xuid);
        $stmt->execute();
    }

    private function fetchUserDetails(string $xuid): array {
        $stmt = $this->sqlite->prepare("SELECT * FROM bedwars_users WHERE xuid = :xuid");
        $stmt->bindParam(":xuid", $xuid);
        $result = $stmt->execute();

        return $result->fetchArray(SQLITE3_ASSOC);
    }

    private function updateProperty(Session $session, string $property): void {
        $stmt = $this->sqlite->prepare("UPDATE bedwars_users SET $property = :value WHERE xuid = :xuid");
        $stmt->bindValue(":value", $session->{'get' . ucfirst($property)}());
        $stmt->bindValue(":xuid", $session->getPlayer()->getXuid());
        $stmt->execute();
    }

    public function saveSession(Session $session): void {
        $xuid = $session->getPlayer()->getXuid();

        $stmt = $this->sqlite->prepare("UPDATE bedwars_users SET 
        coins = :coins,
        kills = :kills,
        wins = :wins,
        levels = :levels,
        deaths = :deaths,
        final_kills = :final_kills,
        xp = :xp
        WHERE xuid = :xuid");

        $stmt->bindValue(":coins", $session->getCoins());
        $stmt->bindValue(":kills", $session->getKills());
        $stmt->bindValue(":wins", $session->getWins());
        $stmt->bindValue(":levels", $session->getLevel()); // اضافه شود
        $stmt->bindValue(":deaths", $session->getDeaths());
        $stmt->bindValue(":final_kills", $session->getFinalKills()); // اضافه شود
        $stmt->bindValue(":xp", $session->getXp()); // اضافه شود
        $stmt->bindValue(":xuid", $xuid);

        $stmt->execute();
    }


}