<?php
/**
 * Achievements engine.
 *
 * - evaluate_achievements(): looks at a user's current stats (lessons
 *   completed, quest levels passed, XP, badges, streaks, etc.) and
 *   unlocks any achievement whose criteria is now met, awarding XP for
 *   each one based on its difficulty.
 * - get_daily_challenges(): returns the user's current set of 5 daily
 *   challenge achievements, generating a fresh set automatically once
 *   24 hours have passed since the last set was assigned.
 *
 * Call evaluate_achievements($conn, $user_id) after any action that
 * could move the needle (finishing a level, completing a quest,
 * updating a profile, adding a friend, etc.) so achievements unlock in
 * near real-time. It is cheap and safe to call repeatedly - every
 * achievement is only ever awarded once per user.
 */

const ACHIEVEMENT_DIFFICULTY_XP = [
    'easy'   => 15,
    'medium' => 30,
    'hard'   => 60,
    'epic'   => 120,
];

/**
 * Gathers the stats needed to check every achievement criteria_type
 * in one place, so evaluate_achievements() only has to hit the DB once.
 */
function collect_user_stats(mysqli $conn, int $user_id): array
{
    $stats = [
        'lessons_completed'        => 0,
        'quest_levels_completed'   => 0,
        'legacy_quests_completed'  => 0,
        'level_reached'            => 1,
        'xp_total'                 => 0,
        'badges_earned'            => 0,
        'certificates_earned'      => 0,
        'profile_photo_uploaded'   => 0,
        'profile_experience_filled'=> 0,
        'feedback_submitted'       => 0,
        'friends_accepted'         => 0,
        'perfect_scores'           => 0,
        'account_age_days'         => 0,
        'always_true'              => 1,
    ];

    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM lesson_progress WHERE user_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $user_id); $stmt->execute();
    $stats['lessons_completed'] = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);

    if (table_exists($conn, 'quest_progress')) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM quest_progress WHERE user_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $user_id); $stmt->execute();
        $stats['quest_levels_completed'] = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM user_quests WHERE user_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $user_id); $stmt->execute();
    $stats['legacy_quests_completed'] = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);

    $userCols = ['level', 'xp'];
    foreach (['profile_picture', 'experience', 'created_at'] as $optionalCol) {
        if (column_exists($conn, 'users', $optionalCol)) {
            $userCols[] = $optionalCol;
        }
    }
    $stmt = $conn->prepare("SELECT " . implode(', ', $userCols) . " FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id); $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    if ($u) {
        $stats['level_reached'] = (int) $u['level'];
        $stats['xp_total'] = (int) $u['xp'];
        $stats['profile_photo_uploaded'] = (!empty($u['profile_picture']) && $u['profile_picture'] !== 'default.png') ? 1 : 0;
        $stats['profile_experience_filled'] = !empty(trim((string)($u['experience'] ?? ''))) ? 1 : 0;
        if (!empty($u['created_at'])) {
            $stats['account_age_days'] = (int) floor((time() - strtotime($u['created_at'])) / 86400);
        }
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM user_badges WHERE user_id = ?");
    $stmt->bind_param("i", $user_id); $stmt->execute();
    $stats['badges_earned'] = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);

    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM certificates WHERE user_id = ?");
    $stmt->bind_param("i", $user_id); $stmt->execute();
    $stats['certificates_earned'] = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);

    if (table_exists($conn, 'feedback')) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM feedback WHERE user_id = ?");
        $stmt->bind_param("i", $user_id); $stmt->execute();
        $stats['feedback_submitted'] = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    }

    if (table_exists($conn, 'friends')) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM friends WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'");
        $stmt->bind_param("ii", $user_id, $user_id); $stmt->execute();
        $stats['friends_accepted'] = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    }

    if (column_exists($conn, 'lesson_progress', 'score')) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM lesson_progress WHERE user_id = ? AND status = 'completed' AND score >= 100");
        $stmt->bind_param("i", $user_id); $stmt->execute();
        $stats['perfect_scores'] = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    }

    return $stats;
}

/**
 * Checks every achievement the user hasn't unlocked yet against their
 * current stats and unlocks/awards XP for any that now qualify.
 *
 * Returns an array of newly-unlocked achievement rows (empty if none).
 */
function evaluate_achievements(mysqli $conn, int $user_id): array
{
    if (!table_exists($conn, 'achievements') || !table_exists($conn, 'user_achievements')) {
        return [];
    }

    $stats = collect_user_stats($conn, $user_id);
    $newlyUnlocked = [];

    $result = $conn->query("
        SELECT a.* FROM achievements a
        WHERE a.id NOT IN (
            SELECT achievement_id FROM user_achievements
            WHERE user_id = " . intval($user_id) . " AND status = 'completed'
        )
    ");
    if (!$result) {
        return [];
    }

    $toAward = [];
    while ($ach = $result->fetch_assoc()) {
        $type = $ach['criteria_type'];
        $need = (int) $ach['criteria_value'];
        $have = $stats[$type] ?? null;
        if ($have !== null && $have >= $need) {
            $toAward[] = $ach;
        }
    }

    if (empty($toAward)) {
        return [];
    }

    $xpGain = 0;
    $upsert = $conn->prepare("
        INSERT INTO user_achievements (user_id, achievement_id, status, completed_at)
        VALUES (?, ?, 'completed', NOW())
        ON DUPLICATE KEY UPDATE status = 'completed', completed_at = NOW()
    ");
    foreach ($toAward as $ach) {
        $achId = (int) $ach['id'];
        $upsert->bind_param("ii", $user_id, $achId);
        $upsert->execute();
        $xpGain += (int) $ach['xp_reward'];
        $newlyUnlocked[] = $ach;
    }

    if ($xpGain > 0) {
        $xpStmt = $conn->prepare("UPDATE users SET xp = xp + ? WHERE id = ?");
        $xpStmt->bind_param("ii", $xpGain, $user_id);
        $xpStmt->execute();
    }

    return $newlyUnlocked;
}

/**
 * Returns the user's current 5 daily challenges (achievement rows, each
 * with a `completed` flag). A fresh set of 5 is generated automatically
 * whenever none exist yet, or the existing set is 24+ hours old.
 * Challenges that the user hasn't completed yet are prioritised so the
 * daily set is actually something to work towards.
 */
function get_daily_challenges(mysqli $conn, int $user_id, int $count = 5): array
{
    if (!table_exists($conn, 'achievements') || !table_exists($conn, 'user_daily_challenges')) {
        return [];
    }

    $ageStmt = $conn->prepare("SELECT MIN(assigned_at) AS oldest, COUNT(*) AS c FROM user_daily_challenges WHERE user_id = ?");
    $ageStmt->bind_param("i", $user_id);
    $ageStmt->execute();
    $row = $ageStmt->get_result()->fetch_assoc();

    $needsRefresh = true;
    if ($row && $row['c'] >= $count && $row['oldest']) {
        $ageSeconds = time() - strtotime($row['oldest']);
        $needsRefresh = $ageSeconds >= 86400; // 24 hours
    }

    if ($needsRefresh) {
        $del = $conn->prepare("DELETE FROM user_daily_challenges WHERE user_id = ?");
        $del->bind_param("i", $user_id);
        $del->execute();

        // Prefer achievements the user hasn't completed yet, so the daily
        // set is a genuine challenge. Top up with completed ones if needed.
        $picked = [];
        $incomplete = $conn->prepare("
            SELECT a.id FROM achievements a
            WHERE a.is_daily_eligible = 1
              AND a.id NOT IN (
                  SELECT achievement_id FROM user_achievements
                  WHERE user_id = ? AND status = 'completed'
              )
            ORDER BY RAND() LIMIT ?
        ");
        $incomplete->bind_param("ii", $user_id, $count);
        $incomplete->execute();
        $res = $incomplete->get_result();
        while ($r = $res->fetch_assoc()) {
            $picked[] = (int) $r['id'];
        }

        if (count($picked) < $count) {
            $remaining = $count - count($picked);
            $exclude = count($picked) ? implode(',', $picked) : '0';
            $topUp = $conn->query("
                SELECT id FROM achievements
                WHERE is_daily_eligible = 1 AND id NOT IN ($exclude)
                ORDER BY RAND() LIMIT $remaining
            ");
            if ($topUp) {
                while ($r = $topUp->fetch_assoc()) {
                    $picked[] = (int) $r['id'];
                }
            }
        }

        if (!empty($picked)) {
            $ins = $conn->prepare("INSERT INTO user_daily_challenges (user_id, achievement_id, assigned_at) VALUES (?, ?, NOW())");
            foreach ($picked as $achId) {
                $ins->bind_param("ii", $user_id, $achId);
                $ins->execute();
            }
        }
    }

    $out = [];
    $result = $conn->prepare("
        SELECT a.*, udc.assigned_at,
               (ua.status = 'completed') AS is_completed
        FROM user_daily_challenges udc
        JOIN achievements a ON a.id = udc.achievement_id
        LEFT JOIN user_achievements ua ON ua.achievement_id = a.id AND ua.user_id = udc.user_id
        WHERE udc.user_id = ?
        ORDER BY a.difficulty = 'easy' DESC, a.id ASC
        LIMIT ?
    ");
    $result->bind_param("ii", $user_id, $count);
    $result->execute();
    $rows = $result->get_result();
    while ($r = $rows->fetch_assoc()) {
        $r['is_completed'] = (bool) $r['is_completed'];
        $out[] = $r;
    }

    return $out;
}

/** Human-readable label for an achievement's underlying stat category. */
function achievement_category_label(string $criteriaType): string
{
    $labels = [
        'lessons_completed'         => 'Learning',
        'quest_levels_completed'    => 'Quests',
        'legacy_quests_completed'   => 'Bonus Quests',
        'level_reached'             => 'Progression',
        'xp_total'                  => 'Experience',
        'badges_earned'             => 'Badges',
        'certificates_earned'       => 'Certificates',
        'profile_photo_uploaded'    => 'Profile',
        'profile_experience_filled' => 'Profile',
        'feedback_submitted'        => 'Community',
        'friends_accepted'          => 'Social',
        'perfect_scores'            => 'Mastery',
        'account_age_days'          => 'Loyalty',
        'always_true'               => 'Getting Started',
    ];
    return $labels[$criteriaType] ?? 'General';
}
