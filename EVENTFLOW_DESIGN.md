# EventFlow Design System

EventFlow should feel editorial, cinematic, and deliberately composed: spacious layouts, confident type, immersive event media, and focused calls to action. The system favors a small set of reliable primitives over generic dashboard-card patterns.

## Color System

- Use a restrained neutral foundation: near-black ink, warm off-white canvas, and layered charcoal surfaces.
- Select one signature accent only after brand direction is approved; reserve it for primary actions, selected states, and meaningful status emphasis.
- Use semantic success, warning, and danger colors only for status and feedback, never as decorative palette expansion.
- Meet WCAG contrast requirements in every state.

## Typography

- Use a distinctive display face for editorial headlines and a highly legible sans serif for interface and body copy.
- Establish a compact, responsive type scale with generous headline leading and comfortable body line length.
- Use hierarchy, weight, casing, and spacing purposefully; avoid relying on color alone to convey importance.

## Spacing

- Base spacing on a consistent scale, with large vertical rhythm between editorial sections and tighter spacing inside controls.
- Favor white space over borders and unnecessary containers.
- Use responsive container widths and intentional alignment grids.

## Buttons

- Provide primary, secondary, quiet, and destructive variants only.
- Buttons have clear hover, focus-visible, disabled, loading, and pressed states.
- Corners, weight, and motion should be subtle and consistent; tap targets remain comfortable on touch devices.

## Forms

- Labels remain visible and inputs have concise help and error text.
- Validation is timely, specific, and announced accessibly.
- Group related fields, preserve entered values after failures, and make keyboard progression predictable.

## Cards

- Use cards only where content needs a contained, repeatable unit.
- Favor image-led editorial compositions with restrained borders, shadows, and corner radii.
- Do not turn every section into a card grid.

## Navigation

- Keep primary navigation brief, obvious, and stable across pages.
- Make the active route clear and provide an accessible mobile menu.
- Persistent navigation should support, not compete with, immersive content.

## Event Cards

- Lead with purposeful imagery, then surface date, title, location, and price or availability in a scannable hierarchy.
- Support keyboard use and expose the full event card as a coherent link target where appropriate.
- Hover treatment may reveal detail or subtly shift media, but must not hide essential information.

## Modals

- Use modals only for focused tasks, confirmations, or short detail views.
- Trap focus, return focus to the trigger, close with Escape, and offer an explicit close control.
- Use a full page or dedicated route for complex workflows.

## Notifications

- Use concise, actionable status messages with appropriate severity.
- Toasts must not be the only place critical results are communicated; errors remain available near the relevant task.
- Respect screen readers using appropriate live-region behavior.

## Responsive Behavior

- Design mobile-first, preserving core discovery and booking paths at every viewport.
- Reflow grids, simplify peripheral decoration, and replace hover-only behavior with explicit touch controls.
- Protect readability, targets, and image focal points across device sizes.

## Animation Principles

Motion must clarify hierarchy, continuity, feedback, or content discovery—not decorate every element.

- Future work may use GSAP and ScrollTrigger for scroll-linked sequences, parallax, pinning, horizontal sections, image clipping or reveals, scale transitions, text movement, cursor interactions, magnetic buttons, page transitions, and focused micro-interactions.
- Each animation needs a narrative or usability purpose, a defined start and end state, and graceful interruption.
- Prefer short, restrained easing for controls; reserve longer scroll choreography for major editorial moments.
- Avoid constant fade-ins, bounce effects, arbitrary movement, and competing simultaneous animations.
- Performance budgets apply: animate composited properties where possible, avoid layout thrashing, and test on constrained mobile hardware.
- GSAP is not currently part of the project dependencies and should be introduced only when a specific approved feature requires it.

## Accessibility

- Use semantic HTML, logical heading order, visible focus, keyboard support, and meaningful text alternatives.
- Maintain contrast, avoid color-only meaning, support zoom and reflow, and keep interactive targets practical.
- Test critical flows with keyboard and screen-reader expectations throughout implementation.

## Reduced-Motion Behavior

- Honor `prefers-reduced-motion` by removing nonessential scroll-linked, parallax, cursor, magnetic, and transition effects.
- Preserve content order and functionality without motion; use immediate state changes or minimal opacity changes only when needed for clarity.
- Never make booking, navigation, or essential feedback depend on animation completion.
