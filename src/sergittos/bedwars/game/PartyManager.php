<?php

declare(strict_types=1);

namespace sergittos\bedwars\game;

use pocketmine\player\Player;
use sergittos\bedwars\game\GameManager;

class PartyManager {
    private static ?PartyManager $instance = null;
    private array $parties = [];
    private array $mutedParties = [];
    private array $partyInvites = [];

    public function __construct() {}

    public static function getInstance(): PartyManager {
        if (self::$instance === null) {
            self::$instance = new PartyManager();
        }
        return self::$instance;
    }
    public function isPlayerInParty(Player $player): bool {
        return $this->getParty($player) !== null;
    }




    public function getParty(Player $player): ?Party {
        $uuid = $player->getUniqueId()->toString();
        foreach ($this->parties as $party) {
            if ($party->isMember($uuid)) {
                return $party;
            }
        }
        return null;
    }



    public function createParty(Player $leader): void {
        if ($this->isInParty($leader)) {
            $leader->sendMessage("§cYou are already in a party.");
            return;
        }

        $this->parties[$leader->getName()] = [
            "leader" => $leader,
            "members" => []
        ];
        $leader->sendMessage("§aYour party has been created!");
    }

    public function invitePlayer(Player $leader, Player $invitee): void {
        if (!isset($this->parties[$leader->getName()])) {
            $leader->sendMessage("§cYou don't have a party. Use /party create.");
            return;
        }

        if ($this->isInParty($invitee)) {
            $leader->sendMessage("§cThe player is already in a party.");
            return;
        }

        $this->partyInvites[$invitee->getName()] = $leader->getName(); // ذخیره درخواست

        $invitee->sendMessage("§e" . $leader->getName() . " invited you to a party. Type: /party accept");
    }

    public function acceptInvite(Player $player): void {
        if (isset($this->partyInvites[$player->getName()])) {
            $leaderName = $this->partyInvites[$player->getName()];
            if (isset($this->parties[$leaderName])) {
                $this->parties[$leaderName]["members"][$player->getName()] = $player;
                $player->sendMessage("§aYou joined the party!");
                unset($this->partyInvites[$player->getName()]); // حذف درخواست بعد از تایید
                return;
            }
        }
        $player->sendMessage("§cYou have no invitations.");
    }

    public function getPartyMembers(Player $player): array {
        foreach ($this->parties as $leaderName => $party) {
            if ($party["leader"] === $player || isset($party["members"][$player->getName()])) {
                return ["leader" => $party["leader"], "members" => $party["members"]];
            }
        }
        return [];
    }

    public function leaveParty(Player $player): void {
        foreach ($this->parties as $leaderName => &$party) {
            if ($party["leader"] === $player) {
                foreach ($party["members"] as $member) {
                    $member->sendMessage("§cYour party was disbanded.");
                }
                unset($this->parties[$leaderName]);
                return;
            } elseif (isset($party["members"][$player->getName()])) {
                unset($party["members"][$player->getName()]);
                $player->sendMessage("§cYou left the party.");
                return;
            }
        }
        $player->sendMessage("§cYou are not in a party.");
    }

    public function kickPlayer(Player $leader, Player $target): void {
        if (!isset($this->parties[$leader->getName()])) {
            $leader->sendMessage("§cYou are not the party leader.");
            return;
        }
        if (!isset($this->parties[$leader->getName()]["members"][$target->getName()])) {
            $leader->sendMessage("§cThis player is not in your party.");
            return;
        }
        unset($this->parties[$leader->getName()]["members"][$target->getName()]);
        $target->sendMessage("§cYou were kicked from the party.");
        $leader->sendMessage("§aYou kicked " . $target->getName() . " from the party.");
    }

    public function disbandParty(Player $leader): void {
        if (!isset($this->parties[$leader->getName()])) {
            $leader->sendMessage("§cYou are not the party leader.");
            return;
        }
        foreach ($this->parties[$leader->getName()]["members"] as $member) {
            $member->sendMessage("§cYour party has been disbanded.");
        }
        unset($this->parties[$leader->getName()]);
        $leader->sendMessage("§aYou disbanded the party.");
    }

    public function mutePartyChat(Player $leader): void {
        if (!isset($this->parties[$leader->getName()])) {
            $leader->sendMessage("§cYou are not the party leader.");
            return;
        }
        $partyName = $leader->getName();
        if (isset($this->mutedParties[$partyName])) {
            unset($this->mutedParties[$partyName]);
            $leader->sendMessage("§aParty chat enabled.");
        } else {
            $this->mutedParties[$partyName] = true;
            $leader->sendMessage("§cParty chat muted.");
        }
    }

    public function getPartyInfo(Player $player): string {
        foreach ($this->parties as $leaderName => $party) {
            if ($party["leader"] === $player || isset($party["members"][$player->getName()])) {
                $msg = "§eYour party information:\n";
                $msg .= "§6Leader: " . $party["leader"]->getName() . "\n";
                $msg .= "§aMembers:\n";
                foreach ($party["members"] as $member) {
                    $msg .= "§e- " . $member->getName() . "\n";
                }
                return $msg;
            }
        }
        return "§cYou are not in a party.";
    }

    public function startGame(Player $leader): void {
        if (!isset($this->parties[$leader->getName()])) {
            $leader->sendMessage("§cYou are not the party leader.");
            return;
        }

        $party = $this->parties[$leader->getName()];
        $players = array_merge([$party["leader"]], $party["members"]);

        $gameManager = GameManager::getInstance();
        $game = $gameManager->findAvailableGame();

        if ($game === null) {
            $leader->sendMessage("§cNo available games at the moment.");
            return;
        }

        foreach ($players as $player) {
            if ($player instanceof Player && $player->isOnline()) { // بررسی آنلاین بودن
                $game->addPlayer($player);
            } else {
                $leader->sendMessage("§c" . $player->getName() . " is not online. They will not be added to the game.");
            }
        }
        $leader->sendMessage("§aGame started! Your party is in the same match.");
    }

    public function isInParty(Player $player): bool {
        foreach ($this->parties as $party) {
            if ($party["leader"] === $player || isset($party["members"][$player->getName()])) {
                return true;
            }
        }
        return false;
    }
}
