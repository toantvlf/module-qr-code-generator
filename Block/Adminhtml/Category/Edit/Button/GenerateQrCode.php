<?php
declare(strict_types=1);

namespace TVTCommerce\QrCodeGenerator\Block\Adminhtml\Category\Edit\Button;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * "Generate QR Code" toolbar button on the admin Category edit page.
 *
 * Deliberately does NOT extend core's
 * Magento\Catalog\Block\Adminhtml\Category\AbstractCategory (the base core's own
 * Edit\SaveButton/DeleteButton extend) — that base pulls in Category\ResourceModel\Tree and
 * CategoryFactory constructor dependencies this button never uses, only to read the current
 * category out of the registry. A minimal direct implementation (same Context+Registry
 * dependencies core's Product\Edit\Button\Generic uses) avoids injecting unused collaborators.
 *
 * Wired into the toolbar via view/adminhtml/ui_component/category_form.xml, which merges a new
 * <button> entry into core's category_form.xml <buttons> list.
 */
class GenerateQrCode implements ButtonProviderInterface
{
    public function __construct(
        private readonly Context $context,
        private readonly Registry $registry
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getButtonData(): array
    {
        $category = $this->registry->registry('category');
        $categoryId = $category ? (int) $category->getId() : 0;

        // No storefront URL exists for a category that has not been saved yet (e.g. the "Add
        // Root Category" screen), so the button is only shown once an entity ID is available.
        if ($categoryId <= 0) {
            return [];
        }

        $url = $this->context->getUrl('tvtqrcode/category/generate', ['id' => $categoryId]);

        return [
            'label' => __('Generate QR Code'),
            'class' => 'action-secondary',
            'on_click' => sprintf("window.open('%s', '_blank');", $url),
            'sort_order' => 40,
        ];
    }
}
