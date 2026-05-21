<?php

if (!defined('FLEXOR_SPORTS')) {
    exit('Direct access denied');
}

/*
|--------------------------------------------------------------------------
| GET FEATURED MATCHES
|--------------------------------------------------------------------------
*/

$featuredMatches = [];

foreach (
    $SPORTS_COMPETITIONS
    as $competition
) {

    /*
    |--------------------------------------------------------------------------
    | SHARED MATCHES
    |--------------------------------------------------------------------------
    */

    $matches =
    $allCompetitionMatches[
        $competition['code']
    ]
    ?? [];

    if(
        empty($matches)
    ){
        continue;
    }

    foreach (
        $matches
        as $match
    ) {

        if (
            empty($match['utcDate'])
        ) {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | MATCH DATE
        |--------------------------------------------------------------------------
        */

        $matchDate =
        date(
            'Y-m-d',
            strtotime(
                $match['utcDate']
            )
        );

        /*
        |--------------------------------------------------------------------------
        | CURRENT DATE
        |--------------------------------------------------------------------------
        */

        if(
            empty($currentDate)
        ){

            $currentDate =
            date('Y-m-d');
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER TODAY
        |--------------------------------------------------------------------------
        */

        if (
            $matchDate !== $currentDate
        ) {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | SAVE COMPETITION
        |--------------------------------------------------------------------------
        */

        $match['competition_data'] =
        $competition;

        $featuredMatches[] =
        $match;
    }
}

/*
|--------------------------------------------------------------------------
| SORT FEATURED
|--------------------------------------------------------------------------
*/

usort(
    $featuredMatches,
    function($a, $b){

        $priority = [

            'IN_PLAY'   => 1,
            'LIVE'      => 1,
            'PAUSED'    => 1,

            'TIMED'     => 2,

            'FINISHED'  => 3
        ];

        $aStatus =
        $a['status']
        ?? 'TIMED';

        $bStatus =
        $b['status']
        ?? 'TIMED';

        return (
            ($priority[$aStatus] ?? 99)
            <=>
            ($priority[$bStatus] ?? 99)
        );
    }
);

/*
|--------------------------------------------------------------------------
| LIMIT
|--------------------------------------------------------------------------
*/

$featuredMatches =
array_slice(
    $featuredMatches,
    0,
    5
);

?>

<?php if (!empty($featuredMatches)): ?>

<div class="hero-slider">

    <?php foreach (
        $featuredMatches
        as $index => $match
    ): ?>

        <?php

        $competition =
        $match['competition_data'];

        $theme =
        $SPORTS_THEMES[
            $competition['code']
        ];

        $homeTeam =
        $match['homeTeam']['shortName']
        ?? $match['homeTeam']['name']
        ?? '';

        $awayTeam =
        $match['awayTeam']['shortName']
        ?? $match['awayTeam']['name']
        ?? '';

        $homeLogo =
        $match['homeTeam']['crest']
        ?? '';

        $awayLogo =
        $match['awayTeam']['crest']
        ?? '';

        $leagueLogo =
        $competition['logo']
        ?? '';

        $status =
        $match['status']
        ?? 'TIMED';

        /*
        |--------------------------------------------------------------------------
        | STATUS TEXT
        |--------------------------------------------------------------------------
        */

        $statusText = 'قادمة';

        if (
            $status === 'IN_PLAY'
            ||
            $status === 'LIVE'
        ) {

            $statusText = 'مباشر';
        }
        elseif (
            $status === 'FINISHED'
        ) {

            $statusText = 'انتهت';
        }
        elseif (
            $status === 'PAUSED'
        ) {

            $statusText = 'استراحة';
        }

        /*
        |--------------------------------------------------------------------------
        | SCORE
        |--------------------------------------------------------------------------
        */

        $scoreHome =
        $match['score']['fullTime']['home']
        ?? 0;

        $scoreAway =
        $match['score']['fullTime']['away']
        ?? 0;

        ?>

        <div
            class="
            hero-slide
            <?= $index === 0
                ? 'active active-slide'
                : '' ?>
            "
            data-primary="<?= $theme['primary'] ?>"
            data-secondary="<?= $theme['secondary'] ?>"
        >

            <!-- BACKGROUND -->

            <div
                class="hero-bg"
                style="
                    background:
                    linear-gradient(
                        135deg,
                        <?= $theme['primary'] ?>,
                        <?= $theme['secondary'] ?>
                    );
                "
            ></div>

            <!-- LEAGUE LOGO -->

            <div class="hero-bg-logo">

                <img
                    src="<?= $leagueLogo ?>"
                    alt=""
                >

            </div>

            <!-- OVERLAY -->

            <div class="hero-overlay"></div>

            <!-- CONTENT -->

            <div class="hero-content">

                <!-- LEFT -->

                <div class="hero-left">

                    <div class="hero-league">

                        <?= $competition['name'] ?>

                    </div>

                    <div class="hero-title">

                        <?= $homeTeam ?>

                        VS

                        <?= $awayTeam ?>

                    </div>

                    <div class="hero-meta">

                        <div class="hero-badge">

                            <?= $statusText ?>

                        </div>

                        <div class="hero-badge">

                            Featured Match

                        </div>

                    </div>

                    <div class="hero-description">

                        شاهد أهم مباريات اليوم
                        بجودة عالية ونتائج مباشرة
                        وإحصائيات كاملة داخل FLEXOR.

                    </div>

                </div>

                <!-- RIGHT -->

                <div class="hero-right">

                    <!-- HOME -->

                    <div class="hero-team">

                        <img
                            src="<?= $homeLogo ?>"
                            alt=""
                        >

                        <div class="hero-team-name">

                            <?= $homeTeam ?>

                        </div>

                    </div>

                    <!-- SCORE -->

                    <div class="hero-center-score">

                        <div class="hero-team-score">

                            <?= $scoreHome ?>

                        </div>

                        <div class="hero-vs">

                            VS

                        </div>

                        <div class="hero-team-score">

                            <?= $scoreAway ?>

                        </div>

                    </div>

                    <!-- AWAY -->

                    <div class="hero-team">

                        <img
                            src="<?= $awayLogo ?>"
                            alt=""
                        >

                        <div class="hero-team-name">

                            <?= $awayTeam ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    <?php endforeach; ?>

    <!-- DOTS -->

    <div class="hero-nav">

        <?php foreach (
            $featuredMatches
            as $index => $match
        ): ?>

            <div
                class="
                hero-dot
                <?= $index === 0
                    ? 'active-dot'
                    : '' ?>
                "
                data-slide="<?= $index ?>"
            ></div>

        <?php endforeach; ?>

    </div>

</div>

<?php endif; ?>