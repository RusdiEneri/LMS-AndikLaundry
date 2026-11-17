import { cva, type VariantProps } from 'class-variance-authority';

export const buttonVariants = cva(
    'inline-flex items-center justify-center whitespace-nowrap rounded-pill text-sm font-semibold transition-all duration-[120ms] ease-standard focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 select-none cursor-pointer',
    {
        variants: {
            variant: {
                default: 'bg-accent-500 text-navy-950 hover:bg-accent-600 shadow-sm active:scale-[0.98]',
                primary: 'bg-navy-800 text-white hover:bg-navy-700 shadow-sm active:scale-[0.98]',
                secondary: 'bg-primary-700 text-white hover:bg-primary-600 shadow-sm active:scale-[0.98]',
                outline: 'border-[1.5px] border-navy-900 bg-transparent text-text hover:bg-primary-100/50 active:scale-[0.98]',
                'outline-dark': 'border-[1.5px] border-white bg-transparent text-white hover:bg-white/10 active:scale-[0.98]',
                ghost: 'text-primary-700 hover:bg-primary-100 active:scale-[0.98]',
                destructive: 'bg-danger text-white hover:bg-danger/90 shadow-sm active:scale-[0.98]',
                link: 'text-primary-700 underline-offset-4 hover:underline',
            },
            size: {
                default: 'h-10 px-6 py-2',
                sm: 'h-8 px-4 text-xs',
                lg: 'h-12 px-8 text-base',
                icon: 'h-10 w-10 p-0 rounded-full',
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
