<?php

declare(strict_types=1);


namespace sergittos\bedwars;


use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\event\Listener;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use sergittos\bedwars\command\BedWarsCommand;
use sergittos\bedwars\entity\PlayBedwarsEntity;
use sergittos\bedwars\game\entity\misc\Fireball;
use sergittos\bedwars\game\entity\shop\ItemShopVillager;
use sergittos\bedwars\game\entity\shop\UpgradesShopVillager;
use sergittos\bedwars\game\GameHeartbeat;
use sergittos\bedwars\game\GameManager;
use sergittos\bedwars\game\map\MapFactory;
use sergittos\bedwars\game\task\RemoveGameTask;
use sergittos\bedwars\listener\GameListener;
use sergittos\bedwars\listener\ItemListener;
use sergittos\bedwars\listener\SessionListener;
use sergittos\bedwars\listener\SetupListener;
use sergittos\bedwars\listener\SpawnProtectionListener;
use sergittos\bedwars\listener\WaitingListener;
use sergittos\bedwars\provider\json\JsonProvider;
use sergittos\bedwars\provider\mysql\MysqlProvider;
use sergittos\bedwars\provider\Provider;
use sergittos\bedwars\provider\sqlite\SqliteProvider;
use sergittos\bedwars\session\SessionFactory;
use sergittos\bedwars\utils\ConfigGetter;
use sergittos\bedwars\game\PartyManager;
use pocketmine\event\player\PlayerChatEvent;

use function basename;
use function strtolower;

class BedWars extends PluginBase {
    use SingletonTrait;

    private GameManager $game_manager;
    private PartyManager $party_manager;
    private Provider $provider;


    protected function onLoad(): void {
        self::setInstance($this);

        $worlds_dir = $this->getDataFolder() . "worlds/";
        if(!is_dir($worlds_dir)) {
            mkdir($worlds_dir);
        }

        $this->saveResource("maps.json");
    }

    protected function onEnable(): void {
        MapFactory::init();

        $partyManager = PartyManager::getInstance();
        $this->party_manager = new PartyManager();

        $this->provider = $this->obtainProvider();
        $this->game_manager = new GameManager();

        $this->registerEntity(PlayBedwarsEntity::class);
        $this->registerEntity(ItemShopVillager::class);
        $this->registerEntity(UpgradesShopVillager::class);
        $this->registerFireball();

        $this->registerListener(new GameListener());
        $this->registerListener(new ItemListener());
        $this->registerListener(new SessionListener());
        $this->registerListener(new SetupListener());
        $this->registerListener(new WaitingListener());

        if(ConfigGetter::isSpawnProtectionEnabled()) {
            $this->registerListener(new SpawnProtectionListener());
        }

        $this->getServer()->getCommandMap()->register("bedwars", new BedWarsCommand());

        $this->getScheduler()->scheduleRepeatingTask(new GameHeartbeat(), 1);
    }
    public function getParty(Player $player): ?array {
        foreach ($this->parties as $party) {
            if ($party["leader"] === $player || isset($party["members"][$player->getName()])) {
                return $party;
            }
        }
        return null;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "leave") {
            if ($sender instanceof Player) {
                $session = SessionFactory::getSession($sender);
                if($session->hasGame()) {
                    $session->getGame()->removePlayer($session);
                } else {
                    $sender->sendMessage(TextFormat::RED . "You don't have a game.");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Use command in game.");
            }
        }

        if ($command->getName() === "party") {
            if (!$sender instanceof Player) {
                $sender->sendMessage("§cThis command can only be used in the game..");
                return true;
            }

            if (!isset($args[0])) {
                $sender->sendMessage("§eUse: /party <create|invite|accept|leave|list|mute|kick|disband|info>");
                return true;
            }

            switch ($args[0]) {
                case "create":
                    $this->party_manager->createParty($sender);
                    break;

                case "invite":
                    if (!isset($args[1])) {
                        $sender->sendMessage("§eUse: /party invite <Name Player>");
                        return true;
                    }
                    $target = $this->getServer()->getPlayerExact($args[1]);
                    if (!$target instanceof Player) {
                        $sender->sendMessage("§cThe player in question is not online");
                        return true;
                    }
                    $this->party_manager->invitePlayer($sender, $target);
                    break;

                case "accept":
                    $this->party_manager->acceptInvite($sender);
                    break;

                case "list":
                $info = $this->party_manager->getPartyInfo($sender);
                $sender->sendMessage($info);
                break;

                case "leave":
                    $this->party_manager->leaveParty($sender);
                    break;

                case "mute":
                    $this->party_manager->mutePartyChat($sender);
                    $sender->sendMessage("§eParty chat has been muted.");
                    break;

                case "kick":
                    if (!isset($args[1])) {
                        $sender->sendMessage("§eUse: /party kick <Name Player>");
                        return true;
                    }
                    $target = $this->getServer()->getPlayerExact($args[1]);
                    if (!$target instanceof Player) {
                        $sender->sendMessage("§cThe player in question is not online");
                        return true;
                    }
                    $this->party_manager->kickPlayer($sender, $target);
                    break;

                case "disband":
                    $this->party_manager->disbandParty($sender);
                    $sender->sendMessage("§eYour party has been disbanded.");
                    break;

                case "info":
                    $info = $this->party_manager->getPartyInfo($sender);
                    $sender->sendMessage($info);
                    break;

                default:
                    $sender->sendMessage("§eThe command is invalid! Usage: /party <create|invite|accept|leave|list|mute|kick|disband|info>");
            }
            return true;
        }
        return false;
    }

    protected function onDisable(): void {
        foreach(SessionFactory::getSessions() as $session) {
            $session->save();
        }

        foreach($this->game_manager->getGames() as $game) {
            $game->unloadWorld();
            $this->getServer()->getAsyncPool()->submitTask(new RemoveGameTask($game));
        }
    }

    private function registerListener(Listener $listener): void {
        $this->getServer()->getPluginManager()->registerEvents($listener, $this);
    }

    private function registerEntity(string $class): void {
        EntityFactory::getInstance()->register($class, function(World $world, CompoundTag $nbt) use ($class): Entity {
            return new $class(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
        }, ["bedwars:" . basename($class)]);
    }

    private function registerFireball(): void {
        EntityFactory::getInstance()->register(Fireball::class, function(World $world, CompoundTag $nbt): Fireball {
            return new Fireball(EntityDataHelper::parseLocation($nbt, $world), null);
        }, ["bedwars:fireball"]);
    }

    private function obtainProvider(): Provider {
        return match(strtolower(ConfigGetter::getProvider())) {
            "mysql" => new MysqlProvider(),
            "sqlite", "sqlite3" => new SqliteProvider(),
            "json" => new JsonProvider(),
            default => throw new \Error("Invalid provider, check your config and try again.")
        };
    }

    public function getProvider(): Provider {
        return $this->provider;
    }

    public function getGameManager(): GameManager {
        return $this->game_manager;
    }

    public function setPlayerLevelTag(Player $player, int $level): void {
        $player->setNameTag("§e[LVL $level] §f" . $player->getName());
    }

    private function getPlayerLevel(Player $player): int {
        return 5;
    }
    public function onChat(PlayerChatEvent $event): void {
        $event->cancel();

        $player = $event->getPlayer();
        $level = $this->getPlayerLevel($player);
        $message = $event->getMessage();

        // ارسال پیام سفارشی به همه بازیکنان
        $formattedMessage = "§e[LVL $level] §f" . $player->getName() . "§7: " . $message;
        $this->getServer()->broadcastMessage($formattedMessage);
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $level = $this->getPlayerLevel($player);
        $this->setPlayerLevelTag($player, $level);
    }

}