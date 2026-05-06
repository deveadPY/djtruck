@push('styles')
<style>
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
    .form-group { display: flex; flex-direction: column; gap: .4rem; }
    .form-group.full { grid-column: 1 / -1; }

    label {
        font-size: .78rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    input, select, textarea {
        background: var(--surface2);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: .65rem .9rem;
        color: var(--text);
        font-family: inherit;
        font-size: .875rem;
        outline: none;
        width: 100%;
        transition: border-color .2s;
    }
    input:focus, select:focus, textarea:focus { border-color: var(--primary); }
    input[type="file"] { padding: .45rem .7rem; cursor: pointer; }
    input[type="checkbox"] { width: auto; cursor: pointer; }
    input[type="date"] { color-scheme: dark; }
    select option { background: var(--surface2); color: var(--text); }

    small, .text-muted { font-size: .78rem; color: var(--text-muted); }

    .flash-success {
        background: rgba(34,197,94,.1);
        border: 1px solid rgba(34,197,94,.3);
        color: var(--success);
        padding: .75rem 1rem;
        border-radius: 10px;
        font-size: .875rem;
    }
    .flash-error {
        background: rgba(239,68,68,.1);
        border: 1px solid rgba(239,68,68,.3);
        color: var(--danger);
        padding: .75rem 1rem;
        border-radius: 10px;
        font-size: .875rem;
    }
    .flash-error ul { margin: 0; padding-left: 1.2rem; }
</style>
@endpush
