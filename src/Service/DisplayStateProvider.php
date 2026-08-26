<?php

namespace App\Service;

class DisplayStateProvider
{
    public function __construct(
        private readonly MixxxService $mixxxService,
        private readonly CoverService $coverService,
    ) {
    }

    public function getState(): array
    {
        /*
         * Pour le moment :
         * on essaie de récupérer Mixxx.
         *
         * Si Mixxx n'est pas disponible,
         * on utilise les données de démonstration.
         */
        $mixxx = $this->mixxxService->getState();

        if ($mixxx['current'] === null) {
            return $this->getDemoState();
        }

        $current = $mixxx['current'];

        /*
         * Pochette.
         */
        $current['cover'] =
            $this->coverService->getCover(
                $current['artist'] ?? '',
                $current['title'] ?? ''
            );

        /*
         * On s'assure que les champs nécessaires
         * à l'écran existent.
         */
        $current['style'] =
            $current['style'] ?? 'salsa';

        $current['icon'] =
            $current['icon']
            ?? $this->getStyleIcon(
                $current['style']
            );

        return [
            'current' => $current,

            'previous' =>
                $this->prepareSong(
                    $mixxx['previous']
                ),

            'next' =>
                array_map(
                    fn ($song) =>
                        $this->prepareSong($song),
                    $mixxx['next'] ?? []
                ),

            'nextStyle' =>
                $this->calculateNextStyle(
                    $mixxx['next'] ?? []
                ),

            /*
             * Temporaire.
             * Les événements passeront plus tard
             * par la base de données.
             */
            'events' => [
                '09 août - SBK Wagon Souk',
                '22 août - BachaKizz',
                '23 août - Stage Kizomba',
            ],
        ];
    }


    /**
     * Prépare un morceau pour le Twig.
     */
    private function prepareSong(
        ?array $song
    ): ?array {

        if ($song === null) {
            return null;
        }

        $song['style'] =
            $song['style'] ?? 'salsa';

        $song['icon'] =
            $song['icon']
            ?? $this->getStyleIcon(
                $song['style']
            );

        return $song;
    }


    /**
     * Retourne l'icône correspondant au style.
     */
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


    /**
     * Détermine le prochain changement
     * de style à partir de la queue Mixxx.
     *
     * Pour le moment, on regarde les morceaux
     * suivants et on cherche le premier changement
     * de style.
     */
    private function calculateNextStyle(
        array $next
    ): array {

        if (empty($next)) {

            return [
                'style' => 'salsa',
                'icon' => '🟥',
                'count' => 0,
                'minutes' => 0,
            ];
        }

        /*
         * Style actuel de référence.
         *
         * On pourra améliorer ça ensuite en passant
         * également le morceau actuel à la méthode.
         */
        $currentStyle =
            $next[0]['style']
            ?? 'salsa';

        foreach (
            $next
            as $index => $song
        ) {

            $style =
                strtolower(
                    $song['style']
                    ?? ''
                );

            if (
                $style === ''
                ||
                $style === $currentStyle
            ) {
                continue;
            }

            /*
             * Nombre de morceaux avant le changement.
             */
            $count =
                $index + 1;

            /*
             * Estimation du temps.
             */
            $minutes = 0;

            for (
                $i = 0;
                $i < $index;
                $i++
            ) {

                $duration =
                    $next[$i]['duration']
                    ?? 0;

                $minutes +=
                    $duration / 60;
            }

            return [
                'style' => $style,

                'icon' =>
                    $this->getStyleIcon(
                        $style
                    ),

                'count' =>
                    $count,

                'minutes' =>
                    (int) round($minutes),
            ];
        }

        /*
         * Aucun changement trouvé.
         */
        return [
            'style' =>
                $currentStyle,

            'icon' =>
                $this->getStyleIcon(
                    $currentStyle
                ),

            'count' =>
                count($next),

            'minutes' =>
                0,
        ];
    }


    /**
     * Données de démonstration.
     *
     * Elles permettent de continuer à développer
     * l'écran même lorsque Mixxx n'est pas connecté.
     */
    private function getDemoState(): array
    {
        return [

            'current' => [

                'style' => 'salsa',

                'icon' => '🟥',

                'artist' =>
                    'Marc Anthony',

                'title' =>
                    'Vivir Mi Vida (Official Remix Version Extended Dance Floor)',

                'cover' =>
                    $this->coverService->getCover(
                        'Marc Anthony',
                        'Vivir Mi Vida (Official Remix Version Extended Dance Floor)'
                    ),

                'elapsed' => 221,

                'duration' => 302,
            ],


            'previous' => [

                'style' => 'bachata',

                'icon' => '🟣',

                'artist' =>
                    'Prince Royce',

                'title' =>
                    'Darte un Beso',
            ],


            'next' => [

                [

                    'style' => 'kizomba',

                    'icon' => '🟦',

                    'artist' =>
                        'C4 Pedro',

                    'title' =>
                        'Vamos Ficar',

                    'duration' =>
                        240,
                ],

                [

                    'style' => 'bachata',

                    'icon' => '🟣',

                    'artist' =>
                        'Dani J',

                    'title' =>
                        'Quitémonos la Ropa',

                    'duration' =>
                        250,
                ],
            ],


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