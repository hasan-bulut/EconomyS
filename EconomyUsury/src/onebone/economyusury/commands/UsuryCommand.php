<?php

/*
 * EconomyS, the massive economy plugin with many features for PocketMine-MP
 * Copyright (C) 2013-2015  onebone <jyc00410@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace onebone\economyusury\commands;

use pocketmine\command\PluginCommand;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\Item;
use pocketmine\Player;

class UsuryCommand extends PluginCommand implements PluginIdentifiableCommand, Listener{
	private $requests = [];
	
	public function __construct($cmd = "usury", $plugin){
		parent::__construct($cmd, $plugin);
		$this->setUsage("/$cmd <host|request>");
		$this->setDescription("Usury master command");
		
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}
	
	public function onPlayerJoin(PlayerJoinEvent $event){
		if(isset($this->requests[strtolower($event->getPlayer()->getName())])){
			$event->getPlayer()->sendMessage("You have ".TextFormat::GREEN.count($this->requests[strtolower($event->getPlayer()->getName())]).TextFormat::RESET." received usury requests.");
		}
	}
	
	public function execute(CommandSender $sender, $label, array $params){
		if(!$this->getPlugin()->isEnabled() or !$this->testPermission($sender)){
			return false;
		}
		
		switch(array_shift($params)){
			case "host":
			switch(array_shift($params)){
				case "open":
				if($this->getPlugin()->usuryHostExists($sender->getName())){
					$sender->sendMessage("You already have usury host.");
					break;
				}
				
				$interest = array_shift($params);
				$interval = array_shift($params);
				
				if(trim($interest) == "" or trim($interval) == ""){
					$sender->sendMessage("Usage: /usury host open <interest> <interval>");
					break;
				}
				
				$this->getPlugin()->openUsuryHost($sender->getName(), $interest, $interval);
				$sender->sendMessage("Your usury host was opened.");
				break;
				case "close":
				$success = $this->getPlugin()->closeUsuryHost($sender->getName());
				if($success){
					$sender->sendMessage("You have closed your usury host. All the queues were cancelled and guarantee items were returned.");
				}else{
					$sender->sendMessage("You don't have usury host opened.");
				}
				break;
				case "accept":
				$player = strtolower(array_shift($params));
				if(trim($player) == ""){
					$sender->sendMessage("Usage: /usury host accept <player>");
					break;
				}
				if(isset($this->requests[strtolower($sender->getName())][$player])){
					$this->getPlugin()->joinHost($player, $sender->getName(), 0.2, $this->requests[strtolower($sender->getName())][$player]);
					$sender->sendMessage("You have accepted player ".TextFormat::GREEN.$player.TextFormat::RESET." to your usury host.");
					unset($this->requests[strtolower($sender->getName())][$player]);
					return true;
				}
				$sender->sendMessage("You don't have player ".TextFormat::GREEN.$player.TextFormat::RESET." in your request list.");
				break;
				case "decline":
				
				break;
				default:
				$sender->sendMessage("Usage: /usury host <open|close|accept|decline>");
			}
			break;
			case "request":
			$requestTo = strtolower(array_shift($params));
			$item = array_shift($params);
			$count = array_shift($params);
			if(trim($requestTo) == "" or trim($item) == "" or !is_numeric($count)){
				$sender->sendMessage("Usage: /usury request <host> <guarantee item> <count>");
				break;
			}
			
			if(!$this->getPlugin()->usuryHostExists($requestTo)){
				$sender->sendMessage("There's no usury host ".TextFormat::GREEN.$requestTo.TextFormat::RESET);
				break;
			}
			if(isset($this->requests[$requestTo][strtolower($sender->getName())]) or $this->getPlugin()->isPlayerJoinedHost($sender->getName(), $requestTo)){
				$sender->sendMessage("You are already related to the host ".TextFormat::GREEN.$requestTo.TextFormat::RESET);
				break;
			}
			
			$item = Item::fromString($item);
			$item->setCount($count);
			if($sender->getInventory()->contains($item)){
			//	$sender->getInventory()->removeItem($item);
				$this->requests[$requestTo][strtolower($sender->getName())] = $item;
				$sender->sendMessage("You have sent request to host ".TextFormat::GREEN.$requestTo.TextFormat::RESET);
				if(($player = $this->getPlugin()->getServer()->getPlayerExact($requestTo)) instanceof Player){
					$player->sendMessage("You received a usury host client request by ".TextFormat::GREEN.$sender->getName().TextFormat::RESET);
				}
			}else{
				$sender->sendMessage(TextFormat::RED."You don't have enough guarantee items!");
			}
			break;
			case "cancel":
			
			break;
			default:
			$sender->sendMessage("Usage: ".$this->getUsage());
		}
		return true;
	}
}