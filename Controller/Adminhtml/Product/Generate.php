<?php
declare(strict_types=1);

namespace TVTCommerce\QrCodeGenerator\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use TVTCommerce\QrCodeGenerator\Model\QrCode\PngGeneratorInterface;
use TVTCommerce\QrCodeGenerator\Model\QrCode\QrCodePageHtmlBuilder;
use TVTCommerce\QrCodeGenerator\Model\QrCode\SizeValidator;

/**
 * Renders the "Generate QR Code" result page for a single product: a base64 PNG <img> encoding
 * the product's storefront URL, plus a "Download PNG" link to Download::execute().
 *
 * ADMIN_RESOURCE deliberately reuses Magento_Catalog::products (the same ACL resource the core
 * product edit/save controllers already require) rather than declaring a new resource of this
 * module's own — an admin who cannot already edit products has no legitimate reason to generate
 * a QR code for one, so piggybacking on that existing permission is the correct, minimal-surface
 * check (see README "ACL" section for the full reasoning).
 */
class Generate extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_Catalog::products';

    public function __construct(
        Action\Context $context,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly PngGeneratorInterface $pngGenerator,
        private readonly SizeValidator $sizeValidator,
        private readonly QrCodePageHtmlBuilder $pageHtmlBuilder,
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

        $productId = (int) $this->getRequest()->getParam('id');

        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return $resultRaw
                ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
                ->setHttpResponseCode(404)
                ->setContents('Product not found.');
        }

        $rawSize = $this->getRequest()->getParam('size');
        $size = $this->sizeValidator->validate($rawSize !== null ? (int) $rawSize : null);

        $url = (string) $product->getProductUrl();
        $png = $this->pngGenerator->generate($url, $size);
        $base64Png = base64_encode($png);

        $downloadUrl = $this->getUrl('tvtqrcode/product/download', ['id' => $productId, 'size' => $size]);
        $title = (string) ($product->getName() ?: $product->getSku());

        $html = $this->pageHtmlBuilder->build($title, $url, $base64Png, $downloadUrl);

        return $resultRaw
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setContents($html);
    }
}
