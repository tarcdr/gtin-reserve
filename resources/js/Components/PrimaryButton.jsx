export default function PrimaryButton({ className = '', disabled, children, ...props }) {
    return (
        <button
            {...props}
            className={
                `inline-flex shrink-0 items-center whitespace-nowrap px-4 py-2 bg-[#4C5E86] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#435477] focus:bg-[#435477] active:bg-[#394866] focus:outline-none focus:ring-2 focus:ring-[#4C5E86] focus:ring-offset-2 transition ease-in-out duration-150 ${
                    disabled && 'opacity-25'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
