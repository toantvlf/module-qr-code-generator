<?php
declare(strict_types=1);

namespace TVTCommerce\QrCodeGenerator\Block\Adminhtml\Product\Edit\Button;

use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Generic;

/**
 * "Generate QR Code" toolbar button on the admin Product edit page.
 *
 * Extends core's own Generic button base (the same base Save/Back/AddAttribute extend — see
 * vendor/magento/module-catalog/Block/Adminhtml/Product/Edit/Button/Generic.php) purely for its
 * getProduct()/getUrl() helpers; this class adds no behavior of its own beyond getButtonData().
 *
 * Wired into the toolbar via view/adminhtml/ui_component/product_form.xml, which merges a new
 * <button> entry into core's product_form.xml <buttons> list — the standard Magento UI
 * component extension point (view/adminhtml/ui_component/*.xml files with the same root <form>
 * name merge across modules, exactly like layout XML).
 */
class GenerateQrCode extends Generic
{
    /**
     * @inheritDoc
     */
    public function getButtonData(): array
    {
        $product = $this->getProduct();
        $productId = $product ? (int) $product->getId() : 0;

        // No storefront URL exists for a product that has not been saved yet, so the button is
        // only shown once an entity ID is available.
        if ($productId <= 0) {
            return [];
        }

        $url = $this->getUrl('tvtqrcode/product/generate', ['id' => $productId]);

        return [
            'label' => __('Generate QR Code'),
            'class' => 'action-secondary',
            'on_click' => sprintf("window.open('%s', '_blank');", $url),
            'sort_order' => 40,
        ];
    }
}
