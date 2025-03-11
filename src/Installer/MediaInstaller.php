<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Installer;

use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderCollection;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use TopiPaymentIntegration\TopiPaymentIntegrationPlugin;

readonly class MediaInstaller
{
    private const PAYMENT_METHOD_MEDIA_FILE = 'src/Resources/config/payment-badge.svg';
    private const SAVED_MEDIA_FILENAME = 'topi_payment_integration_payment-badge';

    private EntityRepository $mediaRepository;

    private EntityRepository $mediaFolderRepository;

    private EntityRepository $paymentMethodRepository;

    private FileSaver $fileSaver;

    /**
     * @param EntityRepository<MediaCollection>         $mediaRepository
     * @param EntityRepository<MediaFolderCollection>   $mediaFolderRepository
     * @param EntityRepository<PaymentMethodCollection> $paymentMethodRepository
     */
    public function __construct(
        EntityRepository $mediaRepository,
        EntityRepository $mediaFolderRepository,
        EntityRepository $paymentMethodRepository,
        FileSaver $fileSaver,
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->mediaFolderRepository = $mediaFolderRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->fileSaver = $fileSaver;
    }

    public function installPaymentMethodMedia(string $paymentMethodId, Context $context, bool $replace = false): void
    {
        $criteria = new Criteria([$paymentMethodId]);
        $criteria->addAssociation('media');
        /** @var PaymentMethodEntity|null $paymentMethod */
        $paymentMethod = $this->paymentMethodRepository->search($criteria, $context)->first();
        if (null === $paymentMethod) {
            throw PaymentException::unknownPaymentMethodById($paymentMethodId);
        }

        if (!$replace && $paymentMethod->getMediaId()) {
            return;
        }

        $mediaFile = $this->getMediaFile();
        $savedFileName = \sprintf(self::SAVED_MEDIA_FILENAME);

        $this->fileSaver->persistFileToMedia(
            $mediaFile,
            $savedFileName,
            $this->getMediaId($savedFileName, $paymentMethod, $context),
            $context
        );
    }

    private function getMediaDefaultFolderId(Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('media_folder.defaultFolder.entity', $this->paymentMethodRepository->getDefinition()->getEntityName()));
        $criteria->addAssociation('defaultFolder');
        $criteria->setLimit(1);

        return $this->mediaFolderRepository->searchIds($criteria, $context)->firstId();
    }

    private function getMediaId(string $fileName, PaymentMethodEntity $paymentMethod, Context $context): string
    {
        $media = $paymentMethod->getMedia();
        if (null !== $media && $media->getFileName() === $fileName) {
            return $media->getId();
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('fileName', $fileName));
        $mediaId = $this->mediaRepository->searchIds($criteria, $context)->firstId();

        if (null === $mediaId) {
            $mediaId = Uuid::randomHex();
        }

        $this->paymentMethodRepository->update(
            [[
                'id' => $paymentMethod->getId(),
                'media' => [
                    'id' => $mediaId,
                    'mediaFolderId' => $this->getMediaDefaultFolderId($context),
                ],
            ]],
            $context
        );

        return $mediaId;
    }

    private function getMediaFile(): MediaFile
    {
        $filePath = \sprintf('%s/%s', TopiPaymentIntegrationPlugin::getPluginDir(), self::PAYMENT_METHOD_MEDIA_FILE);

        return new MediaFile(
            $filePath,
            \mime_content_type($filePath) ?: '',
            \pathinfo($filePath, \PATHINFO_EXTENSION),
            \filesize($filePath) ?: 0
        );
    }
}
