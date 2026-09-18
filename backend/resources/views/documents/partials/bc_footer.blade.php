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
    @if($footerBanner)
        <img src="{{ $footerBanner }}" style="width: 100%; height: auto; max-height: 52px; display: block;" alt="ONCA DRCA">
    @endif
</div>

