<?php

namespace lokiPM\BlockFiller;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\World;
use pocketmine\math\Vector3;
use pocketmine\Server;

class BlockFiller extends PluginBase {

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "blockreplacer") {
            if (count($args) < 2) {
                return false;
            }

            $oldBlockName = strtolower($args[0]);
            $newBlockName = strtolower($args[1]);

            // Hole die Block-Objekte aus VanillaBlocks
            $oldBlock = VanillaBlocks::{$oldBlockName}();
            $newBlock = VanillaBlocks::{$newBlockName}();

            // Bestimme die Welt
            $world = null;
            if (count($args) >= 3) {
                $worldName = $args[2];
                $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
                if ($world === null) {
                    $sender->sendMessage("World '$worldName' does not exist.");
                    return true;
                }
            } elseif ($sender instanceof Player) {
                $world = $sender->getWorld();
            } else {
                $sender->sendMessage("You must specify a world when using this command from the console.");
                return true;
            }

            $blocksReplaced = 0;

            for ($x = 0; $x < 16; $x++) {
                for ($z = 0; $z < 16; $z++) {
                    for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                        $block = $world->getBlockAt($x, $y, $z);
                        if ($block->getTypeId() === $oldBlock->getTypeId()) {
                            $world->setBlock(new Vector3($x, $y, $z), $newBlock);
                            $blocksReplaced++;
                        }
                    }
                }
            }

            $sender->sendMessage("Replaced $blocksReplaced blocks in world '{$world->getFolderName()}'.");
            return true;
        }
        return false;
    }
}
