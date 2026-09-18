@php
    $footerBanner = \App\Support\AooDocumentHelper::embedImage('images/info_DRCA.png')
        ?? (file_exists(public_path('images/info_DRCA.png')) ? 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/info_DRCA.png'))) : null);
@endphp

<div id="bc-footer" style="
    position: fixed;
    bottom: -32px;
    left: 0;
    right: 0;
    width: 100%;
    border-top: 1.5px solid #101011e6;
    padding-top: 4px;
    text-align: center;
">
<<<<<<< HEAD
    @if($footerBanner)
        <img src="{{ $footerBanner }}" style="width: 100%; height: auto; max-height: 52px; display: block;" alt="ONCA DRCA">
    @endif
=======
    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
        <tr>
            <td style="width: 40%; vertical-align: middle; text-align: left;">
                @if($logoOnca)
                    <img src="{{ $logoOnca }}" alt="Logo ONCA" class="header-logo" style="max-height: 60px; height: 60px; width: auto;">
                @endif
            </td>

            <td style="width: 30%; vertical-align: middle; text-align: center;">
            </td>

            <td style="width: 40%; vertical-align: middle; text-align: right;">
                @if($sceauMaroc)
                    <img src="{{ $sceauMaroc }}" alt="Sceau Maroc" class="header-logo" style="max-height: 70px; height: 55px; width: auto;">
                @endif
            </td>
        </tr>
    </table>
>>>>>>> a83e21666c1ccf5615f3290e03345bb6041685a7
</div>

