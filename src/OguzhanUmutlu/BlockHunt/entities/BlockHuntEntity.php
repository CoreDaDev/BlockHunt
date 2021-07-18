<?php

namespace OguzhanUmutlu\BlockHunt\entities;

use OguzhanUmutlu\BlockHunt\arena\Arena;
use OguzhanUmutlu\BlockHunt\BlockHunt;
use pocketmine\entity\object\FallingBlock;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;

class BlockHuntEntity extends FallingBlock {
    /*** @var Arena|null */
    public $arena = null;
    /*** @var Player|null */
    public $player = null;
    /*** @var Vector3|null */
    public $lastPosition = null;
    /*** @var int|null */
    public $lastMove = null;
    protected function initEntity(): void {
        parent::initEntity();
    }

    protected function tryChangeMovement(): void {}
    public function spawnToAll(): void {
        if(!$this->arena instanceof Arena || !$this->player instanceof Player) {
            $this->flagForDespawn();
            return;
        }
        parent::spawnToAll();
    }
    public function entityBaseTick(int $tickDiff = 1): bool {
        return true;
    }

    public function onUpdate(int $currentTick): bool {
        if($this->closed || !$this->level || !$this->arena instanceof Arena || !$this->player instanceof Player) return true;
        if(!isset($this->arena->getSeekers()[$this->player->getName()]) || $this->player->isClosed()) {
            $this->flagForDespawn();
            return true;
        }
        if($this->lastMove && $this->lastMove+3 <= time()) {
            if($this->lastMove+5 <= time()) {
                $this->level->setBlock($this, $this->block);
                $this->arena->blocks[$this->player->getName()] = $this->level->getBlock($this);
                $this->flagForDespawn();
            } else {
                if($this->level->getBlock($this)->getId() == 0)
                    $this->player->sendPopup(BlockHunt::T("transform-soon", [($this->lastMove+5)-time()]));
                else $this->player->sendPopup(BlockHunt::T("transform-error"));
            }
        }
        if($this->lastPosition instanceof Vector3 && $this->lastPosition->distance($this->player->asVector3()) > 0)
            $this->lastMove = time();
        $this->lastPosition = $this->player->asVector3();
        $this->teleport($this->player);
        return true;
    }

    public function attack(EntityDamageEvent $source): void {
        $source->setCancelled();
        parent::attack($source);
        if(!$this->player instanceof Player || ($source instanceof EntityDamageByEntityEvent && $source->getDamager()->id == $this->player->id)) {
            return;
        }
        if($this->player instanceof Player && !$this->player->isClosed() && $source instanceof EntityDamageByEntityEvent) {
            $ev = new EntityDamageByEntityEvent($source->getDamager(), $this->player, $source->getCause(), $source->getBaseDamage());
            $ev->call();
            if(!$ev->isCancelled())
                $this->player->attack($ev);
        }
    }
}
