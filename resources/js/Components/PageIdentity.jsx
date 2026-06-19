export default function PageIdentity({
    pageId = '',
}) {
    return (
        pageId ? (
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-y-0 right-4 flex items-center select-none"
            >
                <span className="rounded-full border border-slate-300/55 bg-white/40 px-3 py-1 text-sm font-semibold tracking-[0.2em] text-slate-700/65 shadow-[0_2px_12px_rgba(15,23,42,0.05)] backdrop-blur-[1px] sm:text-base">
                    {pageId}
                </span>
            </div>
        ) : null
    );
}
