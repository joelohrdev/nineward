<?php

use App\Models\Player;
use App\Models\Season;

test('league age is the player\'s age on the season\'s cutoff date', function (string $dateOfBirth, int $leagueAge) {
    $season = Season::factory()->make(['age_cutoff_on' => '2026-05-01']);
    $player = Player::factory()->make(['date_of_birth' => $dateOfBirth]);

    expect($player->leagueAgeFor($season))->toBe($leagueAge);
})->with([
    'tenth birthday the day before the cutoff' => ['2016-04-30', 10],
    'tenth birthday on the cutoff' => ['2016-05-01', 10],
    'tenth birthday the day after the cutoff' => ['2016-05-02', 9],
    'born after the cutoff' => ['2026-06-01', 0],
]);
