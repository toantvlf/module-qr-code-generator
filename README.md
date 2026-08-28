# TVTCommerce_QrCodeGenerator

Free, MIT-licensed Magento 2 admin extension from [TVT Commerce](https://tvtcommerce.com). Adds a
**"Generate QR Code"** button to the admin **Product edit** and **Category edit** pages. Clicking
it opens a small admin page showing a PNG QR code that encodes that product's or category's full
storefront URL, plus a **"Download PNG"** link — handy for merchants printing tags, flyers, or
POS materials that link straight to a specific product or category page.

## What it does

- **Product edit page**: a "Generate QR Code" button next to Save/Back. Encodes
  `Product::getProductUrl()`.
- **Category edit page**: the same button next to Save/Delete. Encodes `Category::getUrl()`.
- Both open a new tab showing the PNG rendered inline as a base64 `<img>`, with a "Download PNG"
  link that streams the same PNG as a `Content-Disposition: attachment`.
- The button only renders once the product/category has been saved (has an entity ID) — there is
  no storefront URL to encode for an unsaved "New Product" / "Add Root Category" screen.
- Optional `size` query parameter (pixels) on the generate/download URLs, clamped to
  **100–1000**, default **300** — see `Model\QrCode\SizeValidator`. No `system.xml` config
  section was added; this one clamped parameter didn't justify a new Stores > Configuration
  section (YAGNI).

## Which QR library this reuses from core — read this before assuming it's free

Magento core's own `Magento_TwoFactorAuth` module already generates a QR code, for the Google
Authenticator enrollment flow:

> `vendor/magento/module-two-factor-auth/Model/Provider/Engine/Google.php`, line ~219
> (`getQrCodeAsPng()`), verified against Magento 2.4.8 / `endroid/qr-code` 6.0.9:
> ```php
> $qrCode = new QrCode(
>     $this->getProvisioningUrl($user),
>     new Encoding('UTF-8'),
>     ErrorCorrectionLevel::High,
>     400,
>     0,
>     RoundBlockSizeMode::Margin,
>     new Color(0, 0, 0, 0),
>     new Color(255, 255, 255, 0)
> );
> $writer = new PngWriter();
> $pngData = $writer->write($qrCode);
> ```
> using `use Endroid\QrCode\QrCode;`, `Endroid\QrCode\Writer\PngWriter;`,
> `Endroid\QrCode\Color\Color;`, `Endroid\QrCode\Encoding\Encoding;`,
> `Endroid\QrCode\ErrorCorrectionLevel;`, and `Endroid\QrCode\RoundBlockSizeMode;`. `QrCode`,
> `Color`, and `Encoding` are `final readonly` value objects in 6.x — every value is passed
> positionally through the constructor, there are no fluent setters.

And `vendor/magento/module-two-factor-auth/composer.json` confirms the exact package/version:

```json
"require": {
    ...
    "endroid/qr-code": "^6.0.3",
    ...
}
```

`Model\QrCode\EndroidPngGenerator` in this module (the one place that talks to the third-party
library — see "Design" below) makes the identical `QrCode`/`PngWriter`/`Color`/`Encoding`/
`ErrorCorrectionLevel`/`RoundBlockSizeMode` calls, with the identical `Color(r, g, b, alpha)`
argument shape, for guaranteed API compatibility with whatever `endroid/qr-code` version ships
alongside `module-two-factor-auth` on a real store.

### Dependency risk — why this module requires `endroid/qr-code` directly instead of relying on core

A module's declared Composer dependency is not the same guarantee as "this package is installed
and loadable" — `Magento_TwoFactorAuth` can legitimately ship on a store (its module files exist)
while 2FA is disabled and/or its Composer dependencies were never actually pulled in for that
install, or a future Magento version could change/drop the library entirely without this module
knowing.

**Consequence**: this module's own `composer.json` requires `endroid/qr-code: ^6.0.3` directly
(see `require`), matching `module-two-factor-auth`'s own constraint exactly. Composer will
resolve/dedupe a single shared copy across both modules — this module does not end up with two
separate installs of the library, it just stops depending on `module-two-factor-auth` having
pulled its own dependency correctly. This module does **not** declare a hard `require` on
`magento/module-two-factor-auth` itself (no `<sequence>` entry, no composer require) — it only
reuses the same *library choice*, not the 2FA module.

**A prior version of `Model\QrCode\EndroidPngGenerator` called a fluent, mutable-setter API
(`$qrCode->setSize()`, `->setMargin()`, etc.) that only exists on `endroid/qr-code` 3.x/4.x — that
API does not exist on the 6.x releases `module-two-factor-auth` actually requires, so it fataled
with `Call to undefined method QrCode::setSize()` on every real Magento 2.4.8 install. Fixed by
matching `Google::getQrCodeAsPng()`'s real, verified constructor call exactly (see above) instead
of an assumed older API shape.**

## Design

- `Model\QrCode\PngGeneratorInterface` — one-method contract: `generate(string $data, int
  $sizePixels): string` (raw PNG bytes). The only seam anything in this module uses to reach the
  QR library. Wired to `EndroidPngGenerator` via a `<preference>` in `etc/di.xml` — without that
  entry the object manager cannot instantiate the interface at all.
- `Model\QrCode\EndroidPngGenerator` — the sole `endroid/qr-code` call site (see above).
- `Model\QrCode\SizeValidator` — clamps the optional `size` request parameter to [100, 1000].
  Framework-free; unit-tested.
- `Model\QrCode\FilenameSanitizer` — turns a product/category name into a safe
  `qr-code-<slug>.png` filename for the download's `Content-Disposition` header (defends against
  header injection from free-text admin-entered names). Framework-free; unit-tested.
- `Model\QrCode\QrCodePageHtmlBuilder` — builds the small standalone HTML page (base64 `<img>` +
  download link) shared by both the product and category "Generate" controllers. Deliberately not
  a layout/phtml block: this page has exactly one job and no reusable UI to justify layout
  XML/template/block machinery.
- `Controller\Adminhtml\Product\{Generate,Download}` and
  `Controller\Adminhtml\Category\{Generate,Download}` — four small GET controllers under the
  `tvtqrcode` admin route.
- `Block\Adminhtml\Product\Edit\Button\GenerateQrCode` — extends core's own
  `Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Generic` (the same base Save/Back/
  AddAttribute extend).
- `Block\Adminhtml\Category\Edit\Button\GenerateQrCode` — implements `ButtonProviderInterface`
  directly (Context + Registry only) rather than extending core's
  `Category\AbstractCategory` (which pulls in `Category\ResourceModel\Tree` and
  `CategoryFactory` constructor dependencies this button never uses).
- `view/adminhtml/ui_component/{product_form,category_form}.xml` — each merges one new
  `<button>` entry into core's own `<buttons>` list for that form. UI component XML files
  merge across modules by filename, exactly like layout XML — this is the standard,
  non-invasive way third-party modules add toolbar buttons to Product/Category edit, and no
  core file is overridden.

## ACL

Both `Generate` and `Download` controllers (product and category variants) declare
`ADMIN_RESOURCE`, reusing **existing** core resources rather than declaring new ones of this
module's own:

- Product controllers: `Magento_Catalog::products` (the same resource
  `catalog_product_new`/`catalog_product_edit`/save already require).
- Category controllers: `Magento_Catalog::categories`.

**Judgment call**: an admin who cannot already edit products has no legitimate reason to generate
a QR code for one — reusing the existing, narrower resource ties this endpoint's permission
directly to "can this admin already edit this entity type," rather than adding a new
ACL tree node purely for a one-button feature. Every admin controller in this module explicitly
declares `ADMIN_RESOURCE` (verified: neither `Generate` nor `Download` would be reachable by an
admin lacking that resource — `Magento\Backend\App\AbstractAction::_isAllowed()` checks it before
`execute()` runs).

## Requirements

- Magento 2.4.x, PHP 8.1+
- `endroid/qr-code` `^6.0.3` (declared directly in this module's `composer.json` — see
  "Dependency risk" above for why this is not left implicit via `module-two-factor-auth`)

## Installation

Copy (or symlink) this directory to `app/code/TVTCommerce/QrCodeGenerator`, then:

```
bin/magento module:enable TVTCommerce_QrCodeGenerator
bin/magento setup:upgrade
bin/magento cache:flush
```

## Testing

`Model\QrCode\SizeValidator` and `Model\QrCode\FilenameSanitizer` are the only pieces of pure,
Magento-independent logic in this module, and both are covered under `tests/Unit`, run via an
isolated PHPUnit environment (mirrors `module-email-otp-two-factor-auth/tests/` exactly):

```
cd tests
composer install
vendor/bin/phpunit -c phpunit.xml
```

`Model\QrCode\EndroidPngGenerator` (the `endroid/qr-code` call site) and the four admin
controllers are intentionally **not** unit-tested here: they require either the third-party QR
library or a real Magento object graph (repositories, `Escaper`, HTTP request/response,
`RawFactory`), neither of which is available in this isolated, dependency-free test environment.
`PngGeneratorInterface` exists specifically so `EndroidPngGenerator` could be swapped for a mock
in integration/functional tests inside a real Magento instance, if desired.

## License

MIT — see `composer.json`.
