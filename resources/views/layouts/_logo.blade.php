{{-- The Shiplog mark: three lines of a log on the coral square. size: null (26px, the command bar) or 'lg' (56px, sign-in). --}}
<span @class(['app-mark', 'app-mark-lg' => ($size ?? null) === 'lg']) aria-hidden="true">
    <svg viewBox="0 0 24 24"><path d="M5 7h14M5 12h10M5 17h6"/></svg>
</span>
