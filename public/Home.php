<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config/database.php';

$contact_error = "";
$contact_success = "";

// Process Quick / Priority Contact Form
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['send_message']) || isset($_POST['send_contact']))) {
    $firstname = htmlspecialchars(trim($_POST['firstname'] ?? ''));
    $lastname = htmlspecialchars(trim($_POST['lastname'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    if (empty($firstname) || empty($lastname) || empty($email) || empty($phone) || empty($message)) {
        $contact_error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contact_error = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_messages (firstname, lastname, email, phone, message) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssss", $firstname, $lastname, $email, $phone, $message);
            if ($stmt->execute()) {
                $contact_success = "Thank you! Your message has been sent successfully. A dedicated concierge will reach out shortly.";
            } else {
                $contact_error = "Error saving message: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $contact_error = "Database error: " . $conn->error;
        }
    }
}

// Fetch Approved Database Reviews for the Reviews section
$db_reviews = [];
if (isset($conn) && !$conn->connect_error) {
    $r_stmt = $conn->query("
        SELECT r.*, u.username, s.business_name, s.category as supplier_cat
        FROM ratings r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN suppliers s ON r.supplier_id = s.id
        WHERE r.admin_status = 'approved' AND r.rating >= 4
        ORDER BY r.rating DESC, r.id DESC
        LIMIT 6
    ");
    if ($r_stmt) {
        while ($row = $r_stmt->fetch_assoc()) {
            $initials = 'VC';
            if (!empty($row['username'])) {
                $parts = explode(' ', trim($row['username']));
                $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : substr($parts[0], 1, 1)));
            }
            $db_reviews[] = [
                'name' => !empty($row['username']) ? ucfirst($row['username']) : 'Verified Client',
                'role' => !empty($row['supplier_cat']) ? $row['supplier_cat'] . ' Review' : 'Verified Celebration',
                'event_badge' => !empty($row['business_name']) ? $row['business_name'] : 'Accredited Partner',
                'rating' => (int)$row['rating'],
                'title' => !empty($row['review_title']) ? $row['review_title'] : 'Exceptional Service & Execution',
                'comment' => $row['review_text'],
                'date' => date('M Y', strtotime($row['created_at'] ?? 'now')),
                'avatar' => $initials
            ];
        }
    }
}

// Curated Showcase Testimonials covering all 4 event universes
$curated_reviews = [
    [
        'name' => 'Dilshan & Chamari Perera',
        'role' => 'Bride & Groom',
        'event_badge' => '💍 Royal Wedding • Cinnamon Grand Ballroom',
        'rating' => 5,
        'title' => 'Our Poruwa and reception were flawlessly synchronized!',
        'comment' => 'Planning a 350-guest wedding seemed terrifying until we found EVENTFLARE. Being able to inspect Poruwa masters, floral stage decorators, and drone photographers with upfront transparent prices saved us weeks of stressful calls. On our wedding day, every single vendor arrived on schedule and delivered magic.',
        'date' => 'August 2026',
        'avatar' => 'DP'
    ],
    [
        'name' => 'Kavindu Senanayake',
        'role' => 'Alumni Batch Coordinator',
        'event_badge' => '🤝 10-Year Batch Reunion • Mount Lavinia Lawn',
        'rating' => 5,
        'title' => 'Live charcoal BBQ and acoustic duo stole the afternoon!',
        'comment' => 'Organizing 120 school batchmates is usually pure chaos. EVENTFLARE made booking the weatherproof canopy marquees, live BBQ skewer stations, and acoustic duo seamless. The budget calculator was 100% accurate down to the rupee. Everyone is still talking about how good the grilled burgers were!',
        'date' => 'July 2026',
        'avatar' => 'KS'
    ],
    [
        'name' => 'Dr. Rochelle Vandort',
        'role' => 'Birthday Host',
        'event_badge' => '🎂 50th Golden Jubilee • Waters Edge',
        'rating' => 5,
        'title' => 'Transformed our venue into a golden fairytale',
        'comment' => 'For my mother’s 50th milestone, we wanted a breathtaking pastel and gold balloon arch, custom dessert table, and live saxophone player. The vendor coordination through EVENTFLARE was spotless. Having all service contacts and invoices organized under one dashboard gave us total peace of mind.',
        'date' => 'June 2026',
        'avatar' => 'RV'
    ],
    [
        'name' => 'Devinda Rajapakse',
        'role' => 'Club & Rave Organizer',
        'event_badge' => '🎧 Neon Pulse Rave • Warehouse 42 Colombo',
        'rating' => 5,
        'title' => 'Concert-grade sound and intelligent laser beams',
        'comment' => 'The audio clarity from the line arrays and the moving head beams were festival-level quality. The DJ and lighting technicians worked in complete harmony. EVENTFLARE is the first platform in Sri Lanka that truly understands modern club party and nightlife event logistics.',
        'date' => 'May 2026',
        'avatar' => 'DR'
    ]
];

// Combine reviews
$display_reviews = !empty($db_reviews) ? array_merge($db_reviews, array_slice($curated_reviews, count($db_reviews))) : $curated_reviews;

include __DIR__ . '/../includes/navbar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVENTFLARE - Seamless Event Planning & Management</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/global.css">

    <style>
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 100px;
        }

        :root {
            --home-surface: rgba(255, 255, 255, 0.85);
            --home-border: rgba(139, 92, 246, 0.16);
            --home-border-hover: rgba(139, 92, 246, 0.35);
            --text-heading: #1e1538;
            --text-main: #3b3759;
            --text-muted: #64748b;
        }

        body {
            background: radial-gradient(circle at top right, #f4efff, #faf8ff 70%);
            background-color: #f7f5fc;
            color: var(--text-main);
            font-family: var(--font-body);
            overflow-x: hidden;
            margin: 0;
            padding-top: 0; /* navbar already has offset */
        }

        /* Responsive Master Container */
        .home-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            box-sizing: border-box;
            width: 100%;
            position: relative;
        }

        /* Ambient Background Glow Orbs */
        .orb {
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.11) 0%, rgba(99, 102, 241, 0) 70%);
            filter: blur(65px);
            z-index: -1;
            pointer-events: none;
        }
        .orb-1 { top: 2%; left: -10%; }
        .orb-2 { top: 22%; right: -12%; }
        .orb-3 { top: 48%; left: -12%; }
        .orb-4 { top: 72%; right: -10%; }
        .orb-5 { bottom: 3%; left: -8%; }

        /* Unified Section Headers */
        .section-header {
            text-align: center;
            max-width: 850px;
            margin: 0 auto 50px;
            padding: 0 15px;
        }

        .section-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(139, 92, 246, 0.08);
            color: var(--primary);
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border: 1px solid rgba(139, 92, 246, 0.2);
            margin-bottom: 16px;
        }

        .section-title {
            font-size: 40px;
            font-weight: 900;
            color: var(--text-heading);
            letter-spacing: -0.02em;
            line-height: 1.2;
            margin: 0 0 14px;
        }

        .section-title span {
            background: linear-gradient(135deg, var(--primary) 0%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section-subtitle {
            font-size: 16px;
            color: var(--text-muted);
            line-height: 1.7;
            max-width: 720px;
            margin: 0 auto;
        }

        /* Section Spacing */
        .page-section {
            padding: 50px 0 60px;
            position: relative;
        }

        /* ================= 1. HERO SECTION ================= */
        .hero {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 40px;
            align-items: center;
            padding: 30px 0 70px;
        }

        .hero-text {
            text-align: left;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(139, 92, 246, 0.09);
            border: 1px solid rgba(139, 92, 246, 0.22);
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 24px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .hero h1 {
            font-size: 58px;
            font-weight: 900;
            margin-bottom: 22px;
            color: var(--text-heading);
            letter-spacing: -0.025em;
            line-height: 1.12;
        }

        .hero h1 span {
            background: linear-gradient(135deg, var(--primary) 0%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p.description {
            font-size: 17.5px;
            color: var(--text-muted);
            margin-bottom: 35px;
            line-height: 1.75;
            max-width: 95%;
        }

        .hero-buttons {
            display: flex;
            gap: 14px;
            margin-bottom: 38px;
            flex-wrap: wrap;
        }

        .social-proof {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .avatar-group {
            display: flex;
        }

        .avatar-group img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 2px solid #ffffff;
            margin-left: -10px;
            object-fit: cover;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }

        .avatar-group img:first-child {
            margin-left: 0;
        }

        .social-proof p {
            font-size: 13.5px;
            color: var(--text-muted);
            line-height: 1.4;
            margin: 0;
        }

        .social-proof p span {
            font-weight: 800;
            color: var(--text-heading);
        }

        .hero-image-wrapper {
            position: relative;
            z-index: 1;
        }

        .hero-image {
            width: 100%;
            height: 440px;
            object-fit: cover;
            border-radius: 28px;
            border: 1px solid var(--home-border);
            box-shadow: 0 20px 45px -10px rgba(139, 92, 246, 0.2);
            transition: var(--transition-smooth);
        }

        .hero-image-wrapper:hover .hero-image {
            transform: scale(1.015);
            box-shadow: 0 25px 50px -10px rgba(139, 92, 246, 0.28);
        }

        .hero-floating-card {
            position: absolute;
            bottom: -20px;
            left: -20px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--home-border);
            padding: 14px 20px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.16);
            animation: floatSlow 4s ease-in-out infinite alternate;
        }

        @keyframes floatSlow {
            0% { transform: translateY(0); }
            100% { transform: translateY(-8px); }
        }

        .hero-floating-card .icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .hero-floating-card .text h4 {
            font-size: 14px;
            font-weight: 800;
            color: var(--text-heading);
            margin: 0;
        }

        .hero-floating-card .text p {
            font-size: 12px;
            color: var(--text-muted);
            margin: 2px 0 0;
        }

        /* ================= 2. SERVICES ALTERNATING SHOWCASE ================= */
        .services-alternate {
            display: flex;
            flex-direction: column;
            gap: 55px;
        }

        .alt-row {
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            gap: 45px;
            align-items: center;
        }

        .alt-row.alt-reverse {
            grid-template-columns: 1fr 1.15fr;
        }

        .alt-text {
            padding: 38px 34px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background: var(--home-surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--home-border);
            border-radius: 24px;
            box-shadow: var(--shadow-premium);
            transition: var(--transition-smooth);
        }

        .alt-text h3 {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-heading);
            margin: 0;
            background: linear-gradient(135deg, var(--text-heading) 40%, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .alt-text p {
            color: var(--text-muted);
            font-size: 14.5px;
            line-height: 1.7;
            margin: 0;
        }

        .alt-text .btn {
            align-self: flex-start;
            margin-top: 8px;
        }

        .alt-image {
            height: 330px;
            border-radius: 24px;
            border: 1px solid var(--home-border);
            background-size: cover;
            background-position: center;
            box-shadow: var(--shadow-premium);
            transition: var(--transition-smooth);
        }

        .alt-row:hover .alt-image {
            transform: scale(1.02);
            box-shadow: 0 16px 36px rgba(139, 92, 246, 0.18);
        }

        .alt-row:hover .alt-text {
            border-color: var(--home-border-hover);
        }

        /* ================= 3. FEATURES / INNOVATIONS ================= */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .feature-card {
            background: var(--home-surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--home-border);
            border-radius: 22px;
            padding: 34px 28px;
            text-align: left;
            transition: var(--transition-smooth);
            box-shadow: var(--shadow-premium);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: var(--home-border-hover);
            box-shadow: 0 16px 36px rgba(139, 92, 246, 0.14);
        }

        .feature-icon {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            background: rgba(139, 92, 246, 0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 20px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            transition: var(--transition-smooth);
        }

        .feature-card:hover .feature-icon {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #ffffff;
            transform: scale(1.08);
        }

        .feature-card h3 {
            font-size: 19px;
            font-weight: 800;
            color: var(--text-heading);
            margin: 0 0 10px;
        }

        .feature-card p {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.65;
            margin: 0;
        }

        /* ================= 4. ABOUT US & TRUST METRICS ================= */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 60px;
        }

        .metric-card {
            background: var(--home-surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--home-border);
            border-radius: 20px;
            padding: 24px 20px;
            text-align: center;
            box-shadow: var(--shadow-premium);
            transition: var(--transition-smooth);
        }

        .metric-card:hover {
            transform: translateY(-4px);
            border-color: var(--home-border-hover);
            box-shadow: 0 14px 30px rgba(139, 92, 246, 0.12);
        }

        .metric-val {
            font-size: 38px;
            font-weight: 900;
            color: var(--text-heading);
            line-height: 1;
            margin-bottom: 8px;
        }

        .metric-val span {
            color: var(--primary);
        }

        .metric-lbl {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-muted);
        }

        /* Foundation Pillars */
        .pillars-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 65px;
        }

        .pillar-card {
            background: var(--home-surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--home-border);
            border-radius: 22px;
            padding: 32px 26px;
            box-shadow: var(--shadow-premium);
            transition: var(--transition-smooth);
        }

        .pillar-card:hover {
            transform: translateY(-5px);
            border-color: var(--home-border-hover);
            box-shadow: 0 16px 34px rgba(139, 92, 246, 0.14);
        }

        .pillar-icon-wrap {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: rgba(139, 92, 246, 0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 18px;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .pillar-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-heading);
            margin: 0 0 12px;
        }

        .pillar-desc {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.65;
            margin: 0;
        }

        /* Steps Grid */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 65px;
        }

        .step-card {
            background: var(--home-surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--home-border);
            border-radius: 20px;
            padding: 26px 20px;
            position: relative;
            box-shadow: var(--shadow-premium);
            transition: var(--transition-smooth);
        }

        .step-card:hover {
            transform: translateY(-4px);
            border-color: var(--home-border-hover);
        }

        .step-badge {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #ffffff;
            font-weight: 800;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 6px 14px rgba(139, 92, 246, 0.3);
        }

        .step-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--text-heading);
            margin: 0 0 8px;
        }

        .step-desc {
            font-size: 13.5px;
            color: var(--text-muted);
            line-height: 1.55;
            margin: 0;
        }

        /* Trust Cards */
        .trust-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .trust-card {
            background: var(--home-surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--home-border);
            border-radius: 20px;
            padding: 24px 20px;
            text-align: center;
            box-shadow: var(--shadow-premium);
            transition: var(--transition-smooth);
        }

        .trust-card:hover {
            transform: translateY(-4px);
            border-color: var(--home-border-hover);
        }

        .trust-icon {
            font-size: 26px;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .trust-card h4 {
            font-size: 15.5px;
            font-weight: 800;
            color: var(--text-heading);
            margin: 0 0 6px;
        }

        .trust-card p {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
            margin: 0;
        }

        /* ================= 5. REVIEWS & TESTIMONIALS ================= */
        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        .review-card {
            background: var(--home-surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--home-border);
            border-radius: 22px;
            padding: 28px 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 18px;
            box-shadow: var(--shadow-premium);
            transition: var(--transition-smooth);
        }

        .review-card:hover {
            transform: translateY(-4px);
            border-color: var(--home-border-hover);
            box-shadow: 0 16px 36px rgba(139, 92, 246, 0.12);
        }

        .review-stars {
            color: #f59e0b;
            font-size: 13px;
            display: flex;
            gap: 3px;
            margin-bottom: 10px;
        }

        .review-title {
            font-size: 17px;
            font-weight: 800;
            color: var(--text-heading);
            margin: 0 0 8px;
            line-height: 1.4;
        }

        .review-quote {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.65;
            margin: 0;
        }

        .review-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 1px solid rgba(139, 92, 246, 0.12);
            gap: 12px;
            flex-wrap: wrap;
        }

        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .reviewer-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #ffffff;
            font-weight: 800;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .reviewer-meta h4 {
            font-size: 14.5px;
            font-weight: 800;
            color: var(--text-heading);
            margin: 0 0 2px;
        }

        .reviewer-meta span {
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: #059669;
            font-weight: 700;
            font-size: 11px;
        }

        .review-event-pill {
            background: rgba(139, 92, 246, 0.08);
            color: var(--primary);
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11.5px;
            font-weight: 700;
            border: 1px solid rgba(139, 92, 246, 0.18);
        }

        /* ================= 6. CONCIERGE & CONTACT HUB ================= */
        .guarantee-chips {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 45px;
        }

        .guarantee-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid var(--home-border);
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-heading);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.05);
            transition: var(--transition-smooth);
        }

        .guarantee-chip i {
            color: var(--primary);
        }

        .guarantee-chip:hover {
            transform: translateY(-2px);
            border-color: var(--home-border-hover);
        }

        .contact-grid-wrap {
            display: grid;
            grid-template-columns: 1fr 1.35fr;
            gap: 36px;
            align-items: flex-start;
        }

        /* Concierge Info Directory */
        .concierge-info-panel {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .concierge-card {
            background: var(--home-surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--home-border);
            border-radius: 20px;
            padding: 22px 20px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            transition: var(--transition-smooth);
            box-shadow: var(--shadow-premium);
        }

        .concierge-card:hover {
            transform: translateY(-3px);
            border-color: var(--home-border-hover);
            box-shadow: 0 14px 28px rgba(139, 92, 246, 0.12);
        }

        .concierge-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: rgba(139, 92, 246, 0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            border: 1px solid rgba(139, 92, 246, 0.2);
            transition: var(--transition-smooth);
        }

        .concierge-card:hover .concierge-icon-wrap {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #ffffff;
            transform: scale(1.05);
        }

        .concierge-body {
            flex-grow: 1;
        }

        .concierge-label {
            font-size: 11px;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 3px;
            display: block;
        }

        .concierge-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--text-heading);
            margin: 0 0 3px;
        }

        .concierge-detail {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
            margin: 0 0 6px;
        }

        .concierge-action-link {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition-smooth);
        }

        .concierge-action-link:hover {
            color: var(--text-heading);
            transform: translateX(3px);
        }

        .social-channel-card {
            background: var(--home-surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--home-border);
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow-premium);
        }

        .social-channel-card h4 {
            font-size: 14.5px;
            font-weight: 800;
            color: var(--text-heading);
            margin: 0 0 6px;
        }

        .social-channel-card p {
            font-size: 13px;
            color: var(--text-muted);
            margin: 0 0 12px;
            line-height: 1.5;
        }

        .social-btns-wrap {
            display: flex;
            gap: 10px;
        }

        .social-btn {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: rgba(139, 92, 246, 0.08);
            border: 1px solid rgba(139, 92, 246, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 15px;
            transition: var(--transition-smooth);
        }

        .social-btn:hover {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* Inquiry Form Card */
        .inquiry-form-card {
            background: var(--home-surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--home-border);
            border-radius: 24px;
            padding: 36px 32px;
            box-shadow: 0 20px 45px -10px rgba(139, 92, 246, 0.14);
            position: relative;
        }

        .inquiry-header {
            margin-bottom: 22px;
        }

        .inquiry-header h2 {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-heading);
            margin: 0 0 6px;
            background: linear-gradient(135deg, var(--text-heading) 40%, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .inquiry-header p {
            font-size: 14px;
            color: var(--text-muted);
            margin: 0;
            line-height: 1.6;
        }

        .contact-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .full-width {
            grid-column: span 2;
        }

        .form-label {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-heading);
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: #ffffff;
            border: 1px solid rgba(139, 92, 246, 0.18);
            border-radius: 12px;
            color: var(--text-heading);
            font-size: 14px;
            font-family: var(--font-body);
            transition: var(--transition-smooth);
            outline: none;
            box-sizing: border-box;
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.12);
        }

        .form-input::placeholder {
            color: #94a3b8;
            font-size: 13.5px;
        }

        .submit-btn {
            width: 100%;
            padding: 14px 24px;
            font-size: 15px;
            font-weight: 700;
            border-radius: 12px;
            margin-top: 6px;
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.25);
        }

        .message {
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .message-error {
            background: rgba(220, 38, 38, 0.08);
            color: #dc2626;
            border: 1px solid rgba(220, 38, 38, 0.2);
        }

        .message-success {
            background: rgba(5, 150, 105, 0.08);
            color: #059669;
            border: 1px solid rgba(5, 150, 105, 0.2);
        }

        /* ================= 7. FAQ ACCORDION ================= */
        .faq-list {
            max-width: 860px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .faq-item {
            background: var(--home-surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--home-border);
            border-radius: 18px;
            overflow: hidden;
            transition: var(--transition-smooth);
            box-shadow: var(--shadow-premium);
        }

        .faq-item:hover {
            border-color: var(--home-border-hover);
        }

        .faq-question {
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-weight: 700;
            font-size: 16px;
            color: var(--text-heading);
            user-select: none;
        }

        .faq-answer {
            padding: 0 24px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s cubic-bezier(0, 1, 0, 1), padding 0.3s ease;
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.7;
        }

        .faq-item.active {
            border-color: rgba(139, 92, 246, 0.35);
            background: rgba(255, 255, 255, 0.95);
        }

        .faq-item.active .faq-answer {
            padding: 0 24px 22px;
            max-height: 250px;
            transition: max-height 0.35s cubic-bezier(1, 0, 1, 0), padding 0.3s ease;
        }

        .faq-icon {
            font-size: 14px;
            color: var(--text-muted);
            transition: var(--transition-smooth);
        }

        .faq-item.active .faq-icon {
            transform: rotate(180deg);
            color: var(--primary);
        }

        /* ================= 8. MODERN FOOTER ================= */
        .footer {
            background: #ffffff;
            border-top: 1px solid var(--home-border);
            padding: 70px 0 35px;
            margin-top: 80px;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 40px;
            padding: 0 24px;
            box-sizing: border-box;
        }

        .footer-brand h3 {
            font-size: 24px;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0 0 14px;
        }

        .footer-brand p {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.65;
            max-width: 320px;
            margin: 0;
        }

        .footer-links h4, .footer-social h4 {
            font-size: 15px;
            font-weight: 800;
            margin: 0 0 18px;
            color: var(--text-heading);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .footer-links ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: var(--text-muted);
            font-size: 14px;
            transition: var(--transition-smooth);
        }

        .footer-links a:hover {
            color: var(--primary);
            padding-left: 4px;
        }

        .footer-social-icons {
            display: flex;
            gap: 10px;
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 45px auto 0;
            padding: 25px 24px 0;
            border-top: 1px solid rgba(139, 92, 246, 0.1);
            text-align: center;
            font-size: 13px;
            color: var(--text-muted);
        }

        /* ================= RESPONSIVENESS (1200px and below) ================= */
        @media (max-width: 1200px) {
            .home-container {
                max-width: 100%;
                padding: 0 20px;
            }
            .hero {
                gap: 30px;
            }
            .hero h1 {
                font-size: 48px;
            }
            .alt-row, .alt-row.alt-reverse {
                gap: 35px;
            }
            .metrics-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 16px;
            }
            .pillars-grid {
                gap: 18px;
            }
            .steps-grid {
                gap: 16px;
            }
            .trust-grid {
                gap: 16px;
            }
        }

        @media (max-width: 992px) {
            .hero {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 35px;
            }
            .hero-text {
                text-align: center;
            }
            .hero-buttons {
                justify-content: center;
            }
            .social-proof {
                justify-content: center;
            }
            .hero-image-wrapper {
                max-width: 600px;
                margin: 0 auto;
            }
            .hero-image {
                height: 380px;
            }
            .features-grid {
                grid-template-columns: 1fr;
            }
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .pillars-grid {
                grid-template-columns: 1fr;
            }
            .steps-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .trust-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .reviews-grid {
                grid-template-columns: 1fr;
            }
            .contact-grid-wrap {
                grid-template-columns: 1fr;
            }
            .footer-container {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 38px;
            }
            .section-title {
                font-size: 30px;
            }
            .services-alternate {
                gap: 45px;
            }
            .alt-row, .alt-row.alt-reverse {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .alt-image {
                height: 250px;
                order: -1;
            }
            .alt-text {
                padding: 26px 20px;
            }
            .contact-form-grid {
                grid-template-columns: 1fr;
            }
            .full-width {
                grid-column: span 1;
            }
            .inquiry-form-card {
                padding: 28px 20px;
            }
        }

        @media (max-width: 480px) {
            .hero h1 {
                font-size: 32px;
            }
            .hero p.description {
                font-size: 15.5px;
            }
            .hero-image {
                height: 260px;
            }
            .hero-floating-card {
                left: 10px;
                bottom: -15px;
                padding: 10px 14px;
            }
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            .steps-grid {
                grid-template-columns: 1fr;
            }
            .trust-grid {
                grid-template-columns: 1fr;
            }
            .guarantee-chips {
                flex-direction: column;
                gap: 8px;
            }
            .guarantee-chip {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Ambient Glow Orbs -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="orb orb-4"></div>
    <div class="orb orb-5"></div>

    <div class="home-container">

        <!-- ================= 1. HERO SECTION ================= -->
        <section class="hero" id="hero">
            <div class="hero-text">
                <div class="hero-badge">
                    <i class="fas fa-star"></i> The #1 Premium Event Platform
                </div>
                <h1>Celebrate Life's Moments,<br><span>Logistics Simplified.</span></h1>
                <p class="description">
                    EVENTFLARE bridges the gap between your imagination and flawless execution. Coordinate venues, dynamic budgets, and accredited specialists effortlessly under one roof.
                </p>
                <div class="hero-buttons">
                    <a href="ChooseEvent.php" class="btn btn-primary" style="padding: 14px 28px; font-size: 15px;">
                        <i class="fas fa-sparkles"></i> Start Planning
                    </a>
                    <a href="#about" class="btn btn-secondary" style="padding: 14px 24px; font-size: 15px;">
                        <i class="fas fa-book-open"></i> Explore Our Story
                    </a>
                    <a href="#contact" class="btn btn-secondary" style="padding: 14px 22px; font-size: 15px;">
                        <i class="fas fa-headset"></i> Contact Concierge
                    </a>
                </div>
                
                <div class="social-proof">
                    <div class="avatar-group">
                        <img src="https://i.pravatar.cc/100?img=1" alt="User">
                        <img src="https://i.pravatar.cc/100?img=2" alt="User">
                        <img src="https://i.pravatar.cc/100?img=3" alt="User">
                        <img src="https://i.pravatar.cc/100?img=4" alt="User">
                    </div>
                    <p>Loved by <span>5,000+</span><br>happy celebration hosts</p>
                </div>
            </div>
            
            <div class="hero-image-wrapper">
                <img src="assets/images/event_planning_hero.png" alt="Premium Event Planning Platform" class="hero-image">
                <div class="hero-floating-card">
                    <div class="icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="text">
                        <h4>Booking Confirmed</h4>
                        <p>Just now in Colombo</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================= 2. SERVICES & EVENT UNIVERSES ================= -->
        <section class="page-section" id="services">
            <div class="section-header">
                <span class="section-tag"><i class="fas fa-compass"></i> Select Your Event Style</span>
                <h2 class="section-title">Tailored Visual Architectures & Dedicated Portfolios</h2>
                <p class="section-subtitle">We craft bespoke planning environments for Sri Lanka's finest milestones and celebrations.</p>
            </div>
            
            <div class="services-alternate">
                <!-- Row 1: Hotels & Venues -->
                <div class="alt-row">
                    <div class="alt-text">
                        <h3>Hotels & Venues</h3>
                        <p>Discover luxurious hotels, elegant ballrooms, and oceanfront open-air venues. Our accredited hospitality partners offer premium hosting facilities with full banquet amenities.</p>
                        <a href="events/HotelSlide.php" class="btn btn-primary"><i class="fas fa-hotel"></i> Explore Venues</a>
                    </div>
                    <div class="alt-image" style="background-image: url(assets/images/Hotel.jpg);"></div>
                </div>

                <!-- Row 2: Weddings -->
                <div class="alt-row alt-reverse">
                    <div class="alt-image" style="background-image: url(assets/images/wedding.jpg);"></div>
                    <div class="alt-text">
                        <h3>Weddings</h3>
                        <p>Celebrate your love story in a choreographed fairytale. From traditional Poruwa masters to floral backdrops, drone cinematographers, and choir ensembles, we coordinate every detail seamlessly.</p>
                        <a href="events/WeddingsSlids.php" class="btn btn-primary"><i class="fas fa-heart"></i> Plan Wedding & Past Events</a>
                    </div>
                </div>

                <!-- Row 3: DJ & Parties -->
                <div class="alt-row">
                    <div class="alt-text">
                        <h3>DJ & Parties</h3>
                        <p>Bring festival-grade energy to your party with top-tier DJs, concert line-array sound systems, and moving laser rigs. Perfect for club raves, rooftop sundowners, or private VIP celebrations.</p>
                        <a href="events/DjPartySlide.php" class="btn btn-primary"><i class="fas fa-bolt"></i> Plan Party & Past Events</a>
                    </div>
                    <div class="alt-image" style="background-image: url(assets/images/happy-men-women-throwing-confetti.jpg);"></div>
                </div>

                <!-- Row 4: Birthdays -->
                <div class="alt-row alt-reverse">
                    <div class="alt-image" style="background-image: url(assets/images/birth.jpg);"></div>
                    <div class="alt-text">
                        <h3>Birthdays</h3>
                        <p>Create unforgettable birthday memories for toddlers, sweet sixteens, and golden jubilees. We provide customized pastel balloon arches, 3D theme cakes, live magicians, and dessert stations.</p>
                        <a href="events/BirthdayList.php" class="btn btn-primary"><i class="fas fa-birthday-cake"></i> Plan Birthday & Past Events</a>
                    </div>
                </div>

                <!-- Row 5: Get Togethers -->
                <div class="alt-row">
                    <div class="alt-text">
                        <h3>Get Togethers</h3>
                        <p>Reconnect with old classmates, family circles, or alumni batches. Our spacious lawns, live charcoal BBQ skewer grills, weatherproof marquee tents, and acoustic sing-alongs set the perfect vibe.</p>
                        <a href="events/GetTogether.php" class="btn btn-primary"><i class="fas fa-handshake"></i> Plan Reunion & Past Events</a>
                    </div>
                    <div class="alt-image" style="background-image: url(assets/images/get.jpg);"></div>
                </div>
            </div>
        </section>

        <!-- ================= 3. PLATFORM INNOVATIONS & FEATURES ================= -->
        <section class="page-section" id="features">
            <div class="section-header">
                <span class="section-tag"><i class="fas fa-microchip"></i> Platform Innovations</span>
                <h2 class="section-title">Engineered For <span>Perfection</span></h2>
                <p class="section-subtitle">Enjoy robust digital modules designed to coordinate celebration logistics with transparency and speed.</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-magic"></i>
                    </div>
                    <h3>Smart Event Planner</h3>
                    <p>Register custom packages, select required vendor capabilities, and synchronize multiple suppliers under a single unified dashboard.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <h3>Real-Time Calculator</h3>
                    <p>Estimate buffet, dessert, and beverage expenses instantly, manage budgets per guest, and preserve calculations directly to your profile.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Secure Booking System</h3>
                    <p>Generate encrypted Booking IDs, track status workflows from pending to confirmed, and filter schedules with total database confidentiality.</p>
                </div>
            </div>
        </section>

        <!-- ================= 4. ABOUT US, MISSION & TRUST FOUNDATION ================= -->
        <section class="page-section" id="about">
            <div class="section-header">
                <span class="section-tag"><i class="fas fa-sparkles"></i> The Story Behind EVENTFLARE</span>
                <h2 class="section-title">Redefining How <span>Celebrations</span> Come to Life</h2>
                <p class="section-subtitle">
                    EVENTFLARE was founded to eliminate the stress, chaotic phone calls, and hidden fees that traditionally plague event planning. We unite visionary clients with accredited Sri Lankan specialists through transparent pricing and synchronized execution.
                </p>
            </div>

            <!-- Trust Metrics Strip -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-val">5,000<span>+</span></div>
                    <div class="metric-lbl">Celebrations Coordinated</div>
                </div>
                <div class="metric-card">
                    <div class="metric-val">150<span>+</span></div>
                    <div class="metric-lbl">Accredited Specialists</div>
                </div>
                <div class="metric-card">
                    <div class="metric-val">98.4<span>%</span></div>
                    <div class="metric-lbl">Client Satisfaction</div>
                </div>
                <div class="metric-card">
                    <div class="metric-val">100<span>%</span></div>
                    <div class="metric-lbl">Upfront Price Transparency</div>
                </div>
            </div>

            <!-- Core Foundation Pillars -->
            <div class="pillars-grid">
                <!-- Mission -->
                <div class="pillar-card">
                    <div class="pillar-icon-wrap">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3 class="pillar-title">Our Mission</h3>
                    <p class="pillar-desc">
                        To empower individuals, couples, and organizations by providing a unified digital platform that simplifies event curation, guarantees transparent upfront pricing, and ensures synchronized vendor coordination for every milestone.
                    </p>
                </div>

                <!-- Vision -->
                <div class="pillar-card">
                    <div class="pillar-icon-wrap">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="pillar-title">Our Vision</h3>
                    <p class="pillar-desc">
                        To become the undisputed benchmark in celebration technology across South Asia and beyond, creating a world where technology eliminates friction, elevates creativity, and turns every event into an extraordinary memory.
                    </p>
                </div>

                <!-- The Guarantee -->
                <div class="pillar-card">
                    <div class="pillar-icon-wrap">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <h3 class="pillar-title">The EVENTFLARE Guarantee</h3>
                    <p class="pillar-desc">
                        Every listed partner is rigorously accredited. What you see in our budget calculator is the exact price you pay—zero surprise vendor surcharges, zero no-shows, and dedicated concierge oversight on your event day.
                    </p>
                </div>
            </div>

            <!-- How It Works (4-Step Flow) -->
            <div class="section-header" style="margin-bottom: 25px;">
                <span class="section-tag"><i class="fas fa-layer-group"></i> How It Works</span>
                <h2 class="section-title" style="font-size: 32px;">Four Seamless Steps to Perfection</h2>
                <p class="section-subtitle">Experience the simplicity of our multi-supplier event orchestration.</p>
            </div>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-badge">1</div>
                    <h4 class="step-title">Choose Your Universe</h4>
                    <p class="step-desc">Select between Weddings, DJ Parties, Birthdays, or Get Togethers to inspect tailored portfolios.</p>
                </div>

                <div class="step-card">
                    <div class="step-badge">2</div>
                    <h4 class="step-title">Curate Your Specialists</h4>
                    <p class="step-desc">Pick certified stage decorators, Poruwa masters, BBQ grillers, line-array audio, and photographers.</p>
                </div>

                <div class="step-card">
                    <div class="step-badge">3</div>
                    <h4 class="step-title">Live Budget Simulation</h4>
                    <p class="step-desc">Our real-time calculator updates total costs per guest or per event without requiring hidden estimates.</p>
                </div>

                <div class="step-card">
                    <div class="step-badge">4</div>
                    <h4 class="step-title">Synchronized Execution</h4>
                    <p class="step-desc">On celebration day, all vendors arrive briefed, synchronized, and managed under a single coordinated schedule.</p>
                </div>
            </div>

            <!-- Core Trust Advantages -->
            <div class="trust-grid">
                <div class="trust-card">
                    <div class="trust-icon"><i class="fas fa-certificate"></i></div>
                    <h4>100% Vetted Partners</h4>
                    <p>Every specialist passes rigorous quality, portfolio, and reliability audits before joining.</p>
                </div>

                <div class="trust-card">
                    <div class="trust-icon"><i class="fas fa-scale-balanced"></i></div>
                    <h4>Zero Hidden Markups</h4>
                    <p>Transparent pricing direct from verified partners. No secret agency commissions.</p>
                </div>

                <div class="trust-card">
                    <div class="trust-icon"><i class="fas fa-calculator"></i></div>
                    <h4>Live Budget Calculator</h4>
                    <p>Know your exact cost per person and total budget dynamically before booking.</p>
                </div>

                <div class="trust-card">
                    <div class="trust-icon"><i class="fas fa-headset"></i></div>
                    <h4>Dedicated Concierge</h4>
                    <p>Our team works behind the scenes to verify timelines and vendor handoffs.</p>
                </div>
            </div>
        </section>

        <!-- ================= 5. CLIENT FEEDBACK & VERIFIED REVIEWS ================= -->
        <section class="page-section" id="reviews">
            <div class="section-header">
                <span class="section-tag"><i class="fas fa-comments"></i> Verified Client Feedback</span>
                <h2 class="section-title">What Celebration <span>Hosts Say</span></h2>
                <p class="section-subtitle">Read genuine feedback from couples, batch coordinators, and event hosts who trusted our multi-supplier platform.</p>
            </div>

            <div class="reviews-grid">
                <?php foreach (array_slice($display_reviews, 0, 4) as $rev): ?>
                    <div class="review-card">
                        <div>
                            <div class="review-stars">
                                <?php for ($i = 0; $i < $rev['rating']; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <h3 class="review-title">"<?= htmlspecialchars($rev['title']) ?>"</h3>
                            <p class="review-quote">"<?= htmlspecialchars($rev['comment']) ?>"</p>
                        </div>

                        <div class="review-footer">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <?= htmlspecialchars($rev['avatar'] ?? substr($rev['name'], 0, 2)) ?>
                                </div>
                                <div class="reviewer-meta">
                                    <h4><?= htmlspecialchars($rev['name']) ?></h4>
                                    <span>
                                        <?= htmlspecialchars($rev['role']) ?> &bull; <?= htmlspecialchars($rev['date']) ?>
                                        <span class="verified-badge"><i class="fas fa-check-circle"></i> Verified</span>
                                    </span>
                                </div>
                            </div>
                            <span class="review-event-pill">
                                <?= htmlspecialchars($rev['event_badge']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ================= 6. CONCIERGE & CONTACT HUB ================= -->
        <section class="page-section" id="contact">
            <div class="section-header">
                <span class="section-tag"><i class="fas fa-headset"></i> Dedicated Event Concierge</span>
                <h2 class="section-title">Let's Connect & Build Your <span>Celebration</span></h2>
                <p class="section-subtitle">Have questions about our vetted partners, budget calculator, or custom packages? Our Colombo concierge desk is ready to assist you.</p>
            </div>

            <!-- Quick Service Guarantees -->
            <div class="guarantee-chips">
                <div class="guarantee-chip">
                    <i class="fas fa-bolt"></i> Guaranteed 2-Hour Response Time
                </div>
                <div class="guarantee-chip">
                    <i class="fas fa-shield-halved"></i> 100% Privacy & Data Protected
                </div>
                <div class="guarantee-chip">
                    <i class="fas fa-calculator"></i> Free Budget Consultation
                </div>
            </div>

            <!-- Main 2-Column Contact Split -->
            <div class="contact-grid-wrap">

                <!-- Left Column: Multi-Channel Concierge Directory -->
                <div class="concierge-info-panel">

                    <!-- Direct Hotline -->
                    <div class="concierge-card">
                        <div class="concierge-icon-wrap">
                            <i class="fas fa-phone-volume"></i>
                        </div>
                        <div class="concierge-body">
                            <span class="concierge-label">Direct Concierge Hotline</span>
                            <h3 class="concierge-title">+94 11 234 5678</h3>
                            <p class="concierge-detail">Mon &ndash; Sat, 8:30 AM &ndash; 7:00 PM (IST)</p>
                            <a href="tel:+94112345678" class="concierge-action-link">
                                Call Priority Line <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Instant WhatsApp Desk -->
                    <div class="concierge-card">
                        <div class="concierge-icon-wrap" style="color:#10b981; background:rgba(16, 185, 129, 0.1); border-color:rgba(16, 185, 129, 0.2);">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div class="concierge-body">
                            <span class="concierge-label" style="color:#059669;">Instant WhatsApp Desk</span>
                            <h3 class="concierge-title">+94 77 123 4567</h3>
                            <p class="concierge-detail">Quick answers on supplier availability & packages</p>
                            <a href="https://wa.me/94771234567" target="_blank" rel="noopener noreferrer" class="concierge-action-link" style="color:#059669;">
                                Chat on WhatsApp <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Official Inquiries & RFP -->
                    <div class="concierge-card">
                        <div class="concierge-icon-wrap">
                            <i class="fas fa-envelope-open-text"></i>
                        </div>
                        <div class="concierge-body">
                            <span class="concierge-label">Official Inquiries & RFP</span>
                            <h3 class="concierge-title">support@eventflare.com</h3>
                            <p class="concierge-detail">Detailed celebration briefs and formal proposals</p>
                            <a href="mailto:support@eventflare.com" class="concierge-action-link">
                                Send Formal Email <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Coordination Studio & HQ -->
                    <div class="concierge-card">
                        <div class="concierge-icon-wrap">
                            <i class="fas fa-location-dot"></i>
                        </div>
                        <div class="concierge-body">
                            <span class="concierge-label">Coordination Studio & HQ</span>
                            <h3 class="concierge-title">12 Galle Road, Colombo 03</h3>
                            <p class="concierge-detail">Western Province, Sri Lanka &bull; By Appointment</p>
                            <span class="concierge-detail" style="font-size: 12.5px; color: var(--primary); font-weight: 600;">
                                <i class="fas fa-building"></i> Client Lounge & Vendor Briefing Studio
                            </span>
                        </div>
                    </div>

                    <!-- Social Channels -->
                    <div class="social-channel-card">
                        <h4>Follow Celebration Trends</h4>
                        <p>Get daily inspiration from real weddings, party lighting rigs, and floral themes.</p>
                        <div class="social-btns-wrap">
                            <a href="#" class="social-btn" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-btn" title="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-btn" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#" class="social-btn" title="YouTube"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>

                </div>

                <!-- Right Column: Priority Inquiry Form -->
                <div class="inquiry-form-card">
                    <div class="inquiry-header">
                        <h2>Send a Priority Message</h2>
                        <p>Share your event requirements below. A dedicated coordinator will review your parameters and follow up promptly.</p>
                    </div>

                    <?php if (!empty($contact_error)): ?>
                        <div class="message message-error">
                            <i class="fas fa-circle-exclamation"></i>
                            <span><?= $contact_error; ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($contact_success)): ?>
                        <div class="message message-success">
                            <i class="fas fa-circle-check"></i>
                            <span><?= $contact_success; ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="Home.php#contact" method="POST">
                        <div class="contact-form-grid">
                            <div class="form-group">
                                <label class="form-label" for="firstname">First Name *</label>
                                <input type="text" id="firstname" class="form-input" name="firstname" placeholder="e.g. Kasun" value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="lastname">Last Name *</label>
                                <input type="text" id="lastname" class="form-input" name="lastname" placeholder="e.g. Perera" value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email">Email Address *</label>
                                <input type="email" id="email" class="form-input" name="email" placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="phone">Phone Number *</label>
                                <input type="text" id="phone" class="form-input" name="phone" placeholder="077 123 4567" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                            </div>

                            <div class="form-group full-width">
                                <label class="form-label" for="message">Celebration Details / Inquiry *</label>
                                <textarea id="message" class="form-input" style="height: 130px; resize: vertical;" name="message" placeholder="Please describe your event type (Wedding, DJ Party, Birthday, Reunion), estimated guest count, expected date, or specific specialist needs..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            </div>

                            <div class="full-width">
                                <button type="submit" name="send_contact" class="btn btn-primary submit-btn">
                                    <i class="fas fa-paper-plane"></i> Send Concierge Inquiry
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </section>

        <!-- ================= 7. FAQ ACCORDION ================= -->
        <section class="page-section" id="faq">
            <div class="section-header">
                <span class="section-tag"><i class="fas fa-circle-question"></i> Help & Guidance</span>
                <h2 class="section-title">Frequently Asked Questions</h2>
                <p class="section-subtitle">Get immediate answers to common coordinator and celebration host questions.</p>
            </div>

            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question">
                        <span>How do I secure an event booking?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        Simply navigate to the Bookings tab, register or log in, fill in your details (expected guest counts, dates, tier lists), and click Book. The system instantly generates a secure Booking ID with transparent records.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Can I calculate catering budgets before committing?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        Yes! You can use our integrated Food Budget Calculator to enter Buffet, Snack, and Drink prices. Compare totals against your allocations and save calculations directly to your profile.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>How can I modify my booking parameters?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        Navigate to the Addons page under Book Event. Enter your secure Booking ID to dynamically update venue places, guest quantities, and select AV equipment setups.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>How are suppliers verified and vetted?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        Every specialist on EVENTFLARE undergoes a strict review of past client portfolios, legal verification, equipment standards, and reliability audits before their listings become active.
                    </div>
                </div>
            </div>
        </section>

    </div>

    <!-- ================= 8. MODERN FOOTER ================= -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-brand">
                <h3>EVENTFLARE</h3>
                <p>Providing premium digital planning utilities. We simplify celebrating so you can focus on the memories.</p>
            </div>
            
            <div class="footer-links">
                <h4>Navigation</h4>
                <ul>
                    <li><a href="#hero">Home</a></li>
                    <li><a href="#services">Event Styles</a></li>
                    <li><a href="#about">About Our Story</a></li>
                    <li><a href="#reviews">Client Reviews</a></li>
                    <li><a href="#contact">Contact Concierge</a></li>
                    <li><a href="ChooseEvent.php">Book Event</a></li>
                    <li><a href="admin/Admin.php" style="color: var(--primary); font-weight: 700;">Admin Login</a></li>
                </ul>
            </div>

            <div class="footer-social">
                <h4>Connect With Us</h4>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">Follow us on social networks for party concepts and venue guides.</p>
                <div class="footer-social-icons">
                    <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            &copy; <?php echo date("Y"); ?> EVENTFLARE. All rights reserved. Designed for unforgettable celebrations.
        </div>
    </footer>

    <!-- Interactive Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // FAQ Accordion Toggle
            const faqItems = document.querySelectorAll('.faq-item');
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                question.addEventListener('click', () => {
                    const isActive = item.classList.contains('active');
                    faqItems.forEach(innerItem => innerItem.classList.remove('active'));
                    if (!isActive) {
                        item.classList.add('active');
                    }
                });
            });

            // Smooth scrolling for in-page anchors
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    const targetId = this.getAttribute('href').substring(1);
                    const targetElem = document.getElementById(targetId);
                    if (targetElem) {
                        e.preventDefault();
                        targetElem.scrollIntoView({ behavior: 'smooth' });
                        if (history.pushState) {
                            history.pushState(null, null, '#' + targetId);
                        }
                    }
                });
            });

            // Auto-scroll to contact form if error or success message is displayed
            <?php if (!empty($contact_success) || !empty($contact_error)): ?>
                const contactSection = document.getElementById('contact');
                if (contactSection) {
                    setTimeout(() => {
                        contactSection.scrollIntoView({ behavior: 'smooth' });
                    }, 100);
                }
            <?php endif; ?>
        });
    </script>
    <?php if (isset($conn)) { $conn->close(); } ?>
</body>
</html>
