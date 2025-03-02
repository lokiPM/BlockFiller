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
use pocketmine\scheduler\AsyncTask;
use pocketmine\block\RuntimeBlockRegistry; // Add this import

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

            // Schedule the AsyncTask
            $this->getServer()->getAsyncPool()->submitTask(new BlockReplacerAsyncTask($world->getFolderName(), $oldBlock->getTypeId(), $newBlock->getTypeId(), $sender->getName()));
            $sender->sendMessage("Block replacement process started...");
            return true;
        }
        return false;
    }
}

class BlockReplacerAsyncTask extends AsyncTask {

    private $worldName;
    private $oldBlockId;
    private $newBlockId;
    private $senderName;

    public function __construct(string $worldName, int $oldBlockId, int $newBlockId, string $senderName) {
        $this->worldName = $worldName;
        $this->oldBlockId = $oldBlockId;
        $this->newBlockId = $newBlockId;
        $this->senderName = $senderName;
    }

    public function onRun(): void {
        $world = Server::getInstance()->getWorldManager()->getWorldByName($this->worldName);
        if ($world === null) {
            $this->setResult(["error" => "World '{$this->worldName}' not found."]);
            return;
        }

        $blocksReplaced = 0;
        $chunks = $world->getLoadedChunks();

        foreach ($chunks as $chunkHash => $chunk) {
            World::getXZ($chunkHash, $chunkX, $chunkZ);

            for ($x = 0; $x < 16; $x++) {
                for ($z = 0; $z < 16; $z++) {
                    for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                        $block = $world->getBlockAt($chunkX * 16 + $x, $y, $chunkZ * 16 + $z);
                        if ($block->getTypeId() === $this->oldBlockId) {
                            // Use RuntimeBlockRegistry to get the new block instance
                            $newBlockInstance = RuntimeBlockRegistry::getInstance()->fromTypeId($this->newBlockId);
                            $world->setBlock(new Vector3($chunkX * 16 + $x, $y, $chunkZ * 16 + $z), $newBlockInstance);
                            $blocksReplaced++;
                        }
                    }
                }
            }
        }

        $this->setResult(["blocksReplaced" => $blocksReplaced, "worldName" => $this->worldName]);
    }

    public function onCompletion(): void {
        $sender = Server::getInstance()->getPlayerExact($this->senderName);
        if ($sender === null) {
            // Sender is not online, no need to send a message
            return;
        }

        $result = $this->getResult();
        if (isset($result["error"])) {
            $sender->sendMessage($result["error"]);
        } else {
            $sender->sendMessage("Done! Replaced {$result["blocksReplaced"]} blocks in world '{$result["worldName"]}'.");
        }
    }
}
