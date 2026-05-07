<!-- Primary Meta Tags -->
@php
    $seoTitle = __('ui.seo.title');
    $seoDescription = __('ui.seo.description');
    $seoImageAlt = __('ui.seo.image_alt');
    $seoUrl = url('/');
    $seoImage = asset('seo-banner.jpg');
@endphp

<meta name="title" content="{{ $seoTitle }}">
<meta name="description"
    content="{{ $seoDescription }}">
<link rel="canonical" href="{{ $seoUrl }}">

<!-- Open Graph / Facebook / WhatsApp -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $seoTitle }}">
<meta property="og:url" content="{{ $seoUrl }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta property="og:image:secure_url" content="{{ $seoImage }}">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $seoImageAlt }}">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="{{ $seoUrl }}">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">
