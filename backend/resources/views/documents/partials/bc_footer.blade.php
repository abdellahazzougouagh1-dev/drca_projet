@php
    $logoOnca = \App\Support\AooDocumentHelper::embedImage('images/Generation Green.png');

    $sceauMaroc = \App\Support\AooDocumentHelper::embedImage('images/info_DRCA.png')
@endphp

<div id="bc-footer" style="
    position: fixed;
    bottom: -35px;
    left: 0;
    right: 0;
    width: 100%;
    border-top: 1.5px solid #101011e6;
    padding-top: 6px;
">
    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
        <tr>
            <td style="width: 40%; vertical-align: middle; text-align: left;">
                @if($logoOnca)
                    <img src="{{ $logoOnca }}" alt="Logo ONCA" class="header-logo" style="max-height: 60px; height: 60px; width: auto;">
                @endif
            </td>

            <td style="width: 30%; vertical-align: middle; text-align: center;">
            </td>

            <td style="width: 30%; vertical-align: middle; text-align: right;">
                @if($sceauMaroc)
                    <img src="{{ $sceauMaroc }}" alt="Sceau Maroc" class="header-logo" style="max-height: 55px; height: 55px; width: auto;">
                @endif
            </td>
        </tr>
    </table>
</div>
