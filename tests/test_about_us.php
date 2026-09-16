<?php
/**
 * Automated Verification Suite for About Us Section & Merged Home Experience
 */
$rootDir = dirname(__DIR__);
require_once $rootDir . '/config/database.php';

echo "=======================================================================\n";
echo " Running About Us & Merged Home Experience Verification Suite\n";
echo "=======================================================================\n\n";

$passed = 0;
$failed = 0;

function assert_test($cond, $msg) {
    global $passed, $failed;
    if ($cond) {
        echo "  [PASS] $msg\n";
        $passed++;
    } else {
        echo "  [FAIL] $msg\n";
        $failed++;
    }
}

// 1. File existence & backward-compatibility redirect
$aboutPath = $rootDir . '/public/AboutUs.php';
assert_test(file_exists($aboutPath), "public/AboutUs.php exists on disk for routing compatibility");
$aboutCode = file_get_contents($aboutPath);
assert_test(strpos($aboutCode, 'Home.php#about') !== false, "AboutUs.php smoothly redirects to Home.php#about");

// 2. Merged Home Page verification
$homePath = $rootDir . '/public/Home.php';
assert_test(file_exists($homePath), "public/Home.php exists on disk");
$homeCode = file_get_contents($homePath);

// 3. Global CSS, Fonts & Styling
assert_test(strpos($homeCode, 'assets/css/global.css') !== false, "Home.php includes global.css for unified luxury design system");
assert_test(strpos($homeCode, 'Plus+Jakarta+Sans') !== false, "Home.php includes modern Plus Jakarta Sans typography");

// 4. Hero Brand Statement & Trust Metrics
assert_test(strpos($homeCode, 'The Story Behind EVENTFLARE') !== false, "Home.php #about contains hero story badge");
assert_test(strpos($homeCode, '5,000') !== false, "Home.php #about contains 5,000+ celebrations metric");
assert_test(strpos($homeCode, '150') !== false, "Home.php #about contains 150+ accredited specialists metric");
assert_test(strpos($homeCode, '98.4') !== false, "Home.php #about contains 98.4% client satisfaction metric");
assert_test(strpos($homeCode, 'Upfront Price Transparency') !== false, "Home.php #about contains price transparency metric");

// 5. Core Foundation Pillars: Mission, Vision, Guarantee
assert_test(strpos($homeCode, 'Our Mission') !== false, "Home.php #about contains Our Mission pillar");
assert_test(strpos($homeCode, 'Our Vision') !== false, "Home.php #about contains Our Vision pillar");
assert_test(strpos($homeCode, 'The EVENTFLARE Guarantee') !== false, "Home.php #about contains The EVENTFLARE Guarantee pillar");

// 6. How It Works (4-Step Flow)
assert_test(strpos($homeCode, 'Choose Your Universe') !== false, "Home.php #about Step 1: Choose Your Universe present");
assert_test(strpos($homeCode, 'Curate Your Specialists') !== false, "Home.php #about Step 2: Curate Your Specialists present");
assert_test(strpos($homeCode, 'Live Budget Simulation') !== false, "Home.php #about Step 3: Live Budget Simulation present");
assert_test(strpos($homeCode, 'Synchronized Execution') !== false, "Home.php #about Step 4: Synchronized Execution present");

// 7. Client Feedback & Verified Reviews
assert_test(strpos($homeCode, 'Verified Client Feedback') !== false, "Home.php #reviews contains Verified Client Feedback section tag");
assert_test(strpos($homeCode, 'What Celebration') !== false, "Home.php #reviews contains celebration hosts testimonials header");
assert_test(strpos($homeCode, 'review-stars') !== false, "Home.php #reviews includes 5-star rating indicators");
assert_test(strpos($homeCode, 'review-quote') !== false, "Home.php #reviews includes detailed review quotes");
assert_test(strpos($homeCode, 'verified-badge') !== false, "Home.php #reviews includes Verified Buyer checkmark badges");
assert_test(strpos($homeCode, 'display_reviews') !== false, "Home.php #reviews dynamically iterates through database reviews");

// 8. Core Trust Advantages
assert_test(strpos($homeCode, '100% Vetted Partners') !== false, "Home.php #about contains 100% Vetted Partners trust card");
assert_test(strpos($homeCode, 'Zero Hidden Markups') !== false, "Home.php #about contains Zero Hidden Markups trust card");
assert_test(strpos($homeCode, 'ChooseEvent.php') !== false, "Home.php links to ChooseEvent.php");

echo "\n=======================================================================\n";
echo "Summary: $passed Passed, $failed Failed\n";
echo "=======================================================================\n";

if ($failed === 0) {
    echo "🎉 About Us & Merged Home Verification Suite PASSED!\n";
    exit(0);
} else {
    echo "❌ Verification Suite Failed!\n";
    exit(1);
}
