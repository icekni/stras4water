<?php

namespace App\Service;

use Symfony\Component\HttpKernel\KernelInterface;

class DisplayStateProvider
{
    private string $stateFile;

    public function __construct(
        private readonly CoverService $coverService,
        KernelInterface $kernel,
    ) {
        $this->stateFile =
            $kernel->getProjectDir()
            . '/var/display-state.json';
    }


    public function getState(): array
    {
        if (!file_exists($this->stateFile)) {
            return $this->getDemoState();
        }

        $content = file_get_contents(
            $this->stateFile
        );

        if ($content === false) {
            return $this->getDemoState();
        }

        $state = json_decode(
            $content,
            true
        );

        if (!is_array($state)) {
            return $this->getDemoState();
        }

        return $this->prepareState($state);
    }


    public function updateState(
        array $state
    ): void {
        $directory = dirname(
            $this->stateFile
        );

        if (!is_dir($directory)) {
            mkdir(
                $directory,
                0775,
                true
            );
        }

        file_put_contents(
            $this->stateFile,
            json_encode(
                $state,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
            ),
            LOCK_EX
        );
    }


    private function prepareState(
        array $state
    ): array {
        $current = $this->prepareSong(
            $state['current'] ?? null
        );

        if ($current !== null) {
            $current['cover'] =
                $this->coverService->getCover(
                    $current['artist'] ?? '',
                    $current['title'] ?? ''
                );
        }

        $previous = $this->prepareSong(
            $state['previous'] ?? null
        );

        $next = array_map(
            fn ($song) => $this->prepareSong($song),
            $state['next'] ?? []
        );

        return [
            'current' => $current,

            'previous' => $previous,

            'next' => $next,

            'nextStyle' =>
                $this->calculateNextStyle(
                    $next,
                    $current
                ),

            'events' => [
                '09 août - SBK Wagon Souk',
                '22 août - BachaKizz',
                '23 août - Stage Kizomba',
            ],
        ];
    }


    private function prepareSong(
        ?array $song
    ): ?array {
        if ($song === null) {
            return null;
        }

        $style = strtolower(
            $song['style']
            ?? $song['genre']
            ?? 'salsa'
        );

        $song['style'] =
            $this->normalizeStyle($style);

        $song['icon'] =
            $this->getStyleIcon(
                $song['style']
            );

        if (
            isset($song['length'])
            &&
            !isset($song['duration'])
        ) {
            $song['duration'] =
                $this->parseDuration(
                    $song['length']
                );
        }

        // Garantit des valeurs numériques pour le JavaScript
        if (isset($song['duration'])) {
            $song['duration'] =
                (float) $song['duration'];
        }

        if (isset($song['elapsed'])) {
            $song['elapsed'] =
                (float) $song['elapsed'];
        }

        return $song;
    }


    private function normalizeStyle(
        string $style
    ): string {
        $style = strtolower(
            trim($style)
        );

        if (str_contains($style, 'bachata')) {
            return 'bachata';
        }

        if (str_contains($style, 'kizomba')) {
            return 'kizomba';
        }

        if (str_contains($style, 'salsa')) {
            return 'salsa';
        }

        return 'salsa';
    }


    private function parseDuration(
        string $length
    ): int {
        $parts = explode(
            ':',
            trim($length)
        );

        if (count($parts) !== 2) {
            return 0;
        }

        return
            ((int) $parts[0] * 60)
            + (int) $parts[1];
    }


    private function getStyleIcon(
        string $style
    ): string {

        return match (
            strtolower($style)
        ) {

            'salsa' =>
                '🟥',

            'bachata' =>
                '🟣',

            'kizomba' =>
                '🟦',

            default =>
                '⬜',
        };
    }


    private function calculateNextStyle(
        array $next,
        ?array $current
    ): array {

        if ($current === null || empty($next)) {
            return [
                'style' =>
                    $current['style'] ?? 'salsa',

                'icon' =>
                    $this->getStyleIcon(
                        $current['style'] ?? 'salsa'
                    ),

                'count' => 0,
                'minutes' => 0,
            ];
        }

        $currentStyle = $current['style'];
        $minutes = 0;

        foreach ($next as $index => $song) {
            $style =
                $song['style']
                ?? 'salsa';

            if ($style !== $currentStyle) {
                return [
                    'style' => $style,

                    'icon' =>
                        $this->getStyleIcon($style),

                    'count' => $index + 1,

                    'minutes' =>
                        (int) round($minutes / 60),
                ];
            }

            $minutes +=
                $song['duration'] ?? 0;
        }

        return [
            'style' => $currentStyle,

            'icon' =>
                $this->getStyleIcon(
                    $currentStyle
                ),

            'count' => count($next),

            'minutes' =>
                (int) round($minutes / 60),
        ];
    }


    private function getDemoState(): array
    {
        return [

            'current' => [

                'style' =>
                    'salsa',

                'icon' =>
                    '🟥',

                'artist' =>
                    'Marc Anthony',

                'title' =>
                    'Vivir Mi Vida',

                'cover' =>
                    $this->coverService->getCover(
                        'Marc Anthony',
                        'Vivir Mi Vida'
                    ),

                'elapsed' =>
                    221,

                'duration' =>
                    302,
            ],

            'previous' => [

                'style' =>
                    'bachata',

                'icon' =>
                    '🟣',

                'artist' =>
                    'Prince Royce',

                'title' =>
                    'Darte un Beso',
            ],

            'next' => [

                [
                    'style' =>
                        'kizomba',

                    'icon' =>
                        '🟦',

                    'artist' =>
                        'C4 Pedro',

                    'title' =>
                        'Vamos Ficar',

                    'duration' =>
                        240,
                ],

                [
                    'style' =>
                        'bachata',

                    'icon' =>
                        '🟣',

                    'artist' =>
                        'Dani J',

                    'title' =>
                        'Quitémonos la Ropa',

                    'duration' =>
                        250,
                ],
            ],

            'nextStyle' => [

                'style' =>
                    'bachata',

                'icon' =>
                    '🟣',

                'count' =>
                    2,

                'minutes' =>
                    8,
            ],

            'events' => [

                '09 août - SBK Wagon Souk',
                '22 août - BachaKizz',
                '23 août - Stage Kizomba',
            ],
        ];
    }
}