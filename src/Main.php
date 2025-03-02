<?php

declare(strict_types=1);

namespace lokiPM\ChatPerWorld;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;

class Main extends PluginBase implements Listener {

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onPlayerChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        $world = $player->getWorld()->getName();

        // LiaMiaonaS97 kann alle Nachrichten sehen
        if ($player->getName() === "LiaMiaonaS97") {
            // LiaMiaonaS97 sieht alle Nachrichten, also ändern wir nichts an den Empfängern
            return;
        }

        // Filtere die Empfänger basierend auf der Welt
        $recipients = [];
        foreach ($this->getServer()->getOnlinePlayers() as $onlinePlayer) {
            // LiaMiaonaS97 soll alle Nachrichten sehen
            if ($onlinePlayer->getName() === "LiaMiaonaS97") {
                $recipients[] = $onlinePlayer;
            }
            // Andere Spieler sehen nur Nachrichten aus ihrer eigenen Welt
            elseif ($onlinePlayer->getWorld()->getName() === $world) {
                $recipients[] = $onlinePlayer;
            }
        }

        // Setze die gefilterten Empfänger
        $event->setRecipients($recipients);
    }
}
