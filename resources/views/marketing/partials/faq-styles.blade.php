<style>
/* سوالات متداول (آکاردئون کشویی) — مشترک بین صفحه‌های اصلی سایت */
.fq{--fq-accent:var(--teal,#13a895);--fq-deep:var(--deep,var(--teal-dark,#087f78));--fq-line:var(--line,#dcebe6);--fq-ink:var(--ink,#102b3a);--fq-muted:var(--muted,#627782);display:grid;gap:12px;align-items:start;margin-inline:auto}
.fq.fq-narrow{max-width:820px}
.fq.fq-2{grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.fq-item{position:relative;overflow:hidden;background:#fff;border:1px solid var(--fq-line);border-radius:18px;box-shadow:0 1px 2px rgba(16,43,58,.03);transition:border-color .3s,box-shadow .3s}
.fq-item::before{content:"";position:absolute;inset-block:0;inset-inline-start:0;width:4px;background:linear-gradient(180deg,var(--fq-accent),var(--fq-deep));transform:scaleY(0);transform-origin:top;transition:transform .4s cubic-bezier(.4,0,.2,1)}
.fq-item:hover{border-color:color-mix(in srgb,var(--fq-accent) 45%,var(--fq-line));box-shadow:0 10px 26px rgba(11,107,92,.08)}
.fq-item[open]:not(.is-closing){border-color:color-mix(in srgb,var(--fq-accent) 55%,transparent);box-shadow:0 18px 40px rgba(11,107,92,.13)}
.fq-item[open]:not(.is-closing)::before{transform:scaleY(1)}
.fq-q{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 22px;font-weight:800;font-size:1rem;line-height:1.9;color:var(--fq-ink);user-select:none;-webkit-tap-highlight-color:transparent;transition:color .25s}
.fq-q::-webkit-details-marker{display:none}
.fq-q::marker{content:""}
.fq-q:hover,.fq-item[open]:not(.is-closing) .fq-q{color:var(--fq-deep)}
.fq-q:focus-visible{outline:2px solid var(--fq-accent);outline-offset:-4px;border-radius:inherit}
.fq-q::after{content:"";flex:none;width:32px;height:32px;border-radius:11px;color:var(--fq-deep);background:linear-gradient(currentColor,currentColor) center/12px 2px no-repeat,linear-gradient(currentColor,currentColor) center/2px 12px no-repeat,#e2f5f0;transition:transform .4s cubic-bezier(.4,0,.2,1),background-color .3s,color .3s}
.fq-item[open]:not(.is-closing) .fq-q::after{transform:rotate(45deg);color:#fff;background-color:var(--fq-deep)}
.fq-a{padding:0 22px 20px;color:var(--fq-muted);font-size:.97rem;line-height:2.05}
.fq-a p{margin:0;color:inherit;font-size:inherit;line-height:inherit}
@media(max-width:760px){.fq.fq-2{grid-template-columns:1fr}.fq-q{padding:15px 16px;font-size:.96rem}.fq-a{padding:0 16px 16px}}
@media(prefers-reduced-motion:reduce){.fq-item,.fq-item::before,.fq-q,.fq-q::after{transition:none}}
@media print{.fq-item{break-inside:avoid}}
</style>
