<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 13px;
            color: #000;
            line-height: 1.7;
            direction: rtl;
        }

        table.header-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-bottom: 12px;
        }

        table.header-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .header-center {
            text-align: center;
            direction: rtl;
        }

        .header-title {
            font-size: 14px;
            font-weight: bold;
            line-height: 1.5;
            text-align: center;
        }

        .header-stars {
            font-size: 11px;
            text-align: center;
            letter-spacing: 2px;
            margin-top: 2px;
        }

        .main-title-container {
            width: 100%;
            text-align: center;
            margin: 12px 0 16px 0;
        }

        .main-title {
            font-size: 15px;
            font-weight: bold;
            text-decoration: underline;
            line-height: 1.8;
        }

        .sub-title {
            font-size: 14px;
            font-weight: bold;
            margin-top: 4px;
        }

        .content-p {
            text-align: justify;
            font-size: 13px;
            line-height: 1.8;
            margin-bottom: 10px;
        }

        .url-center {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-decoration: underline;
            margin: 4px 0 10px 0;
            direction: ltr;
        }

        .list-box {
            margin-right: 20px;
            margin-bottom: 10px;
            font-size: 13px;
            line-height: 1.8;
        }

        .bold {
            font-weight: bold;
        }
    </style>
</head>
<body>

@php
    $logoOncaPath = public_path('images/logo-onca.png');
    $logoOncaSrc = file_exists($logoOncaPath) ? 'data:' . mime_content_type($logoOncaPath) . ';base64,' . base64_encode(file_get_contents($logoOncaPath)) : '';
    
    $sceauMarocPath = public_path('images/sceau-maroc.png');
    $sceauMarocSrc = file_exists($sceauMarocPath) ? 'data:' . mime_content_type($sceauMarocPath) . ';base64,' . base64_encode(file_get_contents($sceauMarocPath)) : '';

    $dateOuverture = '......../..../....';
    if (!empty($aoo->date_ouverture)) {
        try {
            $dateOuverture = \Carbon\Carbon::parse($aoo->date_ouverture)->format('Y/m/d');
        } catch (\Exception $e) {
            $dateOuverture = '......../..../....';
        }
    }

    $heureOuverture = '....';
    if (!empty($aoo->heure_ouverture)) {
        try {
            $heureOuverture = \Carbon\Carbon::parse($aoo->heure_ouverture)->format('H:i');
        } catch (\Exception $e) {
            $heureOuverture = '....';
        }
    }

    $lieuOuverture = $aoo->lieu_ouverture_ar
        ?: 'قاعة الاجتماعات بمقر المديرية الجهوية للاستشارة الفلاحية لجهة الرباط-سلا-القنيطرة الكائن مقرها بملتقى شارع محمد الخامس وزنقة سبتة القنيطرة';

    $lots = $aoo->lots ?? collect();
@endphp

<!-- EN-TÊTE OFFICIEL IDENTIQUE -->
<div class="header-table">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 15%; vertical-align: top; text-align: left;">
                @if($logoOncaSrc)
                    <img src="{{ $logoOncaSrc }}" height="110" style="display: block; margin-top: -15px;" alt="ONCA">
                @endif
            </td>
            <td style="width: 70%; vertical-align: middle; text-align: center; padding-top: 5px; direction: rtl;" class="header-title">
                <span style="font-size: 15px; font-weight: bold;">المملكة المغربية</span><br>
                <span style="font-size: 16px; font-weight: bold;">المكتب الوطني للاستشارة الفلاحية</span><br>
                <span style="font-size: 16px; font-weight: bold;">المديرية الجهوية للاستشارة الفلاحية لجهة الرباط-سلا-القنيطرة</span><br>
                <span style="font-size: 12px; letter-spacing: 2px;">********************</span>
            </td>
            <td style="width: 15%; vertical-align: top; text-align: right;">
                @if($sceauMarocSrc)
                    <img src="{{ $sceauMarocSrc }}" height="110" style="display: block; margin-left: auto; margin-top: -15px;" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="3" style="border-bottom: 2px solid #000; padding-top: 5px;"></td>
        </tr>
    </table>
</div>

<!-- TITRE PRINCIPAL -->
<div class="main-title-container">
    <div class="main-title">
        إعلان عن طلب عروض مفتوح وطني بعروض أثمان رقم {{ $aoo->num_aoo ?? '........' }}
    </div>
    <div class="sub-title">
        ( جلسة عمومية )
    </div>
</div>

<!-- PARAGRAPHE SÉANCE -->
<div class="content-p">
    في يوم {{ $dateOuverture }} على الساعة {{ $heureOuverture }} صباحا سيتم ب{{ $lieuOuverture }}، فتح الأظرفة المتعلقة بطلب عروض مفتوح وطني بعروض أثمان رقم {{ $aoo->num_aoo ?? '........' }}، يخص <span class="bold">{{ $aoo->objet_ar ?? $aoo->objet ?? '................................' }}</span>
    @if($lots->count() > 1)
        في <span class="bold">{{ $lots->count() == 2 ? 'حصتين' : $lots->count() . ' حصص' }}</span>:
    @else
        في حصة فريدة.
    @endif
</div>

@if($lots->count() > 1)
    <div class="list-box">
        @foreach($lots as $index => $lot)
            <div>
                <span class="bold">الحصة {{ $index + 1 }}:</span> {{ $lot->objet_lot_ar ?? $lot->objet_ar ?? $lot->objet ?? '................................' }}
            </div>
        @endforeach
    </div>
@endif

<!-- RETRAIT DOSSIER -->
<div class="content-p">
    يجب سحب ملف طلب العروض حصرا عبر تحميله إلكترونيا من بوابة الصفقات العمومية:
</div>

<div class="url-center">
    www.marchespublics.gov.ma
</div>

<!-- CAUTIONNEMENT PROVISOIRE -->
<div class="content-p">
    حدد مبلغ الضمان المؤقت في:
</div>

<div class="list-box">
    @if($lots->count() > 0)
        @foreach($lots as $index => $lot)
            @php
                $caution = (float)($lot->cautionnement_provisoire ?? 0);
            @endphp
            <div>
                @if($lots->count() > 1) - الحصة {{ $index + 1 }}: @else - @endif
                {{ \App\Helpers\NumberToWordsHelper::toArabicWords($caution) }} ({{ number_format($caution, 2, ',', ' ') }}) درهم.
            </div>
        @endforeach
    @else
        @php
            $caution = (float)($aoo->caution_provisoire ?? 0);
        @endphp
        <div>
            - {{ \App\Helpers\NumberToWordsHelper::toArabicWords($caution) }} ({{ number_format($caution, 2, ',', ' ') }}) درهم.
        </div>
    @endif
</div>

<!-- ESTIMATION -->
<div class="content-p">
    تقدير كلفة الأعمال المحددة من طرف صاحب المشروع في مبلغ:
</div>

<div class="list-box">
    @if($lots->count() > 0)
        @foreach($lots as $index => $lot)
            @php
                $montantTtc = (float)($lot->estimation ?? 0);
                if ($montantTtc <= 0 && isset($lot->items)) {
                    $totalHt = 0;
                    foreach ($lot->items as $item) {
                        $totalHt += (float)($item->quantite ?? 0) * (float)($item->prix_unitaire_ht ?? 0);
                    }
                    $montantTtc = $totalHt * 1.20;
                }
            @endphp
            <div>
                @if($lots->count() > 1) - الحصة {{ $index + 1 }}: @else - @endif
                {{ \App\Helpers\NumberToWordsHelper::toArabicWords($montantTtc) }} ({{ number_format($montantTtc, 2, ',', ' ') }}) درهم مع احتساب جميع الرسوم.
            </div>
        @endforeach
    @else
        @php
            $montantTtc = (float)($aoo->estimation_administrative ?? 0);
        @endphp
        <div>
            - {{ \App\Helpers\NumberToWordsHelper::toArabicWords($montantTtc) }} ({{ number_format($montantTtc, 2, ',', ' ') }}) درهم مع احتساب جميع الرسوم.
        </div>
    @endif
</div>

<!-- RÉFÉRENCES RÉGLEMENTAIRES -->
<div class="content-p">
    يجب أن يتوافق محتوى وتقديم وإيداع ملفات المتنافسين مع أحكام المواد من 30 إلى 34 و135 من المرسوم رقم 2-22-431 الصادر في 15 شعبان 1444 (8 مارس 2023) المتعلق بالصفقات العمومية كذلك المادتين 9 و12 من قرار الوزير المنتدب لدى وزيرة الاقتصاد والمالية المكلف بالميزانية رقم 1692-23 الصادر في 4 ذي الحجة 1444 (23 يونيو 2023) المتعلق بتجريد مساطر الصفقات العمومية والوثائق من الصفة المادية.
</div>

<!-- DÉPÔT ÉLECTRONIQUE -->
<div class="content-p">
    يتعين على المتنافسين تقديم ملفاتهم إلكترونيا على بوابة الصفقات العمومية المتاحة على الموقع <span style="direction: ltr; font-weight: bold;">www.marchespublics.gov.ma</span> وتقديم ضماناتهم المؤقتة إلكترونيا.
</div>

<!-- DOCUMENTS -->
<div class="content-p bold">
    إن الوثائق المثبتة الواجب الإدلاء بها هي تلك المنصوص عليها في المادة 8 و10 من نظام الاستشارة.
</div>

</body>
</html>