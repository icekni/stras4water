<?php

namespace App\Service;

class DisplayStateProvider
{
    public function __construct(
        private readonly CoverService $coverService,
    ) {
    }

    public function getState(): array
    {
        $currentArtist = 'Marc Anthony';
        $currentTitle = 'Vivir Mi Vida (Official Remix Version Extended Dance Floor)';

        $previousArtist = 'Prince Royce';
        $previousTitle = 'Darte un Beso';

        $next = [
            [
                'style' => 'kizomba',
                'icon' => '🟦',
                'artist' => 'C4 Pedro',
                'title' => 'Vamos Ficar',
            ],

            [
                'style' => 'bachata',
                'icon' => '🟣',
                'artist' => 'Dani J',
                'title' => 'Quitémonos la Ropa',
            ],
        ];

        return [

            'current' => [

                'style' => 'salsa',

                'icon' => '🟥',

                'artist' => $currentArtist,

                'title' => $currentTitle,

                'cover' => $this->coverService->getCover(
                    $currentArtist,
                    $currentTitle
                ),

                'elapsed' => 221,

                'duration' => 302,
            ],


            'previous' => [

                'style' => 'bachata',

                'icon' => '🟣',

                'artist' => $previousArtist,

                'title' => $previousTitle,

                'cover' => $this->coverService->getCover(
                    $previousArtist,
                    $previousTitle
                ),
            ],


            'next' => array_map(
                function (array $song): array {

                    $song['cover'] =
                        $this->coverService->getCover(
                            $song['artist'],
                            $song['title']
                        );

                    return $song;
                },
                $next
            ),


            'nextStyle' => [

                'style' => 'bachata',

                'icon' => '🟣',

                'count' => 2,

                'minutes' => 8,
            ],


            'events' => [

                '09 août - SBK Wagon Souk',

                '22 août - BachaKizz',

                '23 août - Stage Kizomba',
            ],
        ];
    }
}