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
            // Überprüfe, ob genügend Argumente angegeben wurden
            if (count($args) < 2) {
                $sender->sendMessage("Usage: /blockreplacer <oldblock> <newblock> [world]");
                return false;
            }

            $oldBlockName = strtolower($args[0]);
            $newBlockName = strtolower($args[1]);

            // Überprüfe, ob die Blocknamen gültig sind
            if (!isset(VanillaBlocks::{$oldBlockName}()) || !isset(VanillaBlocks::{$newBlockName}())) {
                $sender->sendMessage("Invalid block name. Use block names like 'stone', 'grass', etc.");
                return false;
            }

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

            // Ersetze die Blöcke
            $blocksReplaced = 0;
            $chunkCount = 0;

            foreach ($world->getLoadedChunks() as $chunk) {
                $chunkCount++;
                for ($x = 0; $x < 16; $x++) {
                    for ($z = 0; $z < 16; $z++) {
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            $block = $world->getBlockAt($chunk->getX() * 16 + $x, $y, $chunk->getZ() * 16 + $z);
                            if ($block->getTypeId() === $oldBlock->getTypeId()) {
                                $world->setBlock(new Vector3($chunk->getX() * 16 + $x, $y, $chunk->getZ() * 16 + $z), $newBlock);
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
