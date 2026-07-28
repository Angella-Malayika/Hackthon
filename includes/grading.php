<?php
/**
 * Shared grading helpers for structured/short-answer and scenario questions
 * used across learn.php levels (level1.php ... level8.php) and quests.php.
 *
 * Goal: give credit for answers that are correct OR "close enough" -
 * not just an exact keyword substring match. This rewards students who
 * understand the concept but phrase it a little differently.
 */

if (!function_exists('is_close_answer')) {
    /**
     * Returns true if $userAnswer is a correct - or near-correct - match
     * for any of the phrases in $keyPhrases.
     *
     * Matching strategy (checked in order, cheapest first):
     *  1. Direct substring match (handles the old exact-keyword behaviour)
     *  2. Word-overlap ratio - counts how many of the key phrase's
     *     significant words appear in the user's answer
     *  3. Character-level similarity (similar_text) - catches typos,
     *     re-ordering, and paraphrasing that's "basically right"
     */
    function is_close_answer(string $userAnswer, array $keyPhrases): bool
    {
        $answer = strtolower(trim($userAnswer));
        if ($answer === '') {
            return false;
        }

        foreach ($keyPhrases as $phrase) {
            $phrase = strtolower(trim($phrase));
            if ($phrase === '') {
                continue;
            }

            // 1. Exact / substring match - still the strongest signal
            if (strpos($answer, $phrase) !== false) {
                return true;
            }

            // 2. Word overlap - e.g. "rules people use to stay safe online"
            //    should still credit "internet governance" style hints if
            //    most of the meaningful words line up.
            $stopWords = ['a','an','the','is','are','of','to','and','or','in','on','for','it','that','this'];
            $phraseWords = array_diff(preg_split('/\W+/', $phrase, -1, PREG_SPLIT_NO_EMPTY), $stopWords);
            $answerWords = array_diff(preg_split('/\W+/', $answer, -1, PREG_SPLIT_NO_EMPTY), $stopWords);
            if (count($phraseWords) > 0 && count($answerWords) > 0) {
                $common = array_intersect($phraseWords, $answerWords);
                $overlap = count($common) / count($phraseWords);
                if ($overlap >= 0.6) {
                    return true;
                }
            }

            // 3. Character-level similarity - catches close-but-not-quite
            //    phrasing and minor spelling mistakes.
            similar_text($answer, $phrase, $percent);
            if ($percent >= 55) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('grade_scenario_answer')) {
    /**
     * Grades an open-ended scenario answer against a set of "likely
     * possible answers" (acceptable reasoning points). Returns a score
     * from 0-100 based on how many distinct acceptable points the
     * learner's answer touches on, using the same close-match logic as
     * is_close_answer() for each point.
     *
     * $acceptablePoints: array of arrays, each inner array is a group of
     * synonymous phrases representing ONE valid point the learner could make.
     * e.g. [['report it','flag it','notify'], ['do not click the link','avoid clicking']]
     */
    function grade_scenario_answer(string $userAnswer, array $acceptablePoints): int
    {
        $answer = trim($userAnswer);
        if ($answer === '') {
            return 0;
        }

        $pointsHit = 0;
        foreach ($acceptablePoints as $pointGroup) {
            if (is_close_answer($answer, (array)$pointGroup)) {
                $pointsHit++;
            }
        }

        if (count($acceptablePoints) === 0) {
            return 0;
        }

        // Give a little credit just for writing a substantive attempt,
        // then scale the rest by how many acceptable points were covered.
        $effortCredit = (str_word_count($answer) >= 15) ? 15 : (str_word_count($answer) >= 6 ? 8 : 0);
        $coverage = ($pointsHit / count($acceptablePoints)) * 85;

        return (int) min(100, round($effortCredit + $coverage));
    }
}
