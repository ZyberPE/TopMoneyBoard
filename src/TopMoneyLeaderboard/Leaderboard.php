<?php

declare(strict_types=1);

namespace TopMoneyLeaderboard;

use pocketmine\math\Vector3;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\utils\TextFormat;

class Leaderboard{

    private Main $plugin;
    private Position $position;
    private FloatingTextParticle $particle;

    public function __construct(
        Main $plugin,
        World $world,
        Vector3 $vector
    ){
        $this->plugin = $plugin;

        $this->position = new Position(
            $vector->x,
            $vector->y,
            $vector->z,
            $world
        );

        $this->particle = new FloatingTextParticle("", "");
    }

    public function getPosition() : Position{
        return $this->position;
    }

    public function update() : void{

        $money = $this->plugin
            ->getEconomy()
            ->getAllMoney();

        arsort($money);

        $top = array_slice(
            $money,
            0,
            10,
            true
        );

        $text = TextFormat::colorize(
            $this->plugin->getConfig()->get("title")
        );

        $text .= "\n";

        $lines = $this->plugin
            ->getConfig()
            ->get("lines", []);

        $i = 1;

        foreach($lines as $line){

            $player = "N/A";
            $cash = "0";

            if(isset(array_keys($top)[$i - 1])){

                $player = array_keys($top)[$i - 1];

                $cash = number_format(
                    (float)array_values($top)[$i - 1],
                    2
                );
            }

            $line = str_replace(
                [
                    "{player$i}",
                    "{money$i}"
                ],
                [
                    $player,
                    $cash
                ],
                $line
            );

            $text .= TextFormat::colorize($line) . "\n";

            $i++;
        }

        $this->particle->setTitle($text);

        $this->position
            ->getWorld()
            ->addParticle(
                $this->position,
                $this->particle
            );
    }

    public function remove() : void{

        $this->particle->setInvisible(true);

        $this->position
            ->getWorld()
            ->addParticle(
                $this->position,
                $this->particle
            );
    }
}
