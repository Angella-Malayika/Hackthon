<?php
/**
 * Self-healing database schema setup.
 *
 * The original hackthon.sql dump is missing a few columns/tables that
 * different pages in this app rely on (feedback replies, badge
 * requirements, unique keys for progress tracking). Rather than forcing a
 * manual migration, this file checks for what's needed and adds it if
 * missing. Everything here is idempotent - safe to run on every request.
 */

function column_exists(mysqli $conn, string $table, string $column): bool
{
    $sql = "SELECT COUNT(*) AS c FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return true; // fail safe: don't attempt to alter if we can't check
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['c'] ?? 0;
    return $count > 0;
}

function table_exists(mysqli $conn, string $table): bool
{
    $sql = "SELECT COUNT(*) AS c FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return true;
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['c'] ?? 0;
    return $count > 0;
}

function index_exists(mysqli $conn, string $table, string $indexName): bool
{
    $sql = "SELECT COUNT(*) AS c FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return true;
    $stmt->bind_param("ss", $table, $indexName);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['c'] ?? 0;
    return $count > 0;
}

function ensure_schema(mysqli $conn): void
{
    // ---------------------------------------------------------------
    // 1. feedback table - reply workflow columns used by admin/feedback.php
    // ---------------------------------------------------------------
    if (table_exists($conn, 'feedback')) {
        if (!column_exists($conn, 'feedback', 'status')) {
            $conn->query("ALTER TABLE feedback ADD COLUMN status ENUM('unread','read','replied') DEFAULT 'unread' AFTER message");
        }
        if (!column_exists($conn, 'feedback', 'reply')) {
            $conn->query("ALTER TABLE feedback ADD COLUMN reply TEXT NULL AFTER status");
        }
        if (!column_exists($conn, 'feedback', 'replied_at')) {
            $conn->query("ALTER TABLE feedback ADD COLUMN replied_at DATETIME NULL AFTER reply");
        }
        if (!column_exists($conn, 'feedback', 'replied_by')) {
            $conn->query("ALTER TABLE feedback ADD COLUMN replied_by INT NULL AFTER replied_at");
        }
        if (!column_exists($conn, 'feedback', 'subject')) {
            $conn->query("ALTER TABLE feedback ADD COLUMN subject VARCHAR(150) NULL AFTER user_id");
        }
    }

    // ---------------------------------------------------------------
    // 1b. quests table - status column (also self-healed by admin/quests.php,
    //     but user/quests.php needs it too, so ensure it here as well)
    // ---------------------------------------------------------------
    if (table_exists($conn, 'quests') && !column_exists($conn, 'quests', 'status')) {
        $conn->query("ALTER TABLE quests ADD COLUMN status ENUM('active','inactive') DEFAULT 'active'");
    }

    if (table_exists($conn, 'quests')) {
        $questCount = $conn->query("SELECT COUNT(*) AS c FROM quests")->fetch_assoc()['c'] ?? 0;
        if ($questCount == 0) {
            $starterQuests = [
                ['Complete Your Profile', 'Fill in your profile details so admins and peers can recognise you.', 30],
                ['Finish Your First Level', 'Complete Level 1 of the learning path with a passing score.', 50],
                ['Share Your Feedback', 'Submit feedback about the platform to help us improve it.', 20],
            ];
            $stmt = $conn->prepare("INSERT INTO quests (title, description, xp_reward, status) VALUES (?, ?, ?, 'active')");
            foreach ($starterQuests as [$title, $desc, $xp]) {
                $stmt->bind_param("ssi", $title, $desc, $xp);
                $stmt->execute();
            }
        }
    }

    // ---------------------------------------------------------------
    // 2. badges table - requirement column used by admin/badges.php
    // ---------------------------------------------------------------
    if (table_exists($conn, 'badges') && !column_exists($conn, 'badges', 'requirement')) {
        $conn->query("ALTER TABLE badges ADD COLUMN requirement VARCHAR(255) NULL AFTER description");
    }

    // ---------------------------------------------------------------
    // 3. Unique keys needed for INSERT ... ON DUPLICATE KEY UPDATE
    // ---------------------------------------------------------------
    if (table_exists($conn, 'lesson_progress') && !index_exists($conn, 'lesson_progress', 'uniq_user_lesson')) {
        // Clean up any accidental duplicates first so the unique key can be created
        $conn->query("DELETE lp1 FROM lesson_progress lp1
                       INNER JOIN lesson_progress lp2
                       WHERE lp1.id > lp2.id
                       AND lp1.user_id <=> lp2.user_id
                       AND lp1.lesson_id <=> lp2.lesson_id");
        $conn->query("ALTER TABLE lesson_progress ADD UNIQUE KEY uniq_user_lesson (user_id, lesson_id)");
    }

    if (table_exists($conn, 'user_quests') && !index_exists($conn, 'user_quests', 'uniq_user_quest')) {
        $conn->query("DELETE uq1 FROM user_quests uq1
                       INNER JOIN user_quests uq2
                       WHERE uq1.id > uq2.id
                       AND uq1.user_id <=> uq2.user_id
                       AND uq1.quest_id <=> uq2.quest_id");
        $conn->query("ALTER TABLE user_quests ADD UNIQUE KEY uniq_user_quest (user_id, quest_id)");
    }

    if (table_exists($conn, 'user_badges') && !index_exists($conn, 'user_badges', 'uniq_user_badge')) {
        $conn->query("DELETE ub1 FROM user_badges ub1
                       INNER JOIN user_badges ub2
                       WHERE ub1.id > ub2.id
                       AND ub1.user_id <=> ub2.user_id
                       AND ub1.badge_id <=> ub2.badge_id");
        $conn->query("ALTER TABLE user_badges ADD UNIQUE KEY uniq_user_badge (user_id, badge_id)");
    }

    // ---------------------------------------------------------------
    // 4. Seed a "Learning Path" category + 8 level lessons so learn.php /
    //    the level pages can log progress into lesson_progress, and the
    //    dashboard / admin reports can read it back consistently.
    // ---------------------------------------------------------------
    if (table_exists($conn, 'categories') && table_exists($conn, 'lessons')) {
        $catRes = $conn->query("SELECT id FROM categories WHERE name = 'Learning Path' LIMIT 1");
        if ($catRes && $catRes->num_rows > 0) {
            $categoryId = $catRes->fetch_assoc()['id'];
        } else {
            $conn->query("INSERT INTO categories (name, description) VALUES
                ('Learning Path', 'The 8 core levels of the Internet Governance & Awareness course')");
            $categoryId = $conn->insert_id;
        }

        $levelTitles = [
            1 => ['Level 1: Internet Foundations', 'Understand the Internet, its governance, and how to use it responsibly.'],
            2 => ['Level 2: Digital Citizenship', 'Learn how to behave safely, respectfully, and responsibly online.'],
            3 => ['Level 3: Online Privacy & Data Protection', 'Learn how personal data is collected, used, and protected online.'],
            4 => ['Level 4: Cybersecurity Basics', 'Learn how to recognise threats and keep your accounts and devices safe.'],
            5 => ['Level 5: Social Media Safety & Digital Footprint', 'Learn how to manage your online presence and reputation.'],
            6 => ['Level 6: Misinformation & Digital Literacy', 'Learn how to spot fake news and evaluate information critically.'],
            7 => ['Level 7: Internet Governance Structures', 'Explore the organisations and policies that keep the Internet running.'],
            8 => ['Level 8: Digital Rights, Ethics & the Future', 'Explore digital rights, ethics, and where the Internet is heading.'],
        ];

        $existing = [];
        $res = $conn->query("SELECT id FROM lessons WHERE category_id = " . intval($categoryId));
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $existing[(int)$row['id']] = true;
            }
        }

        foreach ($levelTitles as $level => [$title, $desc]) {
            $check = $conn->prepare("SELECT id FROM lessons WHERE title = ? LIMIT 1");
            $check->bind_param("s", $title);
            $check->execute();
            $found = $check->get_result()->fetch_assoc();
            if (!$found) {
                $stmt = $conn->prepare("INSERT INTO lessons (category_id, title, description, status) VALUES (?, ?, ?, 'published')");
                $stmt->bind_param("iss", $categoryId, $title, $desc);
                $stmt->execute();
            }
        }
    }

    // ---------------------------------------------------------------
    // 5. Seed badge rows 1-8 (one per level) so user_badges FK inserts
    //    made by level1.php ... level8.php always succeed.
    // ---------------------------------------------------------------
    if (table_exists($conn, 'badges')) {
        $badgeDefs = [
            1 => ['Foundations Star', '🌐', 'Score 90%+ on Level 1'],
            2 => ['Digital Citizen', '🧑‍💻', 'Score 90%+ on Level 2'],
            3 => ['Privacy Guardian', '🔒', 'Score 90%+ on Level 3'],
            4 => ['Cyber Defender', '🛡️', 'Score 90%+ on Level 4'],
            5 => ['Social Savvy', '📱', 'Score 90%+ on Level 5'],
            6 => ['Fact Checker', '🔍', 'Score 90%+ on Level 6'],
            7 => ['Governance Guru', '🏛️', 'Score 90%+ on Level 7'],
            8 => ['Digital Rights Champion', '⚖️', 'Score 90%+ on Level 8'],
        ];
        $maxId = $conn->query("SELECT MAX(id) AS m FROM badges")->fetch_assoc()['m'] ?? 0;
        foreach ($badgeDefs as $id => [$name, $icon, $req]) {
            if ($id > $maxId) {
                // insert fresh, letting auto increment assign matching ids only if table is empty enough
            }
            $check = $conn->prepare("SELECT id FROM badges WHERE id = ?");
            $check->bind_param("i", $id);
            $check->execute();
            $found = $check->get_result()->fetch_assoc();
            if (!$found) {
                $stmt = $conn->prepare("INSERT INTO badges (id, name, description, image, requirement) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $id, $name, $req, $icon, $req);
                $stmt->execute();
            }
        }
    }

    // ---------------------------------------------------------------
    // 6. Seed a default admin account if no admin exists yet
    // ---------------------------------------------------------------
    if (table_exists($conn, 'users')) {
        $adminCheck = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        if ($adminCheck && $adminCheck->num_rows === 0) {
            $defaultEmail = 'admin@platform.local';
            $defaultPassword = 'Admin@12345';
            $hashed = password_hash($defaultPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, status) VALUES ('Platform Admin', ?, ?, 'admin', 'active')");
            $stmt->bind_param("ss", $defaultEmail, $hashed);
            $stmt->execute();
        }
    }
}
