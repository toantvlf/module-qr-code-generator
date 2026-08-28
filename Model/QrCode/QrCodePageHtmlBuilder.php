<?php
declare(strict_types=1);

namespace TVTCommerce\QrCodeGenerator\Model\QrCode;

use Magento\Framework\Escaper;

/**
 * Builds the small, self-contained admin HTML page shown when a "Generate QR Code" button is
 * clicked: the PNG rendered inline as a base64 data URI <img>, plus a "Download PNG" link.
 *
 * Deliberately standalone (not a layout/phtml block) — this page has exactly one job and no
 * reusable UI to justify the layout XML/template/block machinery a full admin page would need.
 * Shared between Controller\Adminhtml\Product\Generate and Controller\Adminhtml\Category\Generate
 * since both need the identical markup, only the title/url/download link differ.
 */
class QrCodePageHtmlBuilder
{
    public function __construct(
        private readonly Escaper $escaper
    ) {
    }

    /**
     * @param string $title       Product/category name shown as the page heading.
     * @param string $url         The storefront URL that was encoded into the QR code.
     * @param string $base64Png   Base64-encoded PNG bytes (no data: URI prefix).
     * @param string $downloadUrl Admin controller URL that streams the PNG as an attachment.
     */
    public function build(string $title, string $url, string $base64Png, string $downloadUrl): string
    {
        $escapedTitle = $this->escaper->escapeHtml($title);
        $escapedUrl = $this->escaper->escapeHtml($url);
        $escapedDownloadUrl = $this->escaper->escapeUrl($downloadUrl);

        return <<<HTML
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>QR Code - {$escapedTitle}</title>
<style>
body { font-family: Arial, Helvetica, sans-serif; background: #f4f4f4; color: #333; margin: 0; padding: 32px; }
.qr-card { max-width: 420px; margin: 0 auto; background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 24px; text-align: center; }
.qr-card img { max-width: 100%; height: auto; border: 1px solid #eee; }
.qr-card h1 { font-size: 16px; margin: 0 0 16px; word-break: break-word; }
.qr-card .qr-url { font-size: 12px; color: #767676; word-break: break-all; margin: 12px 0; }
.qr-card a.download { display: inline-block; margin-top: 16px; padding: 8px 16px; background: #eb5202; color: #fff; text-decoration: none; border-radius: 3px; font-size: 13px; }
</style>
</head>
<body>
<div class="qr-card">
<h1>{$escapedTitle}</h1>
<img src="data:image/png;base64,{$base64Png}" alt="QR code for {$escapedTitle}">
<div class="qr-url">{$escapedUrl}</div>
<a class="download" href="{$escapedDownloadUrl}" download>Download PNG</a>
</div>
</body>
</html>
HTML;
    }
}
