{{-- Brand typefaces, self-hosted from public/fonts (see resources/css/fonts.css). The display face is preloaded; nothing is fetched from a third party. --}}
<link rel="preload" href="{{ asset(\App\Constants\Typography::path(\App\Constants\Typography::PRELOAD_FILE)) }}" as="font" type="font/woff2" crossorigin>
