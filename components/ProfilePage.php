<?php

namespace aaronfc\phpx\components;

use aaronfc\phpx\Component;

require_once __DIR__ . '/../Component.php';
require_once __DIR__ . '/ActivityList.php';

class ProfilePage extends Component {
	public function __construct(
		protected string $username,
		protected int $level,
		protected array $activities
	) {}

	protected function css(): string {
		return file_get_contents( __DIR__ . '/ProfilePage.css' );
	}

	protected function render(): void {
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Profile: <?=$this->username?></title>
		<style>
			.oldcss{}
		</style>
		<style>
			<?=$this->allCss()?>
		</style>
	</head>
	<body>
		<div class="profile">
			<div class="username">Username: <?=$this->username?></div>
			<div class="level">Level: <?=$this->level?></div>

			<?php if (!empty($this->activities)): ?>
				<div class="activities">
					<h3>Recent Activities:</h3>
					<?=new ActivityList($this->activities)?>
				</div>
			<?php else: ?>
				<div class="no-activities">No recent activities</div>
			<?php endif ?>

			<!-- Example of raw printing (using explicit echo) usage for HTML content -->
			<div class="footer">
				<?=$this->raw( "<small>Profile loaded at " . date('Y-m-d H:i:s') . "</small>" )?>
			</div>
		</div>
	</body>
</html>
<?php
	}
}
