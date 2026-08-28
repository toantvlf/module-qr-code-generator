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
use TVTCommerce\QrCodeGenerator\Model\QrCode\PngGeneratorInterface;
use TVTCommerce\QrCodeGenerator\Model\QrCode\QrCodePageHtmlBuilder;
use TVTCommerce\QrCodeGenerator\Model\QrCode\SizeValidator;

/**
 * Renders the "Generate QR Code" result page for a single category: a base64 PNG <img> encoding
 * the category's storefront URL, plus a "Download PNG" link to Download::execute().
 *
 * ADMIN_RESOURCE reuses Magento_Catalog::categories — same reasoning as
 * Controller\Adminhtml\Product\Generate for Magento_Catalog::products.
 *
 * NOTE: CategoryRepositoryInterface::get() is declared to return CategoryInterface, which does
 * NOT expose getUrl() — only the concrete Magento\Catalog\Model\Category does. The shipped
 * Magento\Catalog\Model\CategoryRepository::get() always returns a Category instance (built via
 * CategoryFactory::create()->load()), so calling getUrl()/getName() on it is safe in practice.
 * This is a real gap in the service contract, not a modeling mistake here.
 */
class Generate extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_Catalog::categories';

    public function __construct(
        Action\Context $context,
        private readonly CategoryRepositoryInterface $categoryRepository,
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
        $base64Png = base64_encode($png);

        $downloadUrl = $this->getUrl('tvtqrcode/category/download', ['id' => $categoryId, 'size' => $size]);
        $title = (string) $category->getName();

        $html = $this->pageHtmlBuilder->build($title, $url, $base64Png, $downloadUrl);

        return $resultRaw
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setContents($html);
    }
}
