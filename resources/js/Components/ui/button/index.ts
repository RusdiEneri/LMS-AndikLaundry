import { cva, type VariantProps } from 'class-variance-authority';

export const buttonVariants = cva(
    'inline-flex items-center justify-center whitespace-nowrap rounded-xl text-sm font-bold transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F5FE8] focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 select-none cursor-pointer',
    {
        variants: {
            variant: {
                default: 'bg-[#FF8A5B] text-[#0A1033] hover:bg-[#F26B3A] shadow-sm hover:shadow-[0_4px_14px_rgba(255,138,91,0.35)] active:scale-[0.98]',
                primary: 'bg-[#4F5FE8] text-white hover:bg-[#3D4EC7] shadow-sm hover:shadow-md active:scale-[0.98]',
                destructive: 'bg-[#E5484D] text-white hover:bg-[#C93B40] shadow-sm active:scale-[0.98]',
                outline: 'border border-slate-200 bg-white text-[#10194D] hover:bg-slate-50 hover:border-slate-300 shadow-xs active:scale-[0.98]',
                secondary: 'bg-slate-100 hover:bg-slate-200 text-slate-800 active:scale-[0.98]',
                ghost: 'text-slate-700 hover:bg-slate-100 hover:text-[#10194D]',
                link: 'text-[#4F5FE8] underline-offset-4 hover:underline',
            },
            size: {
                default: 'h-[40px] px-4 py-2',
                sm: 'h-8 px-3 text-xs rounded-lg',
                lg: 'h-12 px-6 text-base rounded-2xl',
                icon: 'h-[40px] w-[40px] p-0 rounded-xl',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    }
);

export type ButtonVariants = VariantProps<typeof buttonVariants>;
export { default as Button } from './Button.vue';
