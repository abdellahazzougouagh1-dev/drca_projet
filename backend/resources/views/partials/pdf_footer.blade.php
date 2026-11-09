@php
    $engine = $pdfEngine ?? 'dompdf';
    $footerBottomOffset = $footerBottom ?? '-84px';
    $footerData = \App\Support\PdfFooterHelper::viewData($engine);
    $wrapperPositionStyle = $engine === 'dompdf'
        ? "position: fixed; bottom: {$footerBottomOffset}; left: 0; right: 0; width: 100%;"
        : 'width: 100%;';
@endphp

@if($engine === 'dompdf')
<style>
    #bc-footer,
    #bc-footer * {
        box-sizing: border-box !important;
    }

    #bc-footer {
        direction: ltr !important;
        text-align: left !important;
        margin: 0 !important;
        background: transparent !important;
    }

    #bc-footer table.pdf-footer-grid,
    #bc-footer table.pdf-footer-divider {
        width: 100% !important;
        border-collapse: collapse !important;
        table-layout: auto !important;
        border: none !important;
        margin: 0 !important;
        padding: 0 !important;
        background: transparent !important;
    }

    #bc-footer table.pdf-footer-divider {
        width: 84% !important;
        margin: 2px auto !important;
    }

    #bc-footer table.pdf-footer-grid td,
    #bc-footer table.pdf-footer-grid th {
        border: none !important;
        background: transparent !important;
    }

    #bc-footer td.pdf-footer-divider-cell {
        border: none !important;
        border-top: 0.75px solid #94a3b8 !important;
        height: 2px !important;
        padding: 0 !important;
        font-size: 1px !important;
        line-height: 1px !important;
        background: transparent !important;
    }

    #bc-footer .pdf-footer-ar,
    #bc-footer .pdf-footer-ar * {
        font-family: "DejaVu Sans", sans-serif !important;
    }

    #bc-footer .pdf-footer-fr,
    #bc-footer .pdf-footer-fr * {
        font-family: "Times New Roman", Times, serif !important;
    }

    #bc-footer img.pdf-footer-logo {
        height: 60px !important;
        width: auto !important;
        max-height: 60px !important;
        max-width: none !important;
        border: none !important;
        margin: 0 !important;
        padding: 0 !important;
        display: inline-block !important;
        vertical-align: middle !important;
    }
</style>
@endif

<div id="bc-footer" class="pdf-footer" dir="ltr" style="{{ $wrapperPositionStyle }} border-top: 1.5px solid #000080; padding-top: 4px; direction: ltr; text-align: left;">
    <table class="pdf-footer-grid" dir="ltr" width="100%" style="width: 100%; border-collapse: collapse; margin: 0; padding: 0; border: none;">
        <tr>
            <td width="12%" style="width: 12%; text-align: left; vertical-align: middle; border: none; padding: 0;">
                @if(!empty($footerData['leftLogoSrc']))
                    <img class="pdf-footer-logo" src="{{ $footerData['leftLogoSrc'] }}" alt="Génération Green 2020-2030" style="height: 60px; width: auto; max-width: none; display: inline-block; vertical-align: middle; border: none; margin: 0; padding: 0;">
                @endif
            </td>
            <td width="73%" style="width: 73%; text-align: center; vertical-align: middle; border: none; padding: 0 4px;">
                <div class="pdf-footer-ar" dir="{{ $engine === 'mpdf' ? 'rtl' : 'ltr' }}" style="font-family: 'DejaVu Sans', sans-serif; font-size: {{ $engine === 'mpdf' ? '7.1pt' : '6.5pt' }}; color: #1a202c; line-height: 1.28; {{ $engine === 'mpdf' ? 'direction: rtl;' : 'white-space: nowrap;' }} text-align: center; margin: 0; padding: 0;">{!! $footerData['arLine1Html'] !!}</div>
                <div class="pdf-footer-ar" dir="{{ $engine === 'mpdf' ? 'rtl' : 'ltr' }}" style="font-family: 'DejaVu Sans', sans-serif; font-size: {{ $engine === 'mpdf' ? '7.1pt' : '6.7pt' }}; color: #1a202c; line-height: 1.28; {{ $engine === 'mpdf' ? 'direction: rtl;' : '' }} text-align: center; margin: 1px 0 0 0; padding: 0;">{!! $footerData['arLine2Html'] !!}</div>
                <table class="pdf-footer-divider" width="84%" style="width: 84%; margin: 2px auto; border-collapse: collapse; border: none;">
                    <tr>
                        <td class="pdf-footer-divider-cell" style="border: none; border-top: 0.75px solid #94a3b8; height: 2px; padding: 0; font-size: 1px; line-height: 1px;">&nbsp;</td>
                    </tr>
                </table>
                <div class="pdf-footer-fr" style="font-family: 'Times New Roman', Times, serif; font-size: 7.2pt; color: #1a202c; line-height: 1.22; text-align: center; margin: 0; padding: 0;">{{ $footerData['frLine1'] }}</div>
                <div class="pdf-footer-fr" style="font-family: 'Times New Roman', Times, serif; font-size: 7.2pt; color: #1a202c; line-height: 1.22; text-align: center; margin: 0; padding: 0;">{{ $footerData['frLine2'] }}</div>
                <div class="pdf-footer-fr" style="font-family: 'Times New Roman', Times, serif; font-size: 7.2pt; color: #1a202c; line-height: 1.22; text-align: center; margin: 1px 0 0 0; padding: 0;">{{ $footerData['frLine3'] }}</div>
            </td>
            <td width="15%" style="width: 15%; text-align: right; vertical-align: middle; border: none; padding: 0;">
                @if(!empty($footerData['rightLogoSrc']))
                    <img class="pdf-footer-logo" src="{{ $footerData['rightLogoSrc'] }}" alt="Ministère de l'Agriculture" style="height: 60px; width: auto; max-width: none; display: inline-block; vertical-align: middle; border: none; margin: 0; padding: 0;">
                @endif
            </td>
        </tr>
    </table>
</div>
