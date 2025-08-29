# Laravel + Inertia Frontend (Vue → React Migration)

## Introduction

We are migrating the frontend from Vue 3 to React using Inertia and the Vibe UI KIT. During this transition, existing pages continue to use Vue via Inertia, while new and refactored screens are implemented in React (Inertia React + Vite).

For legacy Vue pages, we still use the Composition API, TypeScript, Tailwind, and the [shadcn-vue](https://www.shadcn-vue.com) component library. For new React pages, prefer Vibe UI KIT components and keep styling consistent with Tailwind.

## Official Documentation

- Laravel: https://laravel.com/docs
- Inertia.js: https://inertiajs.com
- React adapter: https://inertiajs.com/client-side-setup (React)
- Vue adapter: https://inertiajs.com/client-side-setup (Vue)

## Contributing

When adding or changing UI:
- Prefer React + Inertia for new features (Vibe UI KIT components).
- Keep Vue pages stable; migrate incrementally, page-by-page.
- Do not mix Vue and React within the same page.

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## License

MIT License
