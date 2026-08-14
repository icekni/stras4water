<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CoverService
{
    private const MUSICBRAINZ_URL =
        'https://musicbrainz.org/ws/2/recording';

    private const COVER_ART_URL =
        'https://coverartarchive.org/release';

    private const USER_AGENT =
        'Stras4Water/1.0 (https://stras4water.org)';

    private string $projectDir;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        KernelInterface $kernel
    ) {
        $this->projectDir = $kernel->getProjectDir();
    }


    public function getCover(
        string $artist,
        string $title
    ): string {

        $artist = trim($artist);
        $title = trim($title);


        if (
            $artist === ''
            || $title === ''
        ) {
            return '/images/default-cover.jpg';
        }


        /*
         * Le cache dépend du morceau fourni par Mixxx.
         *
         * Ainsi :
         *
         * Vivir Mi Vida
         *
         * et
         *
         * Vivir Mi Vida (Extended Remix)
         *
         * peuvent avoir leur propre entrée.
         */
        $cacheKey =
            $this->getCacheKey(
                $artist,
                $title
            );


        $cacheDirectory =
            $this->getCacheDirectory();


        $cacheFile =
            $cacheDirectory
            . '/'
            . $cacheKey
            . '.jpg';


        /*
         * Cache local
         */
        if (is_file($cacheFile)) {

            return
                '/public/upload/covers/'
                . $cacheKey
                . '.jpg';
        }


        try {

            /*
             * On génère plusieurs variantes
             * du titre.
             */
            $titleVariants =
                $this->getTitleVariants($title);


            foreach (
                $titleVariants
                as $titleVariant
            ) {

                $releaseIds =
                    $this->findReleaseIds(
                        $artist,
                        $titleVariant
                    );


                foreach (
                    $releaseIds
                    as $releaseId
                ) {

                    $imageUrl =
                        $this->findCoverUrl(
                            $releaseId
                        );


                    if ($imageUrl === null) {
                        continue;
                    }


                    $this->downloadCover(
                        $imageUrl,
                        $cacheFile
                    );


                    if (is_file($cacheFile)) {

                        return
                            '/upload/covers/'
                            . $cacheKey
                            . '.jpg';
                    }
                }
            }

        } catch (\Throwable $e) {

            /*
             * Une erreur externe ne doit jamais
             * empêcher l'écran de fonctionner.
             */
        }


        return '/images/default-cover.jpg';
    }


    /**
     * Génère différentes variantes du titre.
     *
     * Exemple :
     *
     * Vivir Mi Vida (Official Remix Version Extended Dance Floor)
     *
     * devient :
     *
     * 1. Vivir Mi Vida (Official Remix Version Extended Dance Floor)
     * 2. Vivir Mi Vida
     */
    private function getTitleVariants(
        string $title
    ): array {

        $variants = [];


        /*
         * 1. Titre original
         */
        $variants[] =
            trim($title);


        /*
         * 2. Suppression de tout ce qui
         * commence par "("
         *
         * Vivir Mi Vida (Extended Remix)
         *
         * devient :
         *
         * Vivir Mi Vida
         */
        $withoutParentheses =
            preg_replace(
                '/\s*\(.*$/',
                '',
                $title
            );


        if (
            is_string($withoutParentheses)
            && trim($withoutParentheses) !== ''
        ) {

            $variants[] =
                trim($withoutParentheses);
        }


        /*
         * 3. Suppression des suffixes après " - "
         *
         * Vivir Mi Vida - Extended Remix
         *
         * devient :
         *
         * Vivir Mi Vida
         */
        $withoutDash =
            preg_replace(
                '/\s+-\s+.*$/',
                '',
                $title
            );


        if (
            is_string($withoutDash)
            && trim($withoutDash) !== ''
        ) {

            $variants[] =
                trim($withoutDash);
        }


        /*
         * Évite les doublons.
         */
        return array_values(
            array_unique(
                array_filter($variants)
            )
        );
    }


    /**
     * Recherche plusieurs releases correspondant
     * à l'artiste et au titre.
     */
    private function findReleaseIds(
        string $artist,
        string $title
    ): array {

        $response =
            $this->httpClient->request(
                'GET',
                self::MUSICBRAINZ_URL,
                [
                    'query' => [

                        'query' => sprintf(
                            'artist:"%s" AND recording:"%s"',
                            $artist,
                            $title
                        ),

                        'fmt' => 'json',

                        'limit' => 10,

                    ],

                    'headers' => [

                        'User-Agent' =>
                            self::USER_AGENT,

                        'Accept' =>
                            'application/json',

                    ],

                    'timeout' => 5,
                ]
            );


        if (
            $response->getStatusCode()
            !== 200
        ) {

            return [];
        }


        $data =
            $response->toArray(false);


        if (
            empty($data['recordings'])
        ) {

            return [];
        }


        $releaseIds = [];


        foreach (
            $data['recordings']
            as $recording
        ) {

            /*
             * Vérification du titre.
             */
            $recordingTitle =
                $recording['title']
                ?? '';


            if (
                $this->normalize(
                    $recordingTitle
                )
                !==
                $this->normalize(
                    $title
                )
            ) {

                continue;
            }


            /*
             * Vérification de l'artiste.
             */
            $artistMatches = false;


            foreach (
                $recording['artist-credit']
                ?? []
                as $artistCredit
            ) {

                $name =
                    $artistCredit['name']
                    ??
                    $artistCredit['artist']['name']
                    ??
                    '';


                if (
                    $this->normalize(
                        $name
                    )
                    ===
                    $this->normalize(
                        $artist
                    )
                ) {

                    $artistMatches = true;

                    break;
                }
            }


            if (!$artistMatches) {
                continue;
            }


            /*
             * Récupération des releases.
             */
            foreach (
                $recording['releases']
                ?? []
                as $release
            ) {

                if (
                    !empty(
                        $release['id']
                    )
                ) {

                    $releaseIds[] =
                        $release['id'];
                }
            }
        }


        return array_values(
            array_unique(
                $releaseIds
            )
        );
    }


    private function findCoverUrl(
        string $releaseId
    ): ?string {

        $response =
            $this->httpClient->request(
                'GET',
                self::COVER_ART_URL
                . '/'
                . $releaseId,
                [
                    'headers' => [

                        'User-Agent' =>
                            self::USER_AGENT,

                        'Accept' =>
                            'application/json',

                    ],

                    'timeout' => 5,
                ]
            );


        if (
            $response->getStatusCode()
            !== 200
        ) {

            return null;
        }


        $data =
            $response->toArray(false);


        if (
            empty($data['images'])
        ) {

            return null;
        }


        /*
         * Priorité à la couverture avant.
         */
        foreach (
            $data['images']
            as $image
        ) {

            if (
                ($image['front'] ?? false)
                &&
                !empty(
                    $image['thumbnails']['500']
                )
            ) {

                return
                    $image['thumbnails']['500'];
            }
        }


        /*
         * Sinon première image disponible.
         */
        foreach (
            $data['images']
            as $image
        ) {

            if (
                !empty(
                    $image['thumbnails']['500']
                )
            ) {

                return
                    $image['thumbnails']['500'];
            }


            if (
                !empty(
                    $image['image']
                )
            ) {

                return
                    $image['image'];
            }
        }


        return null;
    }


    private function downloadCover(
        string $url,
        string $destination
    ): void {

        $response =
            $this->httpClient->request(
                'GET',
                $url,
                [
                    'headers' => [

                        'User-Agent' =>
                            self::USER_AGENT,

                    ],

                    'timeout' => 10,
                ]
            );


        if (
            $response->getStatusCode()
            !== 200
        ) {

            return;
        }


        $content =
            $response->getContent();


        if (
            $content === ''
        ) {

            return;
        }


        file_put_contents(
            $destination,
            $content
        );
    }


    private function getCacheDirectory(): string
    {
        $directory =
            $this->projectDir
            . '/public/upload/covers';


        if (
            !is_dir($directory)
        ) {

            $filesystem =
                new Filesystem();


            $filesystem->mkdir(
                $directory
            );
        }


        return $directory;
    }


    private function getCacheKey(
        string $artist,
        string $title
    ): string {

        return md5(
            mb_strtolower(
                $artist
                . '|'
                . $title
            )
        );
    }


    private function normalize(
        string $value
    ): string {

        $value =
            mb_strtolower(
                trim($value)
            );


        $value =
            iconv(
                'UTF-8',
                'ASCII//TRANSLIT//IGNORE',
                $value
            )
            ?: $value;


        $value =
            preg_replace(
                '/[^a-z0-9]+/',
                ' ',
                $value
            );


        return trim($value);
    }
}