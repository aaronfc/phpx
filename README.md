# PHPX

A PHP component library for your frontend with automatic XSS protection.

> [!WARNING]
> THIS IS AN EXPERIMENT I AM RUNNING FOR FUN AND FOR MYSELF. BE CAREFUL IF YOU DECIDE TO USE IT.

## Installation

Install via Composer:

```bash
composer require aaronfc/phpx
```

## Quick Start

### Basic Component

Create a simple component by extending the `Component` class:

```php
<?php
use aaronfc\phpx\Component;

class WelcomeMessage extends Component {
    public function __construct(
        protected string $username,
        protected int $loginCount
    ) {}

    protected function render(): void {
?>
<div class="welcome">
    <h1>Welcome, <?=$this->username?>!</h1>
    <p>You have logged in <?=$this->loginCount?> times.</p>
</div>
<?php
    }
}

// Usage
$component = new WelcomeMessage("Aaron", 42);
echo $component;
```

**Rendered output:**
```html
<div class="welcome">
    <h1>Welcome, Aaron!</h1>
    <p>You have logged in 42 times.</p>
</div>
```

### Automatic XSS Protection

PHPX automatically escapes output via short tags (`<?=`)and `echo` statements to prevent XSS attacks:

```php
class UserProfile extends Component {
    public function __construct(
        protected string $userInput
    ) {}

    protected function render(): void {
?>
<div class="profile">
    <p>Bio: <?=$this->userInput?></p>
</div>
<?php
    }
}

// Malicious input is automatically escaped
$component = new UserProfile("<script>alert('xss')</script>");
echo $component;
```

**Rendered output (safe):**
```html
<div class="profile">
    <p>Bio: &lt;script&gt;alert('xss')&lt;/script&gt;</p>
</div>
```

### Raw HTML Output

When you need to output trusted HTML, use the `raw()` method:

```php
class ArticleContent extends Component {
    public function __construct(
        protected string $title,
        protected string $trustedHtml
    ) {}

    protected function render(): void {
?>
<article>
    <h1><?=$this->title?></h1>
    <div class="content">
        <?=$this->raw($this->trustedHtml)?>
    </div>
</article>
<?php
    }
}

$component = new ArticleContent(
    "My Article", 
    "<p>This is <strong>trusted</strong> HTML content.</p>"
);
echo $component;
```

**Rendered output:**
```html
<article>
    <h1>My Article</h1>
    <div class="content">
        <p>This is <strong>trusted</strong> HTML content.</p>
    </div>
</article>
```

### Component Composition

Components can render other components safely:

```php
class ActivityItem extends Component {
    public function __construct(
        protected array $activity
    ) {}

    protected function render(): void {
?>
<li class="activity">
    <span><?=$this->activity['description']?></span>
    <time><?=$this->activity['time']?></time>
</li>
<?php
    }
}

class ActivityList extends Component {
    public function __construct(
        protected array $activities
    ) {}

    protected function render(): void {
?>
<ul class="activities">
    <?php foreach ($this->activities as $activity): ?>
        <?=new ActivityItem($activity)?>
    <?php endforeach ?>
</ul>
<?php
    }
}

$activities = [
    ['description' => 'User logged in', 'time' => '2025-01-15 10:30'],
    ['description' => 'Profile updated', 'time' => '2025-01-15 11:15']
];

$component = new ActivityList($activities);
echo $component;
```

**Rendered output:**
```html
<ul class="activities">
    <li class="activity">
        <span>User logged in</span>
        <time>2025-01-15 10:30</time>
    </li>
    <li class="activity">
        <span>Profile updated</span>
        <time>2025-01-15 11:15</time>
    </li>
</ul>
```

### CSS Support

Components can include CSS that gets automatically bundled. Make sure you call `allCss()` once in your Components hierarchy (top page or layout component):

```php
class Layout extends Component {
    public function __construct(
        protected string $title,
        protected Component $content
    ) {}
    protected function render(): void {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$this->title?></title>
    <style><?=$this->allCss()?></style>
</head>
<body>
    <div class="container">
        <?=$this->content?>
    </div>
</body>
</html>
<?php
    }
}

class StyledComponent extends Component {
    public function __construct(
        protected string $title,
        protected string $content
    ) {}

    protected function css(): string {
        return "
            .card {
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 16px;
                margin: 8px 0;
            }
            .card-title {
                font-weight: bold;
                margin-bottom: 8px;
            }
        ";
    }

    protected function render(): void {
?>
<div class="card">
    <div class="card-title"><?=$this->title?></div>
    <div class="card-content"><?=$this->content?></div>
</div>
<?php
    }
}
```

## Key Features

- **Automatic XSS Protection**: All `<?=$var?>` and `<? echo $var?>` expressions are automatically escaped
- **Safe Component Composition**: Components can safely render other components
- **Raw HTML Support**: Use `raw()` method for trusted HTML content  
- **CSS Bundling**: Component CSS is automatically collected and bundled
- **Familiar Syntax**: Uses standard PHP template syntax you already know

## KNOWN LIMITATIONS
- Check the [issues](https://github.com/aaronfc/phpx/issues).
- Properties on Component layout need to be set as protected. Private properties are not accessible when evaling.
- Documentation is limited on purpose at the moment. This is not supposed to be used by anyone else a part from me.
