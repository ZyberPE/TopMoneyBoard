<?php

declare(strict_types=1);

namespace TopMoneyLeaderboard;

use onebone\economyapi\EconomyAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use TopMoneyLeaderboard\task\UpdateTask;

class Main extends PluginBase{

    private Config $storage;

    /** @var Leaderboard[] */
    private array $leaderboards = [];

    public function onEnable() : void{
        $this->saveDefaultConfig();

        if(!file_exists($this->getDataFolder() . "leaderboards.yml")){
            $cfg = new Config(
                $this->getDataFolder() . "leaderboards.yml",
                Config::YAML,
                ["leaderboards" => []]
            );
            $cfg->save();
        }

        $this->storage = new Config(
            $this->getDataFolder() . "leaderboards.yml",
            Config::YAML
        );

        $this->loadLeaderboards();

        $interval = max(20, (int)$this->getConfig()->get("update-interval", 60) * 20);

        $this->getScheduler()->scheduleRepeatingTask(
            new UpdateTask($this),
            $interval
        );
    }

    public function getEconomy() : EconomyAPI{
        return EconomyAPI::getInstance();
    }

    public function loadLeaderboards() : void{
        foreach($this->storage->get("leaderboards", []) as $data){

            $world = $this->getServer()->getWorldManager()->getWorldByName($data["world"]);

            if($world === null){
                continue;
            }

            $this->leaderboards[] = new Leaderboard(
                $this,
                $world,
                new Vector3(
                    $data["x"],
                    $data["y"],
                    $data["z"]
                )
            );
        }
    }

    public function saveLeaderboards() : void{
        $data = [];

        foreach($this->leaderboards as $leaderboard){

            $pos = $leaderboard->getPosition();

            $data[] = [
                "world" => $pos->getWorld()->getFolderName(),
                "x" => $pos->getX(),
                "y" => $pos->getY(),
                "z" => $pos->getZ()
            ];
        }

        $this->storage->set("leaderboards", $data);
        $this->storage->save();
    }

    public function getLeaderboards() : array{
        return $this->leaderboards;
    }

    public function updateLeaderboards() : void{
        foreach($this->leaderboards as $leaderboard){
            $leaderboard->update();
        }
    }

    public function onDisable() : void{
        $this->saveLeaderboards();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{

        if(!$sender instanceof Player){
            return true;
        }

        if(!isset($args[0])){
            $sender->sendMessage("/topmoneyleaderboard create");
            $sender->sendMessage("/topmoneyleaderboard remove");
            return true;
        }

        switch(strtolower($args[0])){

            case "create":

                $leaderboard = new Leaderboard(
                    $this,
                    $sender->getWorld(),
                    $sender->getPosition()
                );

                $leaderboard->update();

                $this->leaderboards[] = $leaderboard;

                $this->saveLeaderboards();

                $sender->sendMessage(
                    TextFormat::colorize(
                        $this->getConfig()->getNested("messages.created")
                    )
                );

                return true;

            case "remove":

                foreach($this->leaderboards as $k => $leaderboard){

                    if(
                        $leaderboard->getPosition()
                            ->distance($sender->getPosition()) <= 5
                    ){

                        $leaderboard->remove();

                        unset($this->leaderboards[$k]);

                        $this->leaderboards = array_values($this->leaderboards);

                        $this->saveLeaderboards();

                        $sender->sendMessage(
                            TextFormat::colorize(
                                $this->getConfig()->getNested("messages.removed")
                            )
                        );

                        return true;
                    }
                }

                $sender->sendMessage(
                    TextFormat::colorize(
                        $this->getConfig()->getNested("messages.no-leaderboard")
                    )
                );

                return true;
        }

        return true;
    }
}
