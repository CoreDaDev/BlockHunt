<?php

namespace OguzhanUmutlu\BlockHunt\arena;

use OguzhanUmutlu\BlockHunt\scoreboard\ScoreboardAPI;
use OguzhanUmutlu\BlockHunt\BlockHunt;
use pocketmine\Player;

class ScoreboardManager {
    private const EMPTY_CACHE = ["§0\e", "§1\e", "§2\e", "§3\e", "§4\e", "§5\e", "§6\e", "§7\e", "§8\e", "§9\e", "§a\e", "§b\e", "§c\e", "§d\e", "§e\e"];
    private $scoreboards = [];
    private $arena;
    private $networkBound = [];
    private $lastState = [];

    public function __construct(Arena $arena) {
        $this->arena = $arena;
    }

    public function addPlayer(Player $pl): void {
        $this->scoreboards[$pl->getName()] = $pl;
        ScoreboardAPI::setScore($pl, BlockHunt::T("scoreboards.title"), ScoreboardAPI::SORT_ASCENDING);
        $this->updateScoreboard($pl);
    }

    private function updateScoreboard(Player $pl): void {
        if (!isset($this->scoreboards[$pl->getName()])) {
            $this->addPlayer($pl);
            return;
        } elseif (!$pl->isOnline()) {
            $this->removePlayer($pl);
            return;
        }
        $keys = [
            "{name}",
            "{players}",
            "{seekers}",
            "{hunters}",
            "{required_players}",
            "{min_players}",
            "{max_players}",
            "{countdown}",
            "{endsafter}"
        ];
        $values = [
            $this->arena->getData()->name,
            count($this->arena->getPlayers()),
            count($this->arena->getSeekers()),
            count($this->arena->getHunters()),
            $this->arena->getData()-count($this->arena->getPlayers()),
            $this->arena->getData()->minPlayer,
            $this->arena->getData()->maxPlayer,
            $this->arena->getCountdown(),
            $this->arena->getData()->maxTime-$this->arena->getCountdown()
        ];
        switch($this->arena->getStatus()) {
            case Arena::STATUS_ARENA_WAITING:
                $data = array_merge([" "], array_map(function($line) use ($values, $keys) {
                    return str_replace(
                        $keys,
                        $values,
                        BlockHunt::T("scoreboards.waiting.".$line)
                    );
                }, BlockHunt::getInstance()->messages["scoreboards"]["waiting"]));
                break;
            case Arena::STATUS_ARENA_STARTING:
                $data = array_merge([" "], array_map(function($line) use ($values, $keys) {
                    return str_replace(
                        $keys,
                        $values,
                        BlockHunt::T("scoreboards.starting.".$line)
                    );
                }, BlockHunt::getInstance()->messages["scoreboards"]["starting"]));
                break;
            case Arena::STATUS_ARENA_RUNNING:
                $data = array_merge([" "], array_map(function($line) use ($values, $keys) {
                    return str_replace(
                        $keys,
                        $values,
                        BlockHunt::T("scoreboards.running.".$line)
                    );
                }, BlockHunt::getInstance()->messages["scoreboards"]["running"]));
                break;
            case Arena::STATUS_ARENA_CLOSED:
                $data = array_merge([" "], array_map(function($line) use ($values, $keys) {
                    return str_replace(
                        $keys,
                        $values,
                        BlockHunt::T("scoreboards.closed.".$line)
                    );
                }, BlockHunt::getInstance()->messages["scoreboards"]["closed"]));
                break;
            default:
                $data = [" ", "An error occured!"];
                break;
        }
        foreach ($data as $scLine => $message) {
            ScoreboardAPI::setScoreLine($pl, $scLine, $message);
            $line = $scLine + 1;
            if (($this->networkBound[$pl->getName()][$line] ?? -1) === $message) {
                continue;
            }
            ScoreboardAPI::setScoreLine($pl, $line, $message);
            $this->networkBound[$pl->getName()][$line] = $message;
        }
    }

    public function tickScoreboard(): void {
        foreach ($this->arena->getPlayers() as $player)
            $this->updateScoreboard($player);
    }

    public function resetScoreboard(): void {
        foreach ($this->scoreboards as $player) $this->removePlayer($player);
        $this->networkBound = [];
    }

    public function removePlayer(Player $pl): void {
        unset($this->scoreboards[$pl->getName()]);
        unset($this->networkBound[$pl->getName()]);
        ScoreboardAPI::removeScore($pl);
    }
}