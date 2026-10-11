export default function SuccessButton({ className = '', disabled, children, ...props }) {
    return (
        <button
            {...props}
            className={
                `inline-flex shrink-0 items-center whitespace-nowrap px-4 py-2 bg-[#5E9E6B] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#518B5D] focus:bg-[#518B5D] active:bg-[#44764F] focus:outline-none focus:ring-2 focus:ring-[#5E9E6B] focus:ring-offset-2 transition ease-in-out duration-150 ${
                    disabled ? 'opacity-25' : ''
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
