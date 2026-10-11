@props([
    'name' => 'date',
    'value' => '',
    'required' => false,
])

@php
    $englishDigits = static fn (string $input): string => strtr($input, [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ]);
    $parts = preg_split('/[\/-]/', $englishDigits((string) $value)) ?: [];
    $year = old($name.'_year', $parts[0] ?? '');
    $month = old($name.'_month', isset($parts[1]) ? ltrim($parts[1], '0') : '');
    $day = old($name.'_day', isset($parts[2]) ? ltrim($parts[2], '0') : '');
    $idPrefix = str_replace('_', '-', $name);
    $months = [
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
        5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
        9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
    ];
@endphp

<div class="grid grid-cols-3 gap-2" dir="rtl">
    <div>
        <label for="{{ $idPrefix }}-day" class="sr-only">روز</label>
        <select id="{{ $idPrefix }}-day" name="{{ $name }}_day" @required($required) class="{{ $attributes->get('class', 'public-select') }}">
            <option value="">روز</option>
            @for ($option = 1; $option <= 31; $option++)
                <option value="{{ $option }}" @selected((string) $day === (string) $option)>{{ \App\Support\PersianNumber::digits($option) }}</option>
            @endfor
        </select>
    </div>
    <div>
        <label for="{{ $idPrefix }}-month" class="sr-only">ماه</label>
        <select id="{{ $idPrefix }}-month" name="{{ $name }}_month" @required($required) class="{{ $attributes->get('class', 'public-select') }}">
            <option value="">ماه</option>
            @foreach ($months as $option => $label)
                <option value="{{ $option }}" @selected((string) $month === (string) $option)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="{{ $idPrefix }}-year" class="sr-only">سال</label>
        <select id="{{ $idPrefix }}-year" name="{{ $name }}_year" @required($required) class="{{ $attributes->get('class', 'public-select') }}">
            <option value="">سال</option>
            @for ($option = 1300; $option <= 1500; $option++)
                <option value="{{ $option }}" @selected((string) $year === (string) $option)>{{ \App\Support\PersianNumber::digits($option) }}</option>
            @endfor
        </select>
    </div>
</div>
