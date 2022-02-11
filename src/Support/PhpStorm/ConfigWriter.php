<?php

namespace InterNACHI\Modular\Support\PhpStorm;

use DOMDocument;
use InterNACHI\Modular\Support\ModuleRegistry;
use SimpleXMLElement;

abstract class ConfigWriter
{
	/**
	 * @var string
	 */
	public $last_error;
	
	/**
	 * @var string
	 */
	protected $config_path;
	
	/**
	 * @var \InterNACHI\Modular\Support\ModuleRegistry
	 */
	protected $module_registry;
	
	abstract public function write(): bool;
	
	public function __construct($config_path, ModuleRegistry $module_registry)
	{
		$this->config_path = $config_path;
		$this->module_registry = $module_registry;
	}
	
	public function handle(): bool
	{
		if (! $this->checkConfigFilePermissions()) {
			return false;
		}
		
		return $this->write();
	}
	
	protected function checkConfigFilePermissions(): bool
	{
		if (! is_readable($this->config_path) || ! is_writable($this->config_path)) {
			return $this->error("Unable to find or read: '{$this->config_path}'");
		}
		
		if (! is_writable($this->config_path)) {
			return $this->error("Config file is not writable: '{$this->config_path}'");
		}
		
		return true;
	}
	
	protected function error(string $message): bool
	{
		$this->last_error = $message;
		return false;
	}
	
	protected function formatXml(SimpleXMLElement $xml): string
	{
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($xml->asXML());
		
		$xml = $dom->saveXML();
		$xml = preg_replace('~(\S)/>\s*$~m', '$1 />', $xml);
		
		return $xml;
	}
}
