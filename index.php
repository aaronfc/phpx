<?php

namespace aaronfc\phpx;

use aaronfc\phpx\components\ProfilePage;

/**
 * PHP JSX-like Component System
 *
 * Features:
 * - Single-file components with typed props
 * - Auto-escaping with $this->>raw() for safe HTML
 * - Component composition via __toString()
 * - IDE-friendly PHP + HTML syntax
 * - Runtime source transformation
 */

require_once 'Component.php';
require_once 'components/ProfilePage.php';
/**
 * View Renderer for Controllers
 */
class ViewRenderer {
    public function render(Component $component): string {
        return (string)$component;
    }
}

// Simulated controller method
function get_profile() {
    $renderer = new ViewRenderer();

    $activities = [
        ['description' => 'Bought a dragon', 'time' => '2025-03-01 14:30'],
        ['description' => 'Completed quest: Save the Princess', 'time' => '2025-02-28 16:45'],
        ['description' => 'Leveled up to Level 42', 'time' => '2025-02-27 12:15'],
        ['description' => 'Joined guild: <strong>Dragon Slayers</strong>', 'time' => '2025-02-26 09:30']
    ];

    return $renderer->render(new ProfilePage(
        username: "DragonMaster<script>alert('xss')</script>", // Test XSS protection
        level: 42,
        activities: $activities
    ));
}

// ==========================================
// TEST
// ==========================================

try {
    echo get_profile();
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
