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

class Main extends PluginBase {

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "blockreplacer") {
            if (count($args) < 2) {
                $sender->sendMessage("Usage: /blockreplacer <oldblock> <newblock> [world]");
                return false;
            }

            $oldBlockName = strtolower($args[0]);
            $newBlockName = strtolower($args[1]);

            $oldBlock = VanillaBlocks::{$oldBlockName}() ?? null;
            $newBlock = VanillaBlocks::{$newBlockName}() ?? null;

            if ($oldBlock === null || $newBlock === null) {
                $sender->sendMessage("Invalid block name. Use block names like 'stone', 'grass', etc.");
                return false;
            }

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
            $chunkCount = 0;

            foreach ($world->getLoadedChunks() as $chunkHash => $chunk) {
                World::getXZ($chunkHash, $chunkX, $chunkZ);
                $chunkCount++;

                for ($x = 0; $x < 16; $x++) {
                    for ($z = 0; $z < 16; $z++) {
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            $block = $world->getBlockAt($chunkX * 16 + $x, $y, $chunkZ * 16 + $z);
                            if ($block->getTypeId() === $oldBlock->getTypeId()) {
                                $world->setBlock(new Vector3($chunkX * 16 + $x, $y, $chunkZ * 16 + $z), $newBlock);
                                $blocksReplaced++;
                            }
                        }
                    }
                }
            }

            $sender->sendMessage("Replaced $blocksReplaced blocks in world '{$world->getFolderName()}' (processed $chunkCount chunks).");
            return true;
        }
        return false;
    }
}
