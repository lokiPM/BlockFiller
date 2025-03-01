<?php

namespace lokiPM\BlockFiller;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\block\BlockFactory;
use pocketmine\block\Block;
use pocketmine\world\World;
use pocketmine\math\Vector3;

class BlockFiller extends PluginBase {

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "blockreplacer") {
            if (count($args) < 2) {
                return false;
            }

            $oldBlockId = $args[0];
            $newBlockId = $args[1];

            if (!is_numeric($oldBlockId) || !is_numeric($newBlockId)) {
                return false;
            }

            $oldBlock = BlockFactory::getInstance()->get((int)$oldBlockId, 0);
            $newBlock = BlockFactory::getInstance()->get((int)$newBlockId, 0);

            if (!$sender instanceof Player) {
                $sender->sendMessage("This command can only be used in-game.");
                return true;
            }

            $world = $sender->getWorld();
            $blocksReplaced = 0;

            for ($x = 0; $x < 16; $x++) {
                for ($z = 0; $z < 16; $z++) {
                    for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                        $block = $world->getBlockAt($x, $y, $z);
                        if ($block->getId() === $oldBlock->getId()) {
                            $world->setBlock(new Vector3($x, $y, $z), $newBlock);
                            $blocksReplaced++;
                        }
                    }
                }
            }

            $sender->sendMessage("Replaced $blocksReplaced blocks.");
            return true;
        }
        return false;
    }
}
