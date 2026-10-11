export default function DangerButton({ className = '', disabled, children, ...props }) {
    return (
        <button
            {...props}
            className={
                `inline-flex shrink-0 items-center whitespace-nowrap px-4 py-2 bg-[#C1666F] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#AD5962] active:bg-[#964D55] focus:outline-none focus:ring-2 focus:ring-[#C1666F] focus:ring-offset-2 transition ease-in-out duration-150 ${
                    disabled && 'opacity-25'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
