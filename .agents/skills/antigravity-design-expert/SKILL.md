---
name: antigravity-design-expert
description: Design and refine the Jet Charter flight-booking interface in this Laravel app using Blade, Bootstrap, project CSS, and its existing JavaScript. Use for page layout, responsive UI, interaction design, and visual polish.
---

# Jet Charter UI/UX Design Skill

## Purpose

Design clear, trustworthy, responsive interfaces for this flight-booking project. Keep the existing product identity and make booking tasks easy to understand and complete. Apply this skill to Blade views and their related styles or browser-side interactions.

## Project stack and constraints

- Use Laravel routes, controllers, Blade templates, and the existing application structure.
- Use Bootstrap 5 conventions already present in the layouts, plus the project's CSS and design tokens. Check the relevant layout and stylesheet before adding styles.
- Use the existing JavaScript setup and patterns. Prefer small vanilla JavaScript enhancements; use Alpine only where the project already uses it.
- Do not introduce React, Next.js, Vue, React Three Fiber, GSAP, or another UI framework or animation library. Do not make a framework migration as part of a visual task.
- Do not add Tailwind as a new UI approach or add dependencies unless the user explicitly requests it. Prefer Bootstrap and the existing CSS.
- Keep changes within the requested page or flow. Do not replace Laravel Blade with another templating system.

## Product context

This is a Jet Charter flight-booking application. Depending on the page, users may search flights, compare itineraries, enter passenger details, select booking add-ons such as checked baggage, pay, and review bookings. Admin pages manage the application's operational data.

Use the labels, data, routes, and booking rules already present in the application. Do not invent flight availability, prices, booking status, passenger records, or successful actions. Keep customer-facing Vietnamese copy consistent with the surrounding page.

## Design principles

- Prioritize a clear task hierarchy: show the current step, key itinerary details, price or total where relevant, and the next action.
- Use the project's existing `bf-*` classes, CSS variables, components, and layout patterns where suitable. Extend them instead of creating competing design systems.
- Use Bootstrap grid, spacing, forms, buttons, alerts, tables, and utilities where they fit. Add scoped custom CSS for project-specific presentation.
- Keep surfaces restrained and readable. Use spacing, typography, borders, and subtle shadows to establish hierarchy; use glass effects, gradients, or decorative depth only when they support the existing visual identity and do not reduce contrast.
- Make booking summaries and important actions easy to find on both desktop and mobile. Avoid dense forms and ambiguous totals.
- Use consistent states for default, hover, focus, disabled, loading, validation error, empty, and success states when those states exist in the feature.
- Do not add decorative controls, dead links, placeholder actions, or visual elements that imply functionality the backend does not provide.

## Preserve application behavior

Before editing, inspect the target Blade view, its layout, route and controller behavior, related CSS, and JavaScript hooks. Preserve existing:

- Route names and `route()` usage.
- Form actions, HTTP methods, CSRF tokens, method spoofing, input names, IDs, and validation/error output.
- `old()` values, session data, authorization checks, and server-rendered state.
- Data attributes, selectors, and element structure used by existing scripts.

Do not replace a working server-side flow with a visual mockup. If a requested control has no implemented route or behavior, do not imply that it works; explain the missing application behavior in the response.

## Responsive behavior and accessibility

- Build from the existing Bootstrap breakpoints and verify that forms, tables, summaries, and primary actions remain usable on narrow screens.
- Use semantic HTML, associated labels, descriptive button text, keyboard-operable controls, visible focus indicators, and sensible heading order.
- Preserve sufficient text and control contrast. Do not rely on color alone to communicate validation or booking status.
- Keep motion subtle and optional. Honor `prefers-reduced-motion: reduce`; avoid scroll hijacking, parallax requirements, and essential information conveyed only by animation.

## Working approach

1. Identify the requested page or flow and inspect its current implementation and shared styles.
2. Make the smallest cohesive UI change that fits the existing product and preserves its data flow.
3. Reuse existing patterns and keep new CSS scoped and maintainable. Avoid unrelated cleanup or broad redesigns.
4. Review the affected states and responsive layout. If Vite source assets change, use the repository's existing build command when available; report validation performed and any behavior that could not be checked.
