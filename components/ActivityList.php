<?php

namespace aaronfc\phpx\components;

use aaronfc\phpx\Component;

class ActivityList extends Component {
	public function __construct(
		protected array $activities
	) {}

	protected function css(): string {
		return file_get_contents(__DIR__ . '/ActivityList.css');
	}

	protected function render(): void {
?>
<ul class="activity-list">
	<?php foreach ($this->activities as $activity): ?>
		<li class="activity-item">
			<span class="description"><?=$activity['description']?></span>
			<time class="timestamp"><?=$activity['time']?></time>
		</li>
	<?php endforeach ?>
</ul>
<?php
	}
}
