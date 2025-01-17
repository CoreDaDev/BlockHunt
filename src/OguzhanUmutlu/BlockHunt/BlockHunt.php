<?php

namespace OguzhanUmutlu\BlockHunt;

use OguzhanUmutlu\BlockHunt\arena\Arena;
use OguzhanUmutlu\BlockHunt\arena\ArenaData;
use OguzhanUmutlu\BlockHunt\entities\BlockHuntEntity;
use OguzhanUmutlu\BlockHunt\manager\ArenaManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;

class BlockHunt extends PluginBase {
    /*** @var BlockHunt */
    private static $instance;
    public function onLoad() {
        self::$instance = $this;
    }

    /*** @var Player[] */
    public $hide = [];

    /*** @var Config */
    public $arenaConfig;
    public $messages;
    /*** @var Config */
    public $messageConfig;
    /*** @var ArenaManager */
    public $arenaManager;

    public function onEnable() {
        if(!file_exists($this->getDataFolder()."worlds"))
            mkdir($this->getDataFolder()."worlds");
        Entity::registerEntity(BlockHuntEntity::class, true, ["BlockHuntEntity"]);
        $this->arenaConfig = new Config($this->getDataFolder() . "arenas.yml");
        $this->arenaManager = new ArenaManager();
        $this->saveDefaultConfig();
        $this->saveResource("lang/".$this->getConfig()->getNested("lang").".yml");
        $this->messageConfig = new Config($this->getDataFolder()."lang/".$this->getConfig()->getNested("lang").".yml");
        $this->messages = $this->messageConfig->getAll();
        foreach($this->arenaConfig->getAll() as $arenaData) {
            $data = new ArenaData();
            $data->minPlayer = $arenaData["minPlayer"];
            $data->maxPlayer = $arenaData["maxPlayer"];
            $s = $arenaData["spawn"];
            $data->spawn = new Vector3($s["x"], $s["y"], $s["z"]);
            $s = $arenaData["joinSign"];
            if(!$this->getServer()->isLevelLoaded($s["level"]))
                $this->getServer()->loadLevel($s["level"]);
            $lvl = $this->getServer()->getLevelByName($s["level"]);
            $data->joinSign = new Position($s["x"], $s["y"], $s["z"], $lvl);
            $data->startingCountdown = $arenaData["startingCountdown"];
            $data->maxTime = $arenaData["maxTime"];
            $data->map = $arenaData["map"];
            $data->name = $arenaData["name"];
            $this->arenaManager->createArena(new Arena($data));
        }
        Server::getInstance()->getPluginManager()->registerEvents(new EventListener(), $this);
    }

    /*** @return BlockHunt|null */
    public static function getInstance(): ?BlockHunt {
        return self::$instance;
    }

    public static function T(string $key, array $args = []): ?string {
        return self::sT(self::$instance->messages[$key] ?? "Language error: Key ".$key." not found.", $args);
    }

    public static function sT(string $message, array $args = []): string {
        return str_replace(
            [
                "\\n",
                "{line}",
                "&"
            ],
            [
                "\n",
                "\n",
                "§"
            ],
            str_replace(
                array_map(function($n){return "%".(int)$n;}, array_keys($args)),
                array_values($args),
                $message
            )
        );
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if($command->getPermission() && !$sender->hasPermission($command->getPermission())) return true;
        switch($command->getName()) {
            case "blockhunt":
                switch($args[0] ?? "") {
                    case self::T("args-join"):
                        if(!$sender instanceof Player) {
                            $sender->sendMessage(self::T("use-in-game"));
                            return true;
                        }
                        if($this->arenaManager->getPlayerArena($sender)) {
                            $sender->sendMessage(self::T("already-in-game"));
                            return true;
                        }
                        $arena = $this->arenaManager->getAvailableArena();
                        if(!$arena) {
                            $sender->sendMessage(self::T("no-arena"));
                            return true;
                        }
                        $sender->sendMessage(self::T("redirect", [$arena->getData()->name]));
                        $arena->addPlayer($sender);
                        break;
                    case self::T("args-leave"):
                        if(!$sender instanceof Player) {
                            $sender->sendMessage(self::T("use-in-game"));
                            return true;
                        }
                        if(!$this->arenaManager->getPlayerArena($sender)) {
                            $sender->sendMessage(self::T("not-in-arena"));
                            return true;
                        }
                        $this->arenaManager->getPlayerArena($sender)->removePlayer($sender);
                        $sender->sendMessage(self::T("success-left"));
                        break;
                    default:
                        $sender->sendMessage(self::T("usage", [
                            "/blockhunt <".self::T("args-join").", ".self::T("args-leave").">"
                        ]));
                        break;
                }
                break;
            case "blockhuntadmin":
                switch($args[0] ?? "") {
                    case "setup":
                        if(!$sender instanceof Player) {
                            $sender->sendMessage("§c> Use this command in-game.");
                            return true;
                        }
                        if(isset(EventListener::$setup[$sender->getName()])) {
                            $sender->sendMessage("§c> You are already in setup mode!");
                            return true;
                        }
                        EventListener::$setup[$sender->getName()] = ["phase" => 1];
                        $sender->sendMessage("§e> Type the name of arena world to chat.");
                        break;
                    case "start":
                        $arena = $sender instanceof Player && $this->arenaManager->getPlayerArena($sender) ? $this->arenaManager->getPlayerArena($sender) : $this->arenaManager->getArenaById((int)($args[0] ?? -1));
                        if(!$arena) {
                            if(!isset($args[0])) {
                                $sender->sendMessage("§c> Usage: /blockhuntadmin start <arenaId".">");
                                return true;
                            }
                            $sender->sendMessage("§c> Arena not found.");
                            return true;
                        }
                        $arena->start();
                        $sender->sendMessage("§a> Arena started!");
                        break;
                    case "list":
                        $sender->sendMessage("§a> Arenas:");
                        foreach($this->arenaManager->getArenas() as $arena)
                            $sender->sendMessage("§e> ID: ".$arena->getId().", name: ".$arena->getData()->name.", map: ".$arena->getData()->map.", alive players(".count($arena->getPlayers())."): ".implode(", ", array_map(function($n){return $n->getName();}, $arena->getPlayers())));
                        break;
                    case "private":
                        $arena = $sender instanceof Player && $this->arenaManager->getPlayerArena($sender) ? $this->arenaManager->getPlayerArena($sender) : $this->arenaManager->getArenaById((int)($args[0] ?? -1));
                        if(!$arena) {
                            if(!isset($args[0])) {
                                $sender->sendMessage("§c> Usage: /blockhuntadmin private <arenaId".">");
                                return true;
                            }
                            $sender->sendMessage("§c> Arena not found.");
                            return true;
                        }
                        $arena->setPrivate(!$arena->isPrivate());
                        if($arena->isPrivate())
                            $sender->sendMessage("§e> Arena is now §cprivate§e!");
                        else $sender->sendMessage("§e> Arena is now §cpublic§e!");
                        break;
                    default:
                        $sender->sendMessage("§c> Usage: /blockhuntadmin <setup, start, list>");
                        break;
                }
                break;
        }
        return true;
    }
}
