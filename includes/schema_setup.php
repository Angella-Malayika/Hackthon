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

/**
 * Ensures every column in $columns exists on $table, adding any that are
 * missing. Handles the case where a table was already created previously
 * (by an older version of this code, or a partial/failed run) with a
 * different structure than what the current code expects.
 *
 * $columns: [ 'column_name' => 'SQL type/definition', ... ]
 */
function ensure_columns(mysqli $conn, string $table, array $columns): void
{
    if (!table_exists($conn, $table)) {
        return;
    }
    foreach ($columns as $column => $definition) {
        if (!column_exists($conn, $table, $column)) {
            $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    }
}

function ensure_schema(mysqli $conn): void
{
    // ---------------------------------------------------------------
    // 0. users table - columns used by profile.php / achievements.php.
    //    Older installs of this project may predate these columns.
    // ---------------------------------------------------------------
    if (table_exists($conn, 'users')) {
        if (!column_exists($conn, 'users', 'experience')) {
            $conn->query("ALTER TABLE users ADD COLUMN experience TEXT NULL");
        }
        if (!column_exists($conn, 'users', 'profile_picture')) {
            $conn->query("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT 'default.png'");
        }
        if (!column_exists($conn, 'users', 'program')) {
            $conn->query("ALTER TABLE users ADD COLUMN program ENUM('Week','Weekend') NOT NULL DEFAULT 'Week'");
        }
        if (!column_exists($conn, 'users', 'date_joined')) {
            $conn->query("ALTER TABLE users ADD COLUMN date_joined TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
        }
        if (!column_exists($conn, 'users', 'xp')) {
            $conn->query("ALTER TABLE users ADD COLUMN xp INT DEFAULT 0");
        }
        if (!column_exists($conn, 'users', 'level')) {
            $conn->query("ALTER TABLE users ADD COLUMN level INT DEFAULT 1");
        }
    }

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
            1 => ['Foundations Star', '🌐', 'Score 70%+ on Level 1'],
            2 => ['Digital Citizen', '🧑‍💻', 'Score 70%+ on Level 2'],
            3 => ['Privacy Guardian', '🔒', 'Score 70%+ on Level 3'],
            4 => ['Cyber Defender', '🛡️', 'Score 70%+ on Level 4'],
            5 => ['Social Savvy', '📱', 'Score 70%+ on Level 5'],
            6 => ['Fact Checker', '🔍', 'Score 70%+ on Level 6'],
            7 => ['Governance Guru', '🏛️', 'Score 70%+ on Level 7'],
            8 => ['Digital Rights Champion', '⚖️', 'Score 75%+ on Level 8'],
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

    // ---------------------------------------------------------------
    // 7. lesson_progress.score - stores the % scored on a level's
    //    assessment, used for "perfect score" achievements and reporting.
    // ---------------------------------------------------------------
    if (table_exists($conn, 'lesson_progress') && !column_exists($conn, 'lesson_progress', 'score')) {
        $conn->query("ALTER TABLE lesson_progress ADD COLUMN score INT NULL AFTER status");
    }

    // ---------------------------------------------------------------
    // 8. quest_progress - tracks the 8-level Quest challenge (deep-dive
    //    content + 10 objectives + scenario) shown on user/quests.php.
    //    Separate from the legacy `quests`/`user_quests` bonus-quest system.
    // ---------------------------------------------------------------
    if (!table_exists($conn, 'quest_progress')) {
        $conn->query("
            CREATE TABLE quest_progress (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                quest_level INT NOT NULL,
                status ENUM('in_progress','completed') DEFAULT 'in_progress',
                score INT NULL,
                completed_at DATETIME NULL,
                UNIQUE KEY uniq_user_questlevel (user_id, quest_level),
                KEY user_id (user_id),
                CONSTRAINT quest_progress_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }
    ensure_columns($conn, 'quest_progress', [
        'quest_level'   => "INT NOT NULL DEFAULT 1",
        'status'        => "ENUM('in_progress','completed') DEFAULT 'in_progress'",
        'score'         => "INT NULL",
        'completed_at'  => "DATETIME NULL",
    ]);

    // ---------------------------------------------------------------
    // 9. Achievements system - catalog table, per-user unlock tracking,
    //    and the rolling 24h daily-challenge assignment table.
    // ---------------------------------------------------------------
    if (!table_exists($conn, 'achievements')) {
        $conn->query("
            CREATE TABLE achievements (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(150) NOT NULL,
                description VARCHAR(255) NOT NULL,
                icon VARCHAR(10) DEFAULT '🏆',
                criteria_type VARCHAR(50) NOT NULL,
                criteria_value INT NOT NULL DEFAULT 1,
                difficulty ENUM('easy','medium','hard','epic') DEFAULT 'easy',
                xp_reward INT NOT NULL DEFAULT 15,
                is_daily_eligible TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }
    ensure_columns($conn, 'achievements', [
        'title'             => "VARCHAR(150) NOT NULL DEFAULT ''",
        'description'       => "VARCHAR(255) NOT NULL DEFAULT ''",
        'icon'              => "VARCHAR(10) DEFAULT '🏆'",
        'criteria_type'     => "VARCHAR(50) NOT NULL DEFAULT ''",
        'criteria_value'    => "INT NOT NULL DEFAULT 1",
        'difficulty'        => "ENUM('easy','medium','hard','epic') DEFAULT 'easy'",
        'xp_reward'         => "INT NOT NULL DEFAULT 15",
        'is_daily_eligible' => "TINYINT(1) DEFAULT 1",
        'created_at'        => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
    ]);

    if (!table_exists($conn, 'user_achievements')) {
        $conn->query("
            CREATE TABLE user_achievements (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                achievement_id INT NOT NULL,
                status ENUM('locked','completed') DEFAULT 'locked',
                completed_at DATETIME NULL,
                UNIQUE KEY uniq_user_achievement (user_id, achievement_id),
                KEY user_id (user_id),
                KEY achievement_id (achievement_id),
                CONSTRAINT user_achievements_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                CONSTRAINT user_achievements_ibfk_2 FOREIGN KEY (achievement_id) REFERENCES achievements (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }
    ensure_columns($conn, 'user_achievements', [
        'achievement_id' => "INT NOT NULL DEFAULT 0",
        'status'         => "ENUM('locked','completed') DEFAULT 'locked'",
        'completed_at'   => "DATETIME NULL",
    ]);

    if (!table_exists($conn, 'user_daily_challenges')) {
        $conn->query("
            CREATE TABLE user_daily_challenges (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                achievement_id INT NOT NULL,
                assigned_at DATETIME NOT NULL,
                KEY user_id (user_id),
                KEY achievement_id (achievement_id),
                CONSTRAINT user_daily_challenges_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                CONSTRAINT user_daily_challenges_ibfk_2 FOREIGN KEY (achievement_id) REFERENCES achievements (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }
    ensure_columns($conn, 'user_daily_challenges', [
        'achievement_id' => "INT NOT NULL DEFAULT 0",
        'assigned_at'    => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
    ]);

    if (table_exists($conn, 'achievements')) {
        $achCount = $conn->query("SELECT COUNT(*) AS c FROM achievements")->fetch_assoc()['c'] ?? 0;
        if ($achCount == 0) {
            seed_achievements($conn);
        }
    }
}

/**
 * Generates exactly 100 achievements spread across every stat the
 * platform tracks (lessons, quest levels, XP, badges, certificates,
 * profile completeness, feedback, friends, perfect scores, and account
 * age), with difficulty and XP reward scaled to how hard each is to reach.
 */
function seed_achievements(mysqli $conn): void
{
    $difficultyXp = ['easy' => 15, 'medium' => 30, 'hard' => 60, 'epic' => 120];

    // Assigns a difficulty based on position within the ordered threshold list.
    $bucket = function (int $index, int $total): string {
        if ($index === $total - 1) return 'epic';
        if ($index >= $total * 0.66) return 'hard';
        if ($index >= $total * 0.33) return 'medium';
        return 'easy';
    };

    $defs = []; // [title, description, icon, criteria_type, criteria_value, difficulty]

    // 1) Lessons completed (8 max - one per level)
    $lessonNames = [
        1 => 'First Lesson Down', 2 => 'Building Momentum', 3 => 'Halfway Learner',
        4 => 'Steady Scholar', 5 => 'Deep Diver', 6 => 'Almost There',
        7 => 'One To Go', 8 => 'Course Completionist',
    ];
    $thresholds = range(1, 8);
    foreach ($thresholds as $i => $t) {
        $defs[] = [$lessonNames[$t], "Complete $t lesson" . ($t > 1 ? 's' : '') . " in your Learning Journey.", '📘', 'lessons_completed', $t, $bucket($i, count($thresholds))];
    }

    // 2) Quest levels completed (8 max)
    $thresholds = range(1, 8);
    foreach ($thresholds as $i => $t) {
        $defs[] = ["Quest Rank $t", "Pass $t Quest level" . ($t > 1 ? 's' : '') . " with a scenario score of 80%+.", '🧭', 'quest_levels_completed', $t, $bucket($i, count($thresholds))];
    }

    // 3) Legacy bonus quests completed
    $thresholds = [1, 2, 3, 5];
    foreach ($thresholds as $i => $t) {
        $defs[] = ["Bonus Hunter $t", "Complete $t bonus quest" . ($t > 1 ? 's' : '') . " from the community board.", '🎯', 'legacy_quests_completed', $t, $bucket($i, count($thresholds))];
    }

    // 4) Level reached (account level 1-8)
    $levelNames = [
        1 => 'Explorer', 2 => 'Digital Citizen', 3 => 'Privacy Guardian', 4 => 'Cyber Defender',
        5 => 'Social Savvy', 6 => 'Fact Checker', 7 => 'Governance Guru', 8 => 'Digital Rights Champion',
    ];
    $thresholds = range(1, 8);
    foreach ($thresholds as $i => $t) {
        $defs[] = ["$levelNames[$t]", "Reach Level $t on the platform.", '⭐', 'level_reached', $t, $bucket($i, count($thresholds))];
    }

    // 5) XP total milestones
    $thresholds = [10, 25, 50, 75, 100, 150, 200, 300, 400, 500, 650, 800, 1000, 1250, 1500, 1750, 2000, 2500, 3000, 4000, 5000, 7500];
    foreach ($thresholds as $i => $t) {
        $defs[] = ["Earn $t XP", "Accumulate a total of $t experience points.", '⚡', 'xp_total', $t, $bucket($i, count($thresholds))];
    }

    // 6) Badges earned (8 max - one per level badge)
    $thresholds = range(1, 8);
    foreach ($thresholds as $i => $t) {
        $defs[] = ["Badge Collector $t", "Earn $t badge" . ($t > 1 ? 's' : '') . " by scoring 90%+ on level assessments.", '🏅', 'badges_earned', $t, $bucket($i, count($thresholds))];
    }

    // 7) Certificates earned
    $defs[] = ['Certified!', 'Complete the full 8-level course and earn your certificate.', '📜', 'certificates_earned', 1, 'epic'];

    // 8) Profile completeness
    $defs[] = ['Say Cheese', 'Upload a profile picture.', '🖼️', 'profile_photo_uploaded', 1, 'easy'];
    $defs[] = ['Tell Your Story', 'Fill in your experience on your profile.', '✍️', 'profile_experience_filled', 1, 'easy'];

    // 9) Feedback submitted
    $thresholds = [1, 2, 3, 5, 8, 12, 20];
    foreach ($thresholds as $i => $t) {
        $defs[] = ["Voice Heard x$t", "Submit $t piece" . ($t > 1 ? 's' : '') . " of feedback to help improve the platform.", '💬', 'feedback_submitted', $t, $bucket($i, count($thresholds))];
    }

    // 10) Friends accepted
    $thresholds = [1, 2, 3, 4, 5, 7, 10, 15, 20, 25];
    foreach ($thresholds as $i => $t) {
        $defs[] = ["Social Circle $t", "Connect with $t friend" . ($t > 1 ? 's' : '') . " on the platform.", '🤝', 'friends_accepted', $t, $bucket($i, count($thresholds))];
    }

    // 11) Perfect scores (100% on a level assessment)
    $thresholds = range(1, 8);
    foreach ($thresholds as $i => $t) {
        $defs[] = ["Flawless x$t", "Score a perfect 100% on $t level assessment" . ($t > 1 ? 's' : '') . ".", '💯', 'perfect_scores', $t, $bucket($i, count($thresholds))];
    }

    // 12) Account age / loyalty
    $thresholds = [1, 3, 7, 14, 21, 30, 45, 60, 90, 120, 150, 180, 365];
    $ageNames = [1 => 'Day One', 3 => 'Getting Settled', 7 => 'One Week In', 14 => 'Two Weeks Strong'];
    foreach ($thresholds as $i => $t) {
        $label = $ageNames[$t] ?? "Loyal Member ({$t}d)";
        $defs[] = [$label, "Stay part of the platform for $t day" . ($t > 1 ? 's' : '') . ".", '🗓️', 'account_age_days', $t, $bucket($i, count($thresholds))];
    }

    // 13) Welcome achievement - unlocks for everyone immediately
    $defs[] = ['Welcome Aboard!', 'Create your account and begin your Internet Governance journey.', '🎉', 'always_true', 1, 'easy'];

    // Trim/pad to exactly 100 so the catalog is predictable.
    $defs = array_slice($defs, 0, 100);

    $stmt = $conn->prepare("
        INSERT INTO achievements (title, description, icon, criteria_type, criteria_value, difficulty, xp_reward, is_daily_eligible)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ");
    foreach ($defs as [$title, $desc, $icon, $type, $value, $difficulty]) {
        $xp = $difficultyXp[$difficulty];
        $stmt->bind_param("ssssisi", $title, $desc, $icon, $type, $value, $difficulty, $xp);
        $stmt->execute();
    }
}
