<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CoverService
{
    private const MUSICBRAINZ_URL =
        'https://musicbrainz.org/ws/2/recording';

    private const MUSICBRAINZ_RELEASE_URL =
        'https://musicbrainz.org/ws/2/release';

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

    /**
     * Retourne l'URL locale de la pochette.
     *
     * En cas d'échec :
     * /images/default-cover.jpg
     */
    public function getCover(
        string $artist,
        string $title
    ): string {
        $artist = trim($artist);
        $title = trim($title);

        if ($artist === '' || $title === '') {
            return '/images/default-cover.jpg';
        }

        $cacheKey = $this->getCacheKey(
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
        if (is_file($cacheFile) && filesize($cacheFile) > 0) {
            return '/upload/covers/' . $cacheKey . '.jpg';
        }

        try {

            /*
             * On génère plusieurs variantes du titre.
             *
             * Exemple :
             *
             * Vivir Mi Vida (Official Remix Version Extended Dance Floor)
             *
             * ->
             * Vivir Mi Vida (Official Remix Version Extended Dance Floor)
             * Vivir Mi Vida
             */
            $titleVariants =
                $this->buildTitleVariants($title);

            foreach ($titleVariants as $titleVariant) {

                $releaseIds =
                    $this->findReleaseIds(
                        $artist,
                        $titleVariant
                    );

                if (empty($releaseIds)) {
                    continue;
                }

                /*
                 * On récupère les informations des releases
                 * afin de pouvoir les classer.
                 */
                $releases = [];

                foreach ($releaseIds as $releaseId) {

                    $release =
                        $this->getReleaseInfo(
                            $releaseId
                        );

                    if ($release === null) {
                        continue;
                    }

                    /*
                     * On ne garde que les releases
                     * qui correspondent raisonnablement
                     * à notre artiste.
                     */
                    if (
                        !$this->releaseMatchesArtist(
                            $release,
                            $artist
                        )
                    ) {
                        continue;
                    }

                    $score =
                        $this->scoreRelease(
                            $release,
                            $artist,
                            $titleVariant
                        );

                    $releases[] = [
                        'id' => $releaseId,
                        'score' => $score,
                        'release' => $release,
                    ];
                }

                if (empty($releases)) {
                    continue;
                }

                /*
                 * Meilleures releases en premier.
                 */
                usort(
                    $releases,
                    static function (
                        array $a,
                        array $b
                    ): int {
                        return
                            $b['score']
                            <=>
                            $a['score'];
                    }
                );

                /*
                 * On essaie les meilleures releases.
                 *
                 * On ne se limite pas forcément à la première :
                 * une bonne release peut ne pas avoir de pochette.
                 */
                foreach (
                    array_slice($releases, 0, 6)
                    as $candidate
                ) {

                    $imageUrl =
                        $this->findCoverUrl(
                            $candidate['id']
                        );

                    if ($imageUrl === null) {
                        continue;
                    }

                    $this->downloadCover(
                        $imageUrl,
                        $cacheFile
                    );

                    if (
                        is_file($cacheFile)
                        &&
                        filesize($cacheFile) > 0
                    ) {
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
             * empêcher l'écran Live de fonctionner.
             */
        }

        return '/images/default-cover.jpg';
    }


    /**
     * Génère différentes variantes d'un titre DJ.
     */
    private function buildTitleVariants(
        string $title
    ): array {

        $variants = [];

        /*
         * Titre original.
         */
        $variants[] = $title;

        /*
         * Supprime les informations entre parenthèses.
         *
         * Vivir Mi Vida
         * (Official Remix Version Extended Dance Floor)
         *
         * devient :
         *
         * Vivir Mi Vida
         */
        $withoutParentheses =
            preg_replace(
                '/\s*\([^)]*\)/u',
                '',
                $title
            );

        if (
            is_string($withoutParentheses)
            &&
            trim($withoutParentheses) !== ''
        ) {
            $variants[] =
                trim($withoutParentheses);
        }

        /*
         * Supprime également certaines indications
         * courantes des fichiers DJ.
         */
        $withoutDjSuffix =
            preg_replace(
                '/\s*[-–—]\s*(extended|remix|edit|radio edit|version|mix).*$/iu',
                '',
                $title
            );

        if (
            is_string($withoutDjSuffix)
            &&
            trim($withoutDjSuffix) !== ''
        ) {
            $variants[] =
                trim($withoutDjSuffix);
        }

        /*
         * Supprime les doublons.
         */
        $result = [];

        foreach ($variants as $variant) {

            $variant = trim($variant);

            if ($variant === '') {
                continue;
            }

            $key =
                $this->normalize($variant);

            if (isset($result[$key])) {
                continue;
            }

            $result[$key] = $variant;
        }

        return array_values($result);
    }


    /**
     * Recherche les releases correspondant
     * à un artiste et un titre.
     */
    private function findReleaseIds(
        string $artist,
        string $title
    ): array {

        try {

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
                !==
                200
            ) {
                return [];
            }

            $data =
                $response->toArray(false);

            if (
                empty(
                    $data['recordings']
                )
            ) {
                return [];
            }

            $releaseIds = [];

            foreach (
                $data['recordings']
                as $recording
            ) {

                $recordingTitle =
                    $recording['title'] ?? '';

                /*
                 * On accepte uniquement les titres
                 * correspondant réellement à notre recherche.
                 */
                if (
                    $this->normalize(
                        $recordingTitle
                    )
                    !==
                    $this->normalize($title)
                ) {
                    continue;
                }

                /*
                 * Vérification artiste.
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
                        $this->normalize($name)
                        ===
                        $this->normalize($artist)
                    ) {
                        $artistMatches = true;
                        break;
                    }
                }

                if (!$artistMatches) {
                    continue;
                }

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

        } catch (\Throwable) {

            return [];
        }
    }


    /**
     * Récupère les informations détaillées
     * d'une release MusicBrainz.
     */
    private function getReleaseInfo(
        string $releaseId
    ): ?array {

        try {

            $response =
                $this->httpClient->request(
                    'GET',
                    self::MUSICBRAINZ_RELEASE_URL
                    . '/'
                    . $releaseId,
                    [
                        'query' => [
                            'fmt' => 'json',
                            'inc' => 'artist-credits',
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
                !==
                200
            ) {
                return null;
            }

            return
                $response->toArray(false);

        } catch (\Throwable) {

            return null;
        }
    }


    /**
     * Vérifie que la release appartient bien
     * à l'artiste recherché.
     */
    private function releaseMatchesArtist(
        array $release,
        string $artist
    ): bool {

        $target =
            $this->normalize($artist);

        foreach (
            $release['artist-credit']
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
                $this->normalize($name)
                ===
                $target
            ) {
                return true;
            }
        }

        return false;
    }


    /**
     * Donne un score à une release.
     *
     * Plus le score est élevé,
     * plus elle est intéressante.
     */
    private function scoreRelease(
        array $release,
        string $artist,
        string $title
    ): int {

        $score = 0;

        $releaseTitle =
            $release['title'] ?? '';

        /*
         * Titre exactement identique.
         */
        if (
            $this->normalize($releaseTitle)
            ===
            $this->normalize($title)
        ) {
            $score += 100;
        }

        /*
         * Le titre recherché est contenu
         * dans le titre de la release.
         */
        elseif (
            str_contains(
                $this->normalize($releaseTitle),
                $this->normalize($title)
            )
        ) {
            $score += 60;
        }

        /*
         * Release officielle.
         */
        if (
            strtolower(
                $release['status'] ?? ''
            )
            ===
            'official'
        ) {
            $score += 40;
        }

        /*
         * Release sans statut étrange.
         */
        if (
            empty($release['status'])
        ) {
            $score += 5;
        }

        /*
         * Un album/single est généralement
         * plus pertinent qu'une compilation.
         *
         * On regarde le release-group.
         */
        $primaryType =
            strtolower(
                $release['release-group']
                ['primary-type']
                ?? ''
            );

        if (
            $primaryType === 'single'
        ) {
            $score += 35;
        }

        elseif (
            $primaryType === 'album'
        ) {
            $score += 25;
        }

        elseif (
            $primaryType === 'ep'
        ) {
            $score += 20;
        }

        elseif (
            $primaryType === 'compilation'
        ) {
            $score -= 40;
        }

        /*
         * Certaines releases de compilation/radio
         * peuvent contenir ces termes.
         */
        $releaseText =
            $this->normalize(
                $releaseTitle
                . ' '
                . ($release['packaging'] ?? '')
            );

        foreach (
            [
                'radio',
                'hits',
                'best of',
                'various artists',
                'compilation',
                'dance collection',
            ]
            as $badWord
        ) {

            if (
                str_contains(
                    $releaseText,
                    $badWord
                )
            ) {
                $score -= 30;
            }
        }

        /*
         * Une release récente n'est pas forcément
         * meilleure, donc on ne donne volontairement
         * pas de bonus énorme à la date.
         */
        if (
            !empty($release['date'])
        ) {
            $score += 2;
        }

        return $score;
    }


    /**
     * Cherche une vraie pochette avant.
     */
    private function findCoverUrl(
        string $releaseId
    ): ?string {

        try {

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
                !==
                200
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
             * Priorité :
             *
             * 1. front + approved + 500
             * 2. front + 500
             * 3. front
             * 4. première image 500
             */
            foreach (
                $data['images']
                as $image
            ) {

                if (
                    ($image['front'] ?? false)
                    &&
                    ($image['approved'] ?? false)
                    &&
                    !empty(
                        $image['thumbnails']['500']
                    )
                ) {
                    return
                        $image['thumbnails']['500'];
                }
            }

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

            foreach (
                $data['images']
                as $image
            ) {

                if (
                    ($image['front'] ?? false)
                    &&
                    !empty(
                        $image['image']
                    )
                ) {
                    return
                        $image['image'];
                }
            }

            /*
             * Dernier recours :
             * première image disponible.
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

        } catch (\Throwable) {

            return null;
        }

        return null;
    }


    /**
     * Télécharge la pochette localement.
     */
    private function downloadCover(
        string $url,
        string $destination
    ): void {

        try {

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
                !==
                200
            ) {
                return;
            }

            $content =
                $response->getContent();

            if ($content === '') {
                return;
            }

            file_put_contents(
                $destination,
                $content
            );

        } catch (\Throwable) {

            // On ignore les erreurs réseau.
        }
    }


    /**
     * Répertoire de cache.
     */
    private function getCacheDirectory(): string
    {
        $directory =
            $this->projectDir
            . '/public/upload/covers';

        if (!is_dir($directory)) {

            $filesystem =
                new Filesystem();

            $filesystem->mkdir(
                $directory
            );
        }

        return $directory;
    }


    /**
     * Génère une clé stable pour le cache.
     */
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


    /**
     * Normalisation pour comparer
     * les titres et artistes.
     */
    private function normalize(
        string $value
    ): string {

        $value =
            mb_strtolower(
                trim($value)
            );

        $converted =
            iconv(
                'UTF-8',
                'ASCII//TRANSLIT//IGNORE',
                $value
            );

        if (
            $converted !== false
        ) {
            $value = $converted;
        }

        $value =
            preg_replace(
                '/[^a-z0-9]+/',
                ' ',
                $value
            );

        return trim($value);
    }
}