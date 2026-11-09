@php
    $logoOnca = \App\Support\AooDocumentHelper::embedImage('images/logo-onca.png')
        ?? \App\Support\AooDocumentHelper::embedImage('images/logo_onca.png');

    $sceauMaroc = \App\Support\AooDocumentHelper::embedImage('images/sceau-maroc.png')
        ?? \App\Support\AooDocumentHelper::embedImage('images/logo_royaume.png');
@endphp

<div id="bc-header" style="
    position: fixed;
    top: -88px;
    left: 0;
    right: 0;
    width: 100%;
">
    <table class="header-table" style="width: 100%; border-collapse: collapse; margin-bottom: 5px;">
        <tr>
            <td style="width: 24%; text-align: left; vertical-align: middle; border: none; padding: 0;">
                @if($logoOnca)
                    <img src="{{ $logoOnca }}" alt="Logo ONCA" class="header-logo" style="height: 70px; width: auto;">
                @endif
            </td>

            <td class="header-center" style="width: 52%; text-align: center; font-size: 13.5px; font-weight: bold; vertical-align: middle; line-height: 1.35; padding: 0 8px; border: none; color: #000;">
                Direction Régionale du Conseil Agricole<br>
                Rabat-Salé-Kénitra
            </td>

            <td style="width: 24%; text-align: right; vertical-align: middle; border: none; padding: 0;">
                @if($sceauMaroc)
                    <img src="{{ $sceauMaroc }}" alt="Sceau Maroc" class="header-logo" style="height: 70px; width: auto;">
                @endif
            </td>
        </tr>
    </table>

    <div class="header-line" style="border-bottom: 2px solid #000080; margin-bottom: 12px;"></div>
</div>
