## 8. Especificación Técnica Responsive

### 8.1 CSS Custom Properties (Design Tokens)

```css

\:root {

  */\* Breakpoints \*/*

  --bp-xs: 320px;

  --bp-sm: 375px;

  --bp-md: 430px;

  --bp-lg: 768px;

  --bp-xl: 1024px;

  --bp-xxl: 1440px;

  */\* Spacing Scale \*/*

  --space-xs: 4px;

  --space-sm: 8px;

  --space-md: 16px;

  --space-lg: 24px;

  --space-xl: 32px;

  */\* Touch Targets \*/*

  --touch-target-min: 44px;

  --touch-target-comfortable: 48px;

  */\* Typography \*/*

  --font-size-xs: 0.75rem;    */\* 12px \*/*

  --font-size-sm: 0.875rem;   */\* 14px \*/*

  --font-size-base: 1rem;     */\* 16px \*/*

  --font-size-lg: 1.125rem;   */\* 18px \*/*

  --font-size-xl: 1.5rem;     */\* 24px \*/*

  --font-size-xxl: 2rem;      */\* 32px \*/*

  */\* Line Heights \*/*

  --line-height-tight: 1.25;

  --line-height-base: 1.5;

  --line-height-relaxed: 1.75;

  */\* Border Radius \*/*

  --radius-sm: 4px;

  --radius-md: 8px;

  --radius-lg: 12px;

  --radius-full: 9999px;

  */\* Shadows \*/*

  --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);

  --shadow-md: 0 4px 6px rgba(0,0,0,0.07);

  --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);

  */\* Z-Index Scale \*/*

  --z-dropdown: 100;

  --z-sticky: 200;

  --z-modal: 300;

  --z-toast: 400;

  --z-tooltip: 500;

}

```

### 8.2 Media Query Mixins (SCSS)

```scss

@mixin respond-to($breakpoint) {

  @if $breakpoint == xs {

    @media (max-width: 374px) { @content; }

  } @else if $breakpoint == sm {

    @media (min-width: 375px) and (max-width: 429px) { @content; }

  } @else if $breakpoint == md {

    @media (min-width: 430px) and (max-width: 767px) { @content; }

  } @else if $breakpoint == lg {

    @media (min-width: 768px) and (max-width: 1023px) { @content; }

  } @else if $breakpoint == xl {

    @media (min-width: 1024px) and (max-width: 1439px) { @content; }

  } @else if $breakpoint == xxl {

    @media (min-width: 1440px) { @content; }

  }

}

*// Uso*

.auth-form {

  padding: 16px;

  max-width: 100%;

  @include respond-to(sm) {

    padding: 24px;

    max-width: 400px;

    margin: 0 auto;

  }

  @include respond-to(lg) {

    padding: 32px;

    max-width: 440px;

  }

}

```

### 8.3 Container Queries (Futuro)

```css

*/\* Cuando soportado ampliamente \*/*

.auth-form {

  container-type: inline-size;

  container-name: auth-form;

}

@container auth-form (min-width: 400px) {

  .auth-form {

    padding: 32px;

    max-width: 440px;

    margin: 0 auto;

    border-radius: 16px;

    box-shadow: var(--shadow-lg);

  }

  .btn-primary {

    width: auto;

    min-width: 200px;

  }

}

```

---

## 9. Testing Matrix Mobile

| Test                  | Dispositivos                            | Navegadores             | Checklist                 |

|-----------------------|-----------------------------------------|-------------------------|---------------------------|

| **Unit Tests**        | -                                       | Chrome Headless         | 100% componentes          |

| **Visual Regression** | iPhone SE, 13, 14, 15, iPad, Galaxy S23 | Chrome, Safari, Firefox | Percy/Chromatic           |

| **Accessibility**     | NVDA, VoiceOver, TalkBack               | Chrome, Safari          | axe-core + manual         |

| **Performance**       | iPhone SE (throttled 3G)                | Chrome DevTools         | Lighthouse ≥ 90           |

| **Touch Testing**     | iPhone 13, iPad, Galaxy Tab             | Safari, Chrome          | Touch targets, gestures   |

| **Offline**           | Flight mode + throttled                 | Chrome DevTools         | Service Worker, Queue     |

| **Orientation**       | Portrait + Landscape                    | Todos                   | Lock/Unlock, layout shift |

---

## 10. Entregables Semana 10

| Archivo                 | Tipo     | Descripción                                                                                                                                      |

|-------------------------|----------|--------------------------------------------------------------------------------------------------------------------------------------------------|

| \`diseño-movilidad.md\`   | Markdown | **Este documento**: Estrategia mobile-first, breakpoints, jerarquización, navegación, formularios, tablas, confirmaciones, offline, 2 escenarios |

| \`breakpoints.css\`       | CSS      | Custom properties, media queries, container queries, touch targets                                                                               |

| \`mobile-navigation.tsx\` | TSX      | BottomNav, HamburgerMenu, TabBar components                                                                                                      |

| \`responsive-forms.tsx\`  | TSX      | LoginForm, AdminCreateUser (stepper), MFAChallenge responsive                                                                                    |

| \`responsive-tables.tsx\` | TSX      | UserList cards, AuditLogs cards responsive                                                                                                       |

| \`offline-strategy.ts\`   | TS       | Service Worker, Background Sync, Queue                                                                                                           |

| \`mobile-scenarios.md\`   | Markdown | 2 escenarios detallados con decisiones                                                                                                           |

| \`README.md\`             | Markdown | Índice y guía de uso                                                                                                                             |
