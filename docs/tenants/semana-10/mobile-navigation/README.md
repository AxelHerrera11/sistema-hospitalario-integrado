# Mobile Navigation

Componentes disponibles desde `index.ts`:

- `BottomNavigation`
- `Sidebar`
- `HamburgerButton`
- `TabBar`
- `Breadcrumb`
- `SkipLink`

Los hooks responsive y tipos compartidos también se exportan desde `index.ts`. Los componentes utilizan tokens definidos en `breakpoints.css`; importa esa hoja una sola vez en la aplicación.

Ejemplo mínimo:

```tsx
import { useState } from 'react';
import { BottomNavigation } from './mobile-navigation';

const items = [
  { id: 'home', label: 'Inicio', icon: '⌂' },
  { id: 'appointments', label: 'Citas', icon: '▦' },
];

export function ExampleNavigation() {
  const [activeItem, setActiveItem] = useState('home');

  return (
    <BottomNavigation
      items={items}
      activeItem={activeItem}
      onChange={setActiveItem}
    />
  );
}
```
