{{--
    The outcome of the last action, kept in the page for assistive technology and tests but not
    shown: the layout's toast stack (<x-admin.toasts>) announces it. Set with the NotifiesEditors
    trait, or session()->flash('admin.status', ...) before a redirect.
--}}
@if (session('admin.status'))
    <p hidden data-flash>{{ session('admin.status') }}</p>
@endif
