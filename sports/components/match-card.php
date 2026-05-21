<?php

if (!defined('FLEXOR_SPORTS')) {
    exit('Direct access denied');
}

/*
|--------------------------------------------------------------------------
| TEAMS
|--------------------------------------------------------------------------
*/

$homeTeam =
    $match['homeTeam']['shortName']
    ??
    $match['homeTeam']['name']
    ??
    'Unknown';

$awayTeam =
    $match['awayTeam']['shortName']
    ??
    $match['awayTeam']['name']
    ??
    'Unknown';

$homeLogo =
    $match['homeTeam']['crest']
    ?? '';

$awayLogo =
    $match['awayTeam']['crest']
    ?? '';

/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

$status =
    $match['status']
    ?? 'TIMED';

$isLive = false;

$statusText = 'قادمة';

$statusClass = 'status-upcoming';

if (

    $status === 'IN_PLAY'

    ||

    $status === 'LIVE'

) {

    $isLive = true;

    $statusText = 'مباشر';

    $statusClass = 'status-live';
}
elseif (

    $status === 'PAUSED'

) {

    $isLive = true;

    $statusText = 'استراحة';

    $statusClass = 'status-live';
}
elseif (

    $status === 'FINISHED'

) {

    $statusText = 'انتهت';

    $statusClass = 'status-finished';
}

/*
|--------------------------------------------------------------------------
| MATCH TIME
|--------------------------------------------------------------------------
*/

$utcDate =
    $match['utcDate']
    ?? '';

$matchTime = '';

$matchDate = '';

if (!empty($utcDate)) {

    $dateObject = new DateTime(
        $utcDate,
        new DateTimeZone('UTC')
    );

    $dateObject->setTimezone(
        new DateTimeZone(
            'Africa/Cairo'
        )
    );

    $matchTime =
        $dateObject->format(
            'h:i A'
        );

    $matchDate =
        $dateObject->format(
            'd M Y'
        );
}

/*
|--------------------------------------------------------------------------
| SCORE
|--------------------------------------------------------------------------
*/

$homeScore =
    $match['score']['fullTime']['home']
    ?? null;

$awayScore =
    $match['score']['fullTime']['away']
    ?? null;

$scoreText = 'VS';

if (

    $homeScore !== null
    &&
    $awayScore !== null

) {

    $scoreText =
        $homeScore .
        ' : ' .
        $awayScore;
}

/*
|--------------------------------------------------------------------------
| STADIUM
|--------------------------------------------------------------------------
*/

$stadium =
    $match['venue']
    ?? '';

/*
|--------------------------------------------------------------------------
| MATCH MINUTE
|--------------------------------------------------------------------------
*/

$minute = '';

if (
    !empty($match['minute'])
) {

    $minute =
        $match['minute'] . "'";
}

?>

<a
    href="/sports/match.php?id=<?= $match['id'] ?>"
    class="
    match-card
    <?= $isLive
        ? 'live-card'
        : '' ?>
    "
    style="
        border:1px solid <?= $theme['secondary'] ?>55;
    "
>

    <!-- LIVE BADGE -->

    <div
        class="
        live-badge
        <?= $statusClass ?>
        "
        style="
            background:
            <?= $theme['accent'] ?>;
        "
    >

        <?= $statusText ?>

    </div>

    <?php if ($isLive): ?>

        <div class="live-pulse"></div>

    <?php endif; ?>

    <!-- MATCH MINUTE -->

    <?php if (!empty($minute)): ?>

        <div class="match-minute">

            <?= $minute ?>

        </div>

    <?php endif; ?>

    <!-- MATCH TOP -->

    <div class="match-top">

        <div class="match-competition-small">

            <?= htmlspecialchars(
                $competition['name']
            ) ?>

        </div>

    </div>

    <!-- TEAMS -->

    <div class="match-teams">

        <!-- HOME -->

        <div class="team">

            <div class="team-logo-wrap">

                <img
                    src="<?= htmlspecialchars($homeLogo) ?>"
                    alt=""
                    loading="lazy"
                >

            </div>

            <div class="team-name">

                <?= htmlspecialchars($homeTeam) ?>

            </div>

        </div>

        <!-- SCORE -->

        <div class="match-center">

            <div class="
                match-score
                <?= $isLive
                    ? 'live-score'
                    : '' ?>
            ">

                <?= $scoreText ?>

            </div>

            <div class="match-status-text">

                <?= $statusText ?>

            </div>

        </div>

        <!-- AWAY -->

        <div class="team">

            <div class="team-logo-wrap">

                <img
                    src="<?= htmlspecialchars($awayLogo) ?>"
                    alt=""
                    loading="lazy"
                >

            </div>

            <div class="team-name">

                <?= htmlspecialchars($awayTeam) ?>

            </div>

        </div>

    </div>

    <!-- INFO -->

    <div class="match-info">

        <div class="match-time">

            <?= $matchTime ?>

        </div>

        <div class="match-date">

            <?= $matchDate ?>

        </div>

        <?php if (!empty($stadium)): ?>

            <div class="match-stadium">

                <?= htmlspecialchars($stadium) ?>

            </div>

        <?php endif; ?>

    </div>

</a>