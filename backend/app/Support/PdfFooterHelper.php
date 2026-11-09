<?php

namespace App\Support;

use App\Helpers\ArabicGlyphs;

class PdfFooterHelper
{
    public const AR_ADDRESS = 'المديرية الجهوية للاستشارة الفلاحية لجهة الرباط سلا القنيطرة، ملتقى زنقة سبتة، شارع محمد الخامس (قرب بنك المغرب) - القنيطرة';
    public const AR_PHONE_LABEL = 'الهاتف :';
    public const AR_FAX_LABEL = 'الفاكس :';
    public const AR_WEB_LABEL = 'الموقع الإلكتروني :';

    public const FR_LINE_1 = 'Direction Régionale du Conseil Agricole de la Région de Rabat Salé Kénitra';
    public const FR_LINE_2 = 'Angle Rue Sebta - Bd Mohamed V (à côté de Bank Al-Maghreb) - Kénitra';
    public const FR_LINE_3 = 'Tél. : +212 (0) 537 32 55 99 - Fax : +212 (0) 537 36 13 20 - Site web : www.onca.gov.ma';

    public const PHONE_DISPLAY = '+212 (0) 537 32 55 99';
    public const FAX_DISPLAY = '+212 (0) 537 36 13 20';
    public const WEBSITE_DISPLAY = 'www.onca.gov.ma';

    public static function logos(): array
    {
        $ggCandidates = extension_loaded('gd')
            ? ['images/Generation Green.png', 'images/Generation Green.jpg']
            : ['images/Generation Green.jpg', 'images/Generation Green.png'];

        $leftLogo = null;
        foreach ($ggCandidates as $candidate) {
            $leftLogo = AooDocumentHelper::embedImage($candidate);
            if ($leftLogo) {
                break;
            }
        }

        $rightLogo = null;
        foreach (['images/sceau-maroc.png', 'images/sceau-maroc.jpg'] as $candidate) {
            $rightLogo = AooDocumentHelper::embedImage($candidate);
            if ($rightLogo) {
                break;
            }
        }

        return [
            'left' => $leftLogo,
            'right' => $rightLogo,
        ];
    }

    public static function viewData(string $engine = 'dompdf'): array
    {
        $logos = self::logos();

        if ($engine === 'mpdf') {
            $arLine1Html = e(self::AR_ADDRESS);
            $arLine2Html = e(self::AR_PHONE_LABEL)
                . ' &#x202A;' . e(self::PHONE_DISPLAY) . '&#x202C; - '
                . e(self::AR_FAX_LABEL)
                . ' &#x202A;' . e(self::FAX_DISPLAY) . '&#x202C; - '
                . e(self::AR_WEB_LABEL)
                . ' &#x202A;' . e(self::WEBSITE_DISPLAY) . '&#x202C;';
        } else {
            $arLine1Html = e(self::shapeArabic(self::AR_ADDRESS));
            // Visual LTR order for DomPDF right-to-left reading
            $arLine2Html = implode(' ', [
                '<span dir="ltr">' . e(self::WEBSITE_DISPLAY) . '</span>',
                e(self::shapeArabic(self::AR_WEB_LABEL)),
                '-',
                '<span dir="ltr">' . e(self::FAX_DISPLAY) . '</span>',
                e(self::shapeArabic(self::AR_FAX_LABEL)),
                '-',
                '<span dir="ltr">' . e(self::PHONE_DISPLAY) . '</span>',
                e(self::shapeArabic(self::AR_PHONE_LABEL)),
            ]);
        }

        return [
            'pdfEngine' => $engine,
            'leftLogoSrc' => $logos['left'],
            'rightLogoSrc' => $logos['right'],
            'arLine1Html' => $arLine1Html,
            'arLine2Html' => $arLine2Html,
            'frLine1' => self::FR_LINE_1,
            'frLine2' => self::FR_LINE_2,
            'frLine3' => self::FR_LINE_3,
        ];
    }

    public static function mpdfHtml(): string
    {
        return view('partials.pdf_footer', ['pdfEngine' => 'mpdf'])->render();
    }

    private static function shapeArabic(string $phrase): string
    {
        static $arabic = null;

        if ($arabic === null) {
            class_exists(ArabicGlyphs::class);
            $arabic = new \I18N_Arabic('Glyphs');
        }

        return $arabic->utf8Glyphs($phrase, 500, false);
    }
}
