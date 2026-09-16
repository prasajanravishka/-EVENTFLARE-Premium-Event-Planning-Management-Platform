<?php
/**
 * Automated Verification Suite for Contact Concierge & Merged Home Experience
 */
$rootDir = dirname(__DIR__);
require_once $rootDir . '/config/database.php';

echo "=======================================================================\n";
echo " Running Contact Concierge & Merged Home Verification Suite\n";
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
$contactPath = $rootDir . '/public/Contact.php';
assert_test(file_exists($contactPath), "public/Contact.php exists on disk for routing compatibility");
$contactCode = file_get_contents($contactPath);
assert_test(strpos($contactCode, 'Home.php#contact') !== false, "Contact.php smoothly redirects to Home.php#contact");

// 2. Merged Home Page verification
$homePath = $rootDir . '/public/Home.php';
assert_test(file_exists($homePath), "public/Home.php exists on disk");
$homeCode = file_get_contents($homePath);

// 3. Global CSS, fonts & light theme styling
assert_test(strpos($homeCode, 'assets/css/global.css') !== false, "Home.php includes global.css for unified design system");
assert_test(strpos($homeCode, 'Plus+Jakarta+Sans') !== false, "Home.php includes modern Plus Jakarta Sans typography");
assert_test(strpos($homeCode, 'orb') !== false, "Home.php includes ambient background glow orbs");

// 4. Concierge Header & Guarantees
assert_test(strpos($homeCode, 'Dedicated Event Concierge') !== false, "Home.php #contact contains concierge badge");
assert_test(strpos($homeCode, 'Let\'s Connect & Build Your') !== false, "Home.php #contact contains hero headline");
assert_test(strpos($homeCode, 'Guaranteed 2-Hour Response Time') !== false, "Home.php #contact contains 2-hour response guarantee chip");
assert_test(strpos($homeCode, '100% Privacy & Data Protected') !== false, "Home.php #contact contains privacy guarantee chip");
assert_test(strpos($homeCode, 'Free Budget Consultation') !== false, "Home.php #contact contains budget consultation guarantee chip");

// 5. Multi-Channel Concierge Directory
assert_test(strpos($homeCode, '+94 11 234 5678') !== false, "Home.php #contact contains hotline phone number");
assert_test(strpos($homeCode, 'tel:+94112345678') !== false, "Home.php #contact includes direct dial telephone link");
assert_test(strpos($homeCode, '+94 77 123 4567') !== false, "Home.php #contact contains WhatsApp number");
assert_test(strpos($homeCode, 'wa.me/94771234567') !== false, "Home.php #contact includes WhatsApp direct chat link");
assert_test(strpos($homeCode, 'support@eventflare.com') !== false, "Home.php #contact contains official concierge email");
assert_test(strpos($homeCode, 'mailto:support@eventflare.com') !== false, "Home.php #contact includes mailto email link");
assert_test(strpos($homeCode, '12 Galle Road, Colombo 03') !== false, "Home.php #contact contains Colombo HQ address");

// 6. Form Fields & Database Submission Logic
assert_test(strpos($homeCode, 'name="firstname"') !== false, "Home.php #contact form contains firstname input");
assert_test(strpos($homeCode, 'name="lastname"') !== false, "Home.php #contact form contains lastname input");
assert_test(strpos($homeCode, 'name="email"') !== false, "Home.php #contact form contains email input");
assert_test(strpos($homeCode, 'name="phone"') !== false, "Home.php #contact form contains phone input");
assert_test(strpos($homeCode, 'name="message"') !== false, "Home.php #contact form contains message textarea");
assert_test(strpos($homeCode, 'INSERT INTO contact_messages') !== false, "Home.php handles database insertion into contact_messages");
assert_test(strpos($homeCode, 'Send Concierge Inquiry') !== false, "Home.php #contact contains prominent submit button");

echo "\n=======================================================================\n";
echo "Summary: $passed Passed, $failed Failed\n";
echo "=======================================================================\n";

if ($failed === 0) {
    echo "🎉 Contact Concierge & Merged Home Verification Suite PASSED!\n";
    exit(0);
} else {
    echo "❌ Verification Suite Failed!\n";
    exit(1);
}
