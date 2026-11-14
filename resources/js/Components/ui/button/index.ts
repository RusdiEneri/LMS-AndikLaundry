import { cva, type VariantProps } from 'class-variance-authority';

export const buttonVariants = cva(
    'inline-flex items-center justify-center whitespace-nowrap rounded-[6px] text-sm font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-[#6366F1]/12 disabled:pointer-events-none disabled:opacity-50 select-none cursor-pointer',
    {
        variants: {
            variant: {
                default: 'bg-[#6366F1] text-white hover:bg-[#4F46E5] hover:-translate-y-px hover:shadow-[0_4px_12px_rgba(99,102,241,0.35)] active:translate-y-0 active:bg-[#4338CA]',
                destructive: 'border border-[#EF4444] text-[#EF4444] bg-transparent hover:bg-[#EF4444]/10 hover:-translate-y-px active:translate-y-0',
                outline: 'border border-[#E8E8EC] bg-white text-[#0A0A0A] hover:bg-[#FAFAFA] hover:-translate-y-px hover:border-[#9C9C9C] active:translate-y-0',
                secondary: 'border border-[#E8E8EC] bg-transparent text-[#0A0A0A] hover:bg-[#FAFAFA] hover:-translate-y-px hover:border-[#9C9C9C] active:translate-y-0',
                ghost: 'text-[#6B6B6B] hover:text-[#0A0A0A] hover:bg-[#FAFAFA] hover:-translate-y-px active:translate-y-0',
                link: 'text-[#6366F1] underline-offset-4 hover:underline hover:text-[#4F46E5]',
            },
            size: {
                default: 'h-[38px] px-4 py-2',
                sm: 'h-8 px-3 text-xs',
                lg: 'h-11 px-6 text-base',
                icon: 'h-[38px] w-[38px] p-0',
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
