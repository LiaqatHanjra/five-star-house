<template>
  <footer class="w-100 bg-dark border-top position-relative overflow-hidden">
    <div class="position-absolute start-0 end-0 bottom-0 pe-none opacity-15 d-flex align-items-end justify-content-center user-select-none overflow-hidden h-40">
      <svg class="footer-skyline w-100 h-100" preserveAspectRatio="none" viewBox="0 0 1200 160" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M0,160 L0,110 L30,110 L30,85 L45,85 L45,110 L80,110 L80,70 L95,70 L95,110 L130,110 L130,50 L140,50 L140,40 L145,40 L145,50 L170,50 L170,110 L210,110 L210,95 L235,95 L235,65 L260,65 L260,110 L310,110 L310,30 L320,30 L320,10 L323,10 L323,30 L345,30 L345,110 L390,110 L390,80 L420,80 L420,110 L470,110 L470,55 L485,55 L485,45 L500,45 L500,55 L530,55 L530,110 L580,110 L580,75 L615,75 L615,110 L660,110 L660,35 L685,35 L685,110 L730,110 L730,90 L760,90 L760,110 L800,110 L800,60 L830,60 L830,110 L880,110 L880,45 L895,45 L895,20 L898,20 L898,45 L925,45 L925,110 L970,110 L970,75 L1010,75 L1010,110 L1060,110 L1060,50 L1090,50 L1090,110 L1140,110 L1140,85 L1170,85 L1170,110 L1200,110 L1200,160 Z"></path>
      </svg>
    </div>
    <div class="position-relative z-1 site-wrap py-2xl d-flex flex-column flex-md-row align-items-start align-items-md-end justify-content-between gap-xl">
      <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-center gap-2">
          <span class="sq-3 bg-danger d-inline-block" aria-hidden="true"></span>
          <span class="type-headline-sm text-uppercase ls-wider">FIVE STAR HOUSE</span>
        </div>
        <p class="type-overline text-uppercase ls-20 text-primary">FILM • PHOTO • CREATIVE PRODUCTION</p>
      </div>
      <div class="d-flex flex-column align-items-md-end gap-1">
        <p class="type-body-sm text-body-secondary ls-wider text-uppercase">MONTREAL, CANADA — © 2026 FIVE STAR HOUSE</p>
        <p class="type-label-sm text-body-tertiary text-uppercase ls-widest">
          45.5017° N, 73.5673° W • STUDIO REF: 5SH-YUL
          <RouterLink class="ms-2 hover-primary" to="/admin/login">ADMIN</RouterLink>
        </p>
      </div>
    </div>
  </footer>
</template>
