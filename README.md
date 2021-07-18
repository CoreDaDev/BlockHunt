# BlockHunt
[![](https://poggit.pmmp.io/shield.state/BlockHunt)](https://poggit.pmmp.io/p/BlockHunt)
[![](https://poggit.pmmp.io/shield.api/BlockHunt)](https://poggit.pmmp.io/p/BlockHunt)
[![](https://poggit.pmmp.io/shield.dl.total/BlockHunt)](https://poggit.pmmp.io/p/BlockHunt)
[![](https://poggit.pmmp.io/shield.dl/BlockHunt)](https://poggit.pmmp.io/p/BlockHunt)

Block Hunt minigame for PocketMine-MP!

# What is this minigame?

In this minigame there are two teams, Hunters and seekers.

Seekers are trying to run from hunters.

Seekers can transform into blocks.

If a seeker dies it changes his team to hunter.

If seeker(s) survives for maximum time of arena they win else hunters wins.

# How to setup?

Just simply use `/blockhuntadmin setup` command and start setup session!

# API

Use plugin

```php
use OguzhanUmutlu\BlockHunt\BlockHunt;
```

Get player's arena:

```php
BlockHunt::getInstance()->arenaManager->getPlayerArena($player);
```

Events:

```php
use OguzhanUmutlu\BlockHunt\events\BlockHuntKillEvent;
use OguzhanUmutlu\BlockHunt\events\BlockHuntWinEvent;
use OguzhanUmutlu\BlockHunt\events\BlockHuntLoseEvent;
```

```php
/*** BlockHuntKillEvent|BlockHuntWinEvent|BlockHuntLoseEvent */
$player = $event->getPlayer();
$arena = $event->getArena();

/*** BlockHuntKillEvent */
$killer = $event->getKiller();
```


# TODO
- idk

# Reporting bugs
**You may open an issue on the BlockHunt GitHub repository for report bugs**
https://github.com/OguzhanUmutlu/BlockHunt/issues
