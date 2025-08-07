<?php
namespace aaronfc\phpx;

require_once __DIR__ . '/Component.php';
require_once __DIR__ . '/SafeHtml.php';

function test(string $desc, callable $testFn): void {
	Component::reset();
	try {
		$testFn();
		echo "✅ $desc\n";
	} catch (\Throwable $e) {
		echo "❌ $desc: {$e->getMessage()}\n";
	}
}



test('Basic rendering with auto-escaping (short syntax)', function() {
	$component = new class("Hello, World!") extends Component {
		public function __construct(protected string $text) {}
		protected function render(): void { ?><div><?=$this->text?></div><?php }
	};

	$output = (string)$component;
	assert($output === "<div>Hello, World!</div>");
});

test('Basic rendering with auto-escaping (echo syntax)', function() {
	$component = new class("Hello, World!") extends Component {
		public function __construct(protected string $text) {}
		protected function render(): void { ?><div><? echo $this->text?></div><?php }
	};

	$output = (string)$component;
	assert($output === "<div>Hello, World!</div>");
});

test('Raw output (short syntax)', function() {
	$component = new class("<strong>Bold</strong>") extends Component {
		public function __construct(protected string $html) {}
		protected function render(): void { ?><div><?=$this->raw($this->html)?></div><?php }
	};

	$output = (string)$component;
	assert($output === "<div><strong>Bold</strong></div>");
});

test('Raw output (echo syntax)', function() {
	$component = new class("<strong>Bold</strong>") extends Component {
		public function __construct(protected string $html) {}
		protected function render(): void { ?><div><? echo $this->raw($this->html)?></div><?php }
	};

	$output = (string)$component;
	assert($output === "<div><strong>Bold</strong></div>");
});

test('XSS protection (short syntax)', function() {
	$component = new class("<script>alert('xss')</script>") extends Component {
		public function __construct(protected string $userInput) {}
		protected function render(): void { ?><div><?=$this->userInput?></div><?php }
	};

	$output = (string)$component;
	assert(strpos($output, '&lt;script&gt;') !== false);
});

test('XSS protection (echo syntax)', function() {
	$component = new class("<script>alert('xss')</script>") extends Component {
		public function __construct(protected string $userInput) {}
		protected function render(): void { ?><div><? echo $this->userInput?></div><?php }
	};

	$output = (string)$component;
	assert(strpos($output, '&lt;script&gt;') !== false);
});

test('Auto-escaping with extra spaces (short syntax)', function() {
	$component = new class("<test>") extends Component {
		public function __construct(protected string $userInput) {}
		protected function render(): void { ?><div><?=    $this->userInput     ?></div><?php }
	};

	$output = (string)$component;
	assert( $output === "<div>&lt;test&gt;</div>" );
});

test('Auto-escaping with extra spaces (echo syntax)', function() {
	$component = new class("<test>") extends Component {
		public function __construct(protected string $userInput) {}
		protected function render(): void { ?><div><?   echo    $this->userInput     ?></div><?php }
	};

	$output = (string)$component;
	assert( $output === "<div>&lt;test&gt;</div>" );
});

test('Escaping content with question marks (short syntax)', function() {
	$component = new class("<test>") extends Component {
		public function __construct(protected string $userInput) {}
		protected function render(): void { ?><div><?="Are you serious?"?></div><?php }
	};

	$output = (string)$component;
	assert( $output === "<div>Are you serious?</div>" );
});

test('Escaping content with question marks (echo syntax)', function() {
	$component = new class("<test>") extends Component {
		public function __construct(protected string $userInput) {}
		protected function render(): void { ?><div><? echo "Are you serious?"?></div><?php }
	};

	$output = (string)$component;
	assert( $output === "<div>Are you serious?</div>" );
});

test('Auto-escaping with ending semicolon (short syntax)', function() {
	$component = new class("<test>") extends Component {
		public function __construct(protected string $userInput) {}
		protected function render(): void { ?><div><?=$this->userInput;?></div><?php }
	};

	$output = (string)$component;
	assert( $output === "<div>&lt;test&gt;</div>" );
});

test('Auto-escaping with ending semicolon (echo syntax)', function() {
	$component = new class("<test>") extends Component {
		public function __construct(protected string $userInput) {}
		protected function render(): void { ?><div><? echo $this->userInput;?></div><?php }
	};

	$output = (string)$component;
	assert( $output === "<div>&lt;test&gt;</div>" );
});

test('Complex expressions with nested quotes/operators (short syntax)', function () {
	$component = new class("Aarón") extends Component {
		public function __construct(protected string $username) {}
		protected function render(): void {
			?><div><?="Hello, " . ( $this->username ? "Mr.{$this->username}" : "Guest") . "!" ?></div><?php
		}
	};
	$output = (string)$component;
	assert($output === "<div>Hello, Mr.Aarón!</div>");
});

test( 'Multiple variables in same echo (short syntax)', function() {
	$component = new class("Aarón", "PHPX") extends Component {
		public function __construct(protected string $username, protected string $appName) {}
		protected function render(): void {
			?><div><?="Welcome {$this->username} to {$this->appName}!"?></div><?php
		}
	};

	$output = (string)$component;
	assert($output === "<div>Welcome Aarón to PHPX!</div>");
});

test( 'Multiple echos in same line (short syntax)', function() {
	$component = new class("Aarón", "PHPX") extends Component {
		public function __construct(protected string $username, protected string $appName) {}
		protected function render(): void {
			?><div><?=$this->username?> @ <?=$this->appName?>!</div><?php
		}
	};

	$output = (string)$component;
	assert($output === "<div>Aarón @ PHPX!</div>");
});

test('Automatic class resolution in same namespace', function() {
	// Create a mock class in the same namespace
	class InnerComponent extends Component {
		protected function render(): void { ?><span>Inner</span><?php }
	};

	$component = new class() extends Component {
		protected function render(): void {
			// This should resolve to the full class name automatically
			?><div><?=new InnerComponent()?></div><?php
		}
	};

	$output = (string)$component;
	assert($output === "<div><span>Inner</span></div>");
	$source = Component::getCachedSource( $component::class );
	assert( str_contains($source, 'new \\aaronfc\\phpx\\InnerComponent') );
});

test('Automatic class resolution in same namespace not for JS', function() {
	$component = new class() extends Component {
		protected function render(): void {
			// This should resolve to the full class name automatically
			?><div><script>alert(new Set());</script></div><?php
		}
	};

	$output = (string)$component;
	$source = Component::getCachedSource( $component::class );
	assert( ! str_contains($source, '\\aaronfc\\phpx') );
});

test( 'CSS is rendered when available when defined', function() {
	$component = new class() extends Component {
		protected function css(): string { return "body { background: #f0f0f0; }"; }
		protected function render(): void { ?><style><?=$this->allCss()?></style><div class="test">Test</div><?php }
	};

	$output = (string)$component;
	assert( preg_match( '/<style>.*body { background: #f0f0f0; }.*<\/style>/Uis', $output ) );
});

test( 'Multiple CSS definitions are bundled and not repeated', function() {
	class SubComponent extends Component {
		protected function css(): string { return ".subcomponent { background: #f00; }"; }
		protected function render(): void { ?><div class="subcomponent">Subcomponent</div><?php }
	};

	$component = new class() extends Component {
		protected function css(): string { return ".component { background: #0f0; }"; }
		protected function render(): void { ?><style><?=$this->allCss()?></style><div class="component">Component:<br/><?=new SubComponent()?><br/><?=new SubComponent()?></div><?php }
	};

	$output = (string)$component;

	assert( str_contains( $output, '.component { background: #0f0; }') );
	assert( str_contains( $output, '.subcomponent { background: #f00; }') );
	assert( substr_count($output, '.subcomponent') === 1, 'Subcomponent CSS should only appear once' );
});
