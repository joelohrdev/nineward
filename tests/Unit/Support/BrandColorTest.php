<?php

use App\Support\BrandColor;

test('picks the text color with more contrast', function (string $color, string $text) {
    expect((new BrandColor($color))->textColor())->toBe($text);
})->with([
    'dark clay' => ['#B5532C', '#FFFFFF'],
    'yellow' => ['#FFD700', '#1C2430'],
]);

test('a mid-tone color is readable as it is', function () {
    expect((new BrandColor('#B5532C'))->isReadable())->toBeTrue()
        ->and((new BrandColor('#B5532C'))->nearestReadable())->toBe('#B5532C');
});

test('colors too light or too dark are moved to the nearest readable lightness', function (string $color) {
    $suggestion = (new BrandColor($color))->nearestReadable();

    expect($suggestion)->not->toBeNull()
        ->and((new BrandColor($suggestion))->isReadable())->toBeTrue();
})->with(['white' => '#FFFFFF', 'black' => '#000000', 'gold' => '#FFD700', 'navy' => '#0A1A3F']);
