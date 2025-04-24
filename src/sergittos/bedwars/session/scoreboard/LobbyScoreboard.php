<?php

declare(strict_types=1);

namespace sergittos\bedwars\session\scoreboard;

use sergittos\bedwars\session\Session;
use sergittos\bedwars\utils\ConfigGetter;

class LobbyScoreboard extends Scoreboard {

    protected function getLines(Session $session): array {
        $level = (int)$session->getLevel();
        $xp = $session->getXp();
        $requiredXp = $this->calculateRequiredXp($level);
        
        return [

            14 => "§7" . date("d/m/Y") . " §fLobby1",
            13 => "§r ",
            12 => "§fname: §e" . $session->getUsername(),
            11 => "§fLevel: §e" . $level . "x",
            10 => "§fXP: §e" . $xp . "/" . $requiredXp,
            9 => "§fCoins: §e" . $session->getCoins(),
            8 => "§fProgress:",
            7 => "§8[§e" . $this->getProgressBar($xp, $requiredXp) . "§8]",
            6 => "§r",
            5 => "§fkills: §e" . $session->getKills(),
            4 => "§fFinal Kills: §e" . $session->getFinalKills(),
            3 => "§fDeaths: §e" . $session->getDeaths(),
            2 => " ",
            1 => "§e" . (ConfigGetter::getIP()),
        ];
    }

    private function calculateRequiredXp(int $level): int {
        return 1000 + ($level * 500);
    }

    private function getProgressBar(int $current, int $required): string {
        $percentage = min($current / $required, 1.0);
        $filled = (int)round($percentage * 10);
        $empty = 10 - $filled;
        return "§a" . str_repeat("■", $filled) . "§7" . str_repeat("■", $empty);
    }
}