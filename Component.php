<?php

namespace aaronfc\phpx;

require_once 'SafeHtml.php';

abstract class Component {
	private static array $sourceCache = [];
	private static array $cssCache = [];

	public function __toString(): string {
		// Register CSS per class.
		if ( ! is_null( $this->css() ) ) {
			$className = get_class($this);
			if (!isset(self::$cssCache[$className])) {
				self::$cssCache[$className] = $this->css();
			}
		}

		// Initial render transforming source code.
		ob_start();
		$this->renderWithTransformation();
		$output = ob_get_clean();

		// Replace CSS placeholder with all bundled CSS.
		if ( str_contains( $output, '#CSS-PLACEHOLDER#' ) ) {
			$cssBundle = '';
			foreach (self::$cssCache as $class => $css) {
				$cssBundle .= '/* CSS for ' . $class . " */\n";
				$cssBundle .= $css . "\n";
			}
			$output = str_replace('#CSS-PLACEHOLDER#', $cssBundle, $output);
		}
		return $output;
	}

	private function renderWithTransformation(): void {
		$reflection = new \ReflectionMethod($this, 'render');
		$className = get_class($this);

		// Cache transformed source per class
		if (!isset(self::$sourceCache[$className])) {
			$source = $this->getMethodSource($reflection);
			$namespaceContext = $this->getNamespaceContext($reflection);
			$transformed = $this->transformSource($source, $namespaceContext);
			self::$sourceCache[$className] = $transformed;
		}

		// Execute transformed code
		eval(self::$sourceCache[$className]);
	}

	private function getMethodSource(\ReflectionMethod $method): string {
		$file = file_get_contents($method->getFileName());
		$lines = explode("\n", $file);

		$startLine = $method->getStartLine() - 1;
		$endLine = $method->getEndLine() - 1;

		$methodLines = array_slice($lines, $startLine, $endLine - $startLine + 1);

		// Remove method signature and extract body
		$source = implode("\n", $methodLines);

		// Extract content between first { and last }
		$firstBrace = strpos($source, '{');
		$lastBrace = strrpos($source, '}');

		return substr($source, $firstBrace + 1, $lastBrace - $firstBrace - 1);
	}

	private function getNamespaceContext(\ReflectionMethod $method): array {
		$class = $method->getDeclaringClass();
		$file = file_get_contents($method->getFileName());
		$lines = explode("\n", $file);

		$namespace = $class->getNamespaceName();
		$useStatements = [];

		// Extract use statements from file
		foreach ($lines as $line) {
			$trimmed = trim($line);

			if (preg_match('/^use\s+([^;]+);/', $trimmed, $matches)) {
				$useClause = trim($matches[1]);

				// Handle "use Foo\Bar as Baz"
				if (strpos($useClause, ' as ') !== false) {
					list($fullClass, $alias) = explode(' as ', $useClause, 2);
					$useStatements[trim($alias)] = trim($fullClass);
				} else {
					// Handle "use Foo\Bar"
					$parts = explode('\\', $useClause);
					$className = end($parts);
					$useStatements[$className] = $useClause;
				}
			}

			// Stop at class declaration
			if (preg_match('/^(abstract\s+)?class\s+/', $trimmed)) {
				break;
			}
		}

		return [
			'namespace' => $namespace,
			'use' => $useStatements
		];
	}

	private function transformSource(string $source, array $namespaceContext): string {
		// First, resolve class names to fully qualified names
		$source = $this->resolveClassNames($source, $namespaceContext);

		// Transform short syntax <?=$var expressions
		$pattern = '/\<\?\=(.+)\s*;?\s*\?\>/Uis';

		$source = preg_replace_callback($pattern, function($matches) {
			$expression = trim($matches[1]);

			// Auto-escape by wrapping in e() method
			return "<?=\$this->e($expression)?>";
		}, $source);

		// Transform echo expressions.
		$pattern = '/\<\?(?:php)?\s+echo(.+)\s*;?\s*\?\>/Uis';

		$source = preg_replace_callback($pattern, function($matches) {
			$expression = trim($matches[1]);

			// Auto-escape by wrapping in e() method
			return "<?=\$this->e($expression)?>";
		}, $source);

		return $source;
	}

	private function resolveClassNames(string $source, array $namespaceContext): string {
		// Pattern to match "new ClassName(" - the most common case in templates
		$pattern = '/<\?.*\bnew\s+([A-Z][a-zA-Z0-9_]*)\s*\(.*\?>/';

		return preg_replace_callback($pattern, function($matches) use ($namespaceContext) {
			$className = $matches[1];

			// Skip if already fully qualified (starts with \)
			if ($className[0] === '\\') {
				return $matches[0];
			}

			// Check if it's in use statements
			if (isset($namespaceContext['use'][$className])) {
				$fullyQualified = '\\' . $namespaceContext['use'][$className];
				return str_replace($className, $fullyQualified, $matches[0]);
			}

			// If not in use statements, assume it's in the same namespace
			if (!empty($namespaceContext['namespace'])) {
				$fullyQualified = '\\' . $namespaceContext['namespace'] . '\\' . $className;
				return str_replace($className, $fullyQualified, $matches[0]);
			}

			// Return as-is if no namespace context
			return $matches[0];
		}, $source);
	}

	protected function e($value): string {
		// Don't escape Component instances - they're already safe HTML
		if ($value instanceof \aaronfc\phpx\Component ) {
			return (string)$value;
		}

		// Don't escape SafeHtml instances - they're already safe HTML
		if ( $value instanceof SafeHtml ) {
			return (string)$value;
		}

		if (is_null($value)) return '';
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}

	protected function raw($value): SafeHtml {
		return new SafeHtml($value);
	}

	protected function css(): ?string {
		return null;
	}

	protected function allCss(): string {
		return '#CSS-PLACEHOLDER#';
	}

	abstract protected function render(): void;

	/**
	 * Used to reset static caches useful for testing.
	 */
	public static function reset(): void {
		self::$sourceCache = [];
		self::$cssCache = [];
	}

	/**
	 * Returns the cached source code for a give class. Useful for testing.
	 */
	public static function getCachedSource( $classname ): string {
		if ( isset( self::$sourceCache[$classname] ) ) {
			return self::$sourceCache[$classname];
		}
		throw new \RuntimeException("No cached source found for class $classname");
	}
}
