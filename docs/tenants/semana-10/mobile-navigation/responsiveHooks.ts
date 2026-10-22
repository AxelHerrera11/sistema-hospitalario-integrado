import { useEffect, useState } from 'react';

type ElementRef<T extends HTMLElement> = { current: T | null };

const FOCUSABLE_SELECTOR =
  'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

export function useFocusTrap(
  isActive: boolean,
  containerRef: ElementRef<HTMLElement>,
): void {
  useEffect(() => {
    const container = containerRef.current;
    if (!isActive || !container) return;

    const getFocusable = () =>
      Array.from(container.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR))
        .filter((element) => element.getAttribute('aria-hidden') !== 'true');

    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key !== 'Tab') return;

      const focusable = getFocusable();
      if (focusable.length === 0) {
        event.preventDefault();
        container.focus();
        return;
      }

      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    };

    getFocusable()[0]?.focus();
    container.addEventListener('keydown', handleKeyDown);
    return () => container.removeEventListener('keydown', handleKeyDown);
  }, [isActive, containerRef]);
}

export function useMediaQuery(query: string): boolean {
  const [matches, setMatches] = useState(false);

  useEffect(() => {
    if (typeof window === 'undefined' || !window.matchMedia) return;

    const mediaQuery = window.matchMedia(query);
    const update = (matches: boolean) => setMatches(matches);
    update(mediaQuery.matches);

    const listener = (event: MediaQueryListEvent) => update(event.matches);
    if (mediaQuery.addEventListener) {
      mediaQuery.addEventListener('change', listener);
      return () => mediaQuery.removeEventListener('change', listener);
    }

    mediaQuery.addListener(listener);
    return () => mediaQuery.removeListener(listener);
  }, [query]);

  return matches;
}

export const useIsMobile = () => useMediaQuery('(max-width: 480px)');
export const useIsTablet = () =>
  useMediaQuery('(min-width: 481px) and (max-width: 767px)');
export const useIsDesktop = () => useMediaQuery('(min-width: 768px)');
export const useIsWide = () => useMediaQuery('(min-width: 1024px)');
export const useIsWideScreen = () => useMediaQuery('(min-width: 1440px)');
export const useIsTouch = () => useMediaQuery('(pointer: coarse)');
export const useCanHover = () => useMediaQuery('(hover: hover)');
export const useReducedMotion = () =>
  useMediaQuery('(prefers-reduced-motion: reduce)');
export const useHighContrast = () =>
  useMediaQuery('(prefers-contrast: high)');
export const useForcedColors = () => useMediaQuery('(forced-colors: active)');

export interface SafeAreaInsets {
  top: number;
  right: number;
  bottom: number;
  left: number;
}

export function useSafeArea(): SafeAreaInsets {
  const [insets, setInsets] = useState<SafeAreaInsets>({
    top: 0,
    right: 0,
    bottom: 0,
    left: 0,
  });

  useEffect(() => {
    if (typeof window === 'undefined') return;

    const readInsets = () => {
      const probe = document.createElement('div');
      probe.setAttribute('aria-hidden', 'true');
      probe.style.cssText = [
        'position:fixed',
        'visibility:hidden',
        'pointer-events:none',
        'padding-top:env(safe-area-inset-top, 0px)',
        'padding-right:env(safe-area-inset-right, 0px)',
        'padding-bottom:env(safe-area-inset-bottom, 0px)',
        'padding-left:env(safe-area-inset-left, 0px)',
      ].join(';');
      document.documentElement.appendChild(probe);

      const style = window.getComputedStyle(probe);
      const read = (property: 'paddingTop' | 'paddingRight' | 'paddingBottom' | 'paddingLeft') =>
        Number.parseFloat(style[property]) || 0;

      setInsets({
        top: read('paddingTop'),
        right: read('paddingRight'),
        bottom: read('paddingBottom'),
        left: read('paddingLeft'),
      });
      probe.remove();
    };

    readInsets();
    window.addEventListener('resize', readInsets);
    return () => window.removeEventListener('resize', readInsets);
  }, []);

  return insets;
}
