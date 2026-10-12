@php
    $logoOnca = \App\Support\AooDocumentHelper::embedImage('images/logo-onca.png')
        ?? \App\Support\AooDocumentHelper::embedImage('images/logo_onca.png');

    $sceauMaroc = \App\Support\AooDocumentHelper::embedImage('images/sceau-maroc.png')
        ?? \App\Support\AooDocumentHelper::embedImage('images/logo_royaume.png');
@endphp

<div id="bc-header" style="
    position: fixed;
    top: -95px;
    left: 0;
    right: 0;
    width: 100%;
">
    <table class="header-table" style="width: 100%; border-collapse: collapse; margin-bottom: 4px;">
        <tr>
            <td style="width: 20%; vertical-align: middle;">
                @if($logoOnca)
                    <img src="{{ $logoOnca }}" alt="Logo ONCA" class="header-logo" style="max-height: 80px; height: 80px; width: auto;">
                @endif
            </td>

            <td class="header-center" style="width: 52%; text-align: center; font-size: 12px; font-weight: bold; vertical-align: middle; line-height: 1.3;">
                Direction Régionale du Conseil Agricole<br>
                Rabat-Salé-Kénitra
            </td>

            <td style="width: 20%; text-align: right; vertical-align: middle;">
                @if($sceauMaroc)
                    <img src="{{ $sceauMaroc }}" alt="Sceau Maroc" class="header-logo" style="max-height: 80px; height: 80px; width: auto;">
                @endif
            </td>
        </tr>
    </table>

    <div class="header-line" style="border-bottom: 1.5px solid #101011e6; margin-bottom: 14px;"></div>
</div>