---
applyTo: "templates/**"
---

# Template Coding Instructions (Twig)

## Form Rendering

Always declare the form theme at the top of the template:
```twig
{% form_theme form 'bootstrap_5_layout.html.twig' %}
{# Or use the custom theme: #}
{% form_theme form 'app_form_layout.html.twig' %}
```

Rendering methods:
```twig
{{ form_start(form) }}
{{ form_row(form.fieldName) }}    {# Label + widget + errors — use this by default #}
{{ form_widget(form.fieldName) }} {# Input only — you lose label and validation display #}
{{ form_label(form.fieldName) }}  {# Label only #}
{{ form_errors(form.fieldName) }} {# Errors only #}
{{ form_end(form) }}
```

Never use `form_widget()` alone — you'll lose labels and validation messages.

## Entity Type Checking

```twig
{% if event is instanceof('App\\Entity\\ContestEvent') %}
    {# Contest-specific content #}
{% elseif event is instanceof('App\\Entity\\TrainingEvent') %}
    {# Training-specific content #}
{% else %}
    {# Default content #}
{% endif %}
```

## Modal Pattern

Base modal using `_modal.html.twig` (Stimulus `modal` controller):

```twig
{# Trigger link/button #}
<a href="{{ path('app_some_route', {id: entity.id}) }}"
   data-action="modal#open"
   data-title="Modal Title"
   data-size="lg">
    Open Modal
</a>
```

The modal controller fetches content via AJAX, injects it into the modal body, and handles form submissions. Prefix inline/partial modal files with `_` (e.g., `_participation_modal.html.twig`).

## Bootstrap Tooltips

Tooltips are initialized globally in `app.ts` on any element with `data-bs-toggle="tooltip"`.

```twig
{# Basic tooltip #}
<button data-bs-toggle="tooltip" title="Explanation here">Action</button>

{# Disabled action with tooltip (pattern for conveying why action is unavailable) #}
<button class="btn btn-secondary" disabled
        data-bs-toggle="tooltip"
        title="Vous n'êtes pas dans un groupe autorisé pour cet événement">
    <em class="fa-solid fa-calendar-plus me-2"></em>
    S'inscrire
</button>
```

## Authorization in Templates

```twig
{# Role-based #}
{% if is_granted('ROLE_ADMIN') %}
    <a href="{{ path('admin') }}">Admin Panel</a>
{% endif %}

{# Three-tier: admin or owner #}
{% if is_granted('ROLE_ADMIN') or (app.user and app.user.id == user.id) %}
    {# Visible to admin or the account owner #}
{% endif %}

{# Event participation authorization #}
{% set can_participate = eventHelper.canLicenseeParticipateInEvent(licensee, event) %}
<button class="btn {{ can_participate ? 'btn-primary' : 'btn-secondary' }}"
        {{ not can_participate ? 'disabled' : '' }}
        {{ not can_participate
            ? 'data-bs-toggle="tooltip" title="Groupe requis"'
            : 'data-bs-toggle="modal" data-bs-target="#participationModal"' }}>
    S'inscrire
</button>
```

## Cards

```twig
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <em class="fa-solid fa-icon me-2"></em>
            Card Title
        </h5>
    </div>
    <div class="card-body">
        {% if items is empty %}
            <div class="text-center text-muted py-4">
                <em class="fa-solid fa-inbox fa-3x mb-3 d-block"></em>
                <p class="mb-0">Aucun élément</p>
            </div>
        {% else %}
            {# Content #}
        {% endif %}
    </div>
</div>
```

Card header color schemes: `bg-primary` (personal/main), `bg-success` (results/licensees), `bg-warning` (admin), `bg-info` (help), `bg-danger` (errors/deletions).

## Flash Messages

```twig
{% for type in ['success', 'danger', 'warning', 'info'] %}
    {% for message in app.flashes(type) %}
        <div class="alert alert-{{ type }} alert-dismissible fade show" role="alert">
            {{ message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    {% endfor %}
{% endfor %}
```

## Partials / Includes

```twig
{{ include('path/to/_partial.html.twig', {
    variable: value,
    entity: object,
}) }}
```

## Responsive Layout

```twig
<div class="row">
    <div class="col-lg-4 col-12">  {# Sidebar: 4 cols desktop, full width mobile #}
        ...
    </div>
    <div class="col-lg-8 col-12">  {# Main: 8 cols desktop, full width mobile #}
        ...
    </div>
</div>

{# Mobile-only / Desktop-only #}
<div class="d-block d-md-none">Mobile only</div>
<div class="d-none d-md-block">Desktop only</div>
```

## Template Directory Reference

| Directory / File | Purpose |
|-----------------|---------|
| `templates/admin/` | EasyAdmin customizations |
| `templates/club/` | Club information pages |
| `templates/club_application/` | Membership application views |
| `templates/club_equipment/` | Equipment inventory and loan management |
| `templates/email/` | Transactional email templates (Inky markup) |
| `templates/email_notification/` | Notification email templates |
| `templates/event/` | Events: calendar, contest detail, training detail |
| `templates/group/` | Group management |
| `templates/homepage/` | Dashboard |
| `templates/legal/` | Legal pages (CGU, privacy policy) |
| `templates/license/` | License application and renewal |
| `templates/licensee/` | Archer profile, trombinoscope |
| `templates/licensee_management/` | Admin licensee management |
| `templates/login/` | Authentication |
| `templates/management/` | General management views |
| `templates/mobile/` | Mobile-specific views |
| `templates/practice_advice/` | Training resources |
| `templates/registration/` | New user registration |
| `templates/reset_password/` | Password reset flow |
| `templates/user/` | User account |
| `templates/user_management/` | Admin user management |
| `templates/_modal.html.twig` | Reusable modal component (Stimulus-powered) |
| `templates/base.html.twig` | Base layout (navbar, mobile tab bar, modals) |
| `templates/base_public.html.twig` | Public pages layout (login, registration) |
| `templates/app_form_layout.html.twig` | Custom Bootstrap 5 form theme |
