<?php

namespace App\Service;

use App\Entity\Lien;
use App\Entity\User;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class QrCodeGenerator {
    private string $targetDirectory;
    private UrlGeneratorInterface $urlGenerator;
    private string $projectDir;
    private IdEncoderService $idEncoderService;

    public function __construct(KernelInterface $kernel, UrlGeneratorInterface $urlGenerator, IdEncoderService $idEncoderService)
    {
        $this->projectDir = $kernel->getProjectDir();
        $this->targetDirectory = $kernel->getProjectDir() . '/public/qrcodes/';
        if (!is_dir($this->targetDirectory)) {
            mkdir($this->targetDirectory, 0775, true);
        }
        $this->urlGenerator = $urlGenerator;
        $this->idEncoderService = $idEncoderService;
    }

    public function generateMembre(User $user): string
    {
        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $this->idEncoderService->encode($user->getId()),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 26,
            margin: 1,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );
        $result = $builder->build();

        $path = $this->targetDirectory . 'user_' . $user->getId() . '.png';
        $result->saveToFile($path);

        return 'qrcodes/user_' . $user->getId() . '.png';
    }

    public function generateLien(Lien $lien): string
    {
        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $this->urlGenerator->generate('app_lien_redirect', [
                'token' => $lien->getToken()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );
        $result = $builder->build();

        $path = $this->targetDirectory . $lien->getToken() . '.png';
        $result->saveToFile($path);

        return 'qrcodes/' . $lien->getToken() . '.png';
    }
}