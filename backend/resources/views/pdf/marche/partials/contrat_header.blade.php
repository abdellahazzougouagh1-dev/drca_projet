@php
    $logoOnca = \App\Support\AooDocumentHelper::embedImage('images/logo-onca.png')
        ?? \App\Support\AooDocumentHelper::embedImage('images/logo_onca.png');
    $sceauMaroc = \App\Support\AooDocumentHelper::embedImage('images/sceau-maroc.png')
        ?? \App\Support\AooDocumentHelper::embedImage('images/logo_royaume.png');
@endphp

<table class="header-table">
    <tr>
        <td class="header-left">
            @if($logoOnca)
                <img src="{{ $logoOnca }}" alt="ONCA" class="header-logo">
            @endif
        </td>
        <td class="header-center">
            Direction Régionale du Conseil Agricole<br>
            Rabat-Salé-Kénitra
        </td>
        <td class="header-right">
            @if($sceauMaroc)
                <img src="{{ $sceauMaroc }}" alt="Royaume du Maroc" class="header-logo">
            @endif
        </td>
    </tr>
</table>
