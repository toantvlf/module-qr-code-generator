<?php
declare(strict_types=1);

namespace TVTCommerce\QrCodeGenerator\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use TVTCommerce\QrCodeGenerator\Model\QrCode\FilenameSanitizer;
use TVTCommerce\QrCodeGenerator\Model\QrCode\PngGeneratorInterface;
use TVTCommerce\QrCodeGenerator\Model\QrCode\SizeValidator;

/**
 * Streams the category QR code PNG as a file attachment (the "Download PNG" link target from
 * Generate::execute()'s result page).
 *
 * Same ADMIN_RESOURCE reasoning and CategoryRepositoryInterface caveat as Generate — see that
 * class's docblock.
 */
class Download extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_Catalog::categories';

    public function __construct(
        Action\Context $context,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly PngGeneratorInterface $pngGenerator,
        private readonly SizeValidator $sizeValidator,
        private readonly FilenameSanitizer $filenameSanitizer,
        private readonly RawFactory $resultRawFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute(): Raw
    {
        /** @var Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();

        $categoryId = (int) $this->getRequest()->getParam('id');

        try {
            /** @var Category $category */
            $category = $this->categoryRepository->get($categoryId);
        } catch (NoSuchEntityException $e) {
            return $resultRaw
                ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
                ->setHttpResponseCode(404)
                ->setContents('Category not found.');
        }

        $rawSize = $this->getRequest()->getParam('size');
        $size = $this->sizeValidator->validate($rawSize !== null ? (int) $rawSize : null);

        $url = (string) $category->getUrl();
        $png = $this->pngGenerator->generate($url, $size);

        $filename = $this->filenameSanitizer->toPngFilename((string) $category->getName());

        return $resultRaw
            ->setHeader('Content-Type', 'image/png')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setContents($png);
    }
}
