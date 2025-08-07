<?php

namespace aaronfc\phpx;

class SafeHtml {
	public function __construct(
		private string $value
	){}

	public function __toString(): string {
		return $this->value;
	}
}
