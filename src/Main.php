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
use pocketmine\scheduler\Task;

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

            $this->getScheduler()->scheduleRepeatingTask(new BlockReplacerTask($world, $oldBlock, $newBlock, $sender), 1); // 1 Tick = 0.05 Sekunden
            return true;
        }
        return false;
    }
}

class BlockReplacerTask extends Task {

    private $world;
    private $oldBlock;
    private $newBlock;
    private $sender;
    private $chunks;
    private $currentChunkIndex = 0;
    private $blocksReplaced = 0;
    private $foundBlocks = false;

    public function __construct(World $world, $oldBlock, $newBlock, CommandSender $sender) {
        $this->world = $world;
        $this->oldBlock = $oldBlock;
        $this->newBlock = $newBlock;
        $this->sender = $sender;
        $this->chunks = $world->getLoadedChunks();
    }

    public function onRun(): void {
        if ($this->currentChunkIndex >= count($this->chunks)) {
            if (!$this->foundBlocks) {
                $this->sender->sendMessage("Block not found.");
            } else {
                $this->sender->sendMessage("Replaced {$this->blocksReplaced} blocks in world '{$this->world->getFolderName()}'.");
            }
            $this->getHandler()->cancel();
            return;
        }

        $chunkHash = array_keys($this->chunks)[$this->currentChunkIndex];
        $chunk = $this->chunks[$chunkHash];
        World::getXZ($chunkHash, $chunkX, $chunkZ);

        $foundInChunk = false;

        for ($x = 0; $x < 16; $x++) {
            for ($z = 0; $z < 16; $z++) {
                for ($y = $this->world->getMinY(); $y < $this->world->getMaxY(); $y++) {
                    $block = $this->world->getBlockAt($chunkX * 16 + $x, $y, $chunkZ * 16 + $z);
                    if ($block->getTypeId() === $this->oldBlock->getTypeId()) {
                        $this->world->setBlock(new Vector3($chunkX * 16 + $x, $y, $chunkZ * 16 + $z), $this->newBlock);
                        $this->blocksReplaced++;
                        $foundInChunk = true;
                        $this->foundBlocks = true;
                    }
                }
            }
        }

        if (!$foundInChunk && !$this->foundBlocks && $this->currentChunkIndex === count($this->chunks) - 1) {
            $this->sender->sendMessage("Block not found.");
            $this->getHandler()->cancel();
            return;
        }

        $this->currentChunkIndex++;
    }
}
