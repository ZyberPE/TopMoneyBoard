<?php

declare(strict_types=1);

namespace TopMoneyLeaderboard\task;

use pocketmine\scheduler\Task;
use TopMoneyLeaderboard\Main;

class UpdateTask extends Task{

    private Main $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function onRun() : void{
        $this->plugin->updateLeaderboards();
    }
}
