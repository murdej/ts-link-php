<?php


namespace Murdej\TsLinkPhp;

#[\Attribute]
class ClientMethodImport
{
	private string $from;
	private array $types;

	public function __construct(string $from, array $types)
	{
		$this->from = $from;
		$this->types = $types;
	}
}