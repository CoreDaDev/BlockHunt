<?php

namespace OguzhanUmutlu\BlockHunt\arena;

use pocketmine\level\Position;
use pocketmine\math\Vector3;

class ArenaData {
    public $minPlayer = 4;
    public $maxPlayer = 16;
    /*** @var Vector3 */
    public $spawn;
    /*** @var Position */
    public $joinSign;
    public $startingCountdown = 10;
    public $maxTime = 600;
    public $map = "";
    public $name = "";
}