<?php

namespace OguzhanUmutlu\BlockHunt\events;

use OguzhanUmutlu\BlockHunt\arena\Arena;
use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class BlockHuntKillEvent extends PlayerEvent {
    /*** @var Arena */
    private $arena;
    /*** @var Player */
    private $killer;
    public function __construct(Player $player, Player $killer, Arena $arena) {
        $this->player = $player;
        $this->killer = $killer;
        $this->arena = $arena;
    }

    /*** @return Arena */
    public function getArena(): Arena {
        return $this->arena;
    }

    /*** @return Player */
    public function getKiller(): Player {
        return $this->killer;
    }
}