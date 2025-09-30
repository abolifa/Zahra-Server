@php
    use App\Models\Setting;$app_name        = Setting::get_value('app_name') ?? 'eGrocer';
    $support_email   = Setting::get_value('support_email') ?? '';
    $support_number  = Setting::get_value('support_number') ?? '';
    $logo            = Setting::get_value('logo') ?? '';
    $logo_full_path  = $logo ? url('/').'/storage/'.$logo : asset('images/favicon.png');
    $currency        = Setting::get_value('currency') ?? '$';
    $website_url     = Setting::get_value('website_url') ?? '';
    $copyright       = Setting::get_value('copyright_details') ?? '';

    // Firebase (optional, safe only if restricted by domain in Firebase console)
    $firebase = [
        'apiKey'            => Setting::get_value('apiKey') ?? '',
        'authDomain'        => Setting::get_value('authDomain') ?? '',
        'projectId'         => Setting::get_value('projectId') ?? '',
        'storageBucket'     => Setting::get_value('storageBucket') ?? '',
        'messagingSenderId' => Setting::get_value('messagingSenderId') ?? '',
        'appId'             => Setting::get_value('appId') ?? '',
        'measurementId'     => Setting::get_value('measurementId') ?? '',
    ];
@endphp
    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>

    <title>{{ $app_name }}</title>
    <link rel="shortcut icon" href="{{ $logo_full_path }}">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Core styles -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/iconly/bold.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/perfect-scrollbar/perfect-scrollbar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/boostrap_vue.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/form-element-select.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom/common.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dark-mode/app-dark.css') }}">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
</head>
<body>
<div id="app">
    <router-view></router-view>
</div>

@if(auth()->user() && auth()->user()->role_id==1)
    @include('vendor.laraupdater.notification')
@endif

<!-- Vendor scripts -->
<script src="{{ asset('assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/mazer.js') }}"></script>
<script src="{{ asset('assets/js/extensions/form-element-select.js') }}"></script>

<!-- Global app config -->
<script>
    window.AppConfig = {
        baseUrl: '{{ url('/') }}',
        appName: "{{ $app_name }}",
        supportEmail: "{{ $support_email }}",
        supportNumber: "{{ $support_number }}",
        appLogo: "{{ $logo }}",
        currency: "{{ $currency }}",
        websiteUrl: "{{ $website_url }}",
        copyright: "{{ $copyright }}",
        isInstalled: {{ isInstalled() ? 'true' : 'false' }},
    };

    // Legacy globals so Vue code doesn’t crash
    window.baseUrl = window.AppConfig.baseUrl;
    window.appName = window.AppConfig.appName;
    window.appLogo = window.AppConfig.appLogo;
    window.currency = window.AppConfig.currency;
    window.supportEmail = window.AppConfig.supportEmail;
    window.supportNumber = window.AppConfig.supportNumber;
    window.websiteUrl = window.AppConfig.websiteUrl;
    window.copyrightDetails = window.AppConfig.copyright;
    window.isInstalled = window.AppConfig.isInstalled;
    window.isDemo = window.AppConfig.isDemo;
    window.currentVersion = window.AppConfig.currentVersion;
    window.deliveryBoyBonusSettings = window.AppConfig.deliveryBoyBonusSettings;

    @auth
        window.UserPermissions = {!! json_encode(Auth::user()->allPermissions, true) !!};
    window.Role = "{!! Auth::user()->role->name !!}";
    @else
        window.UserPermissions = [];
    window.Role = '';
    @endauth
</script>

<!-- Vue/JS app -->
<script src="{{ asset('js/app.js') }}"></script>

<!-- Firebase (only if properly restricted in Firebase console) -->
@if(!empty($firebase['apiKey']))
    <script src="https://www.gstatic.com/firebasejs/8.3.2/firebase.js"></script>
    <script>
        var firebaseConfig = @json($firebase);
        firebase.initializeApp(firebaseConfig);
    </script>
@endif

<!-- Language JSON -->
@php
    $langFile = resource_path('lang/' . (config('app.locale') ?? 'en') . '.json');
    $langJson = file_exists($langFile) ? file_get_contents($langFile) : '{}';
@endphp
<script>
    localStorage.setItem('language', JSON.stringify({!! $langJson !!}));
</script>
</body>
</html>
